"""
Day34：端到端 RAG Pipeline

完整流程：加载 → 分片 → 向量化 → 存储 → 检索
"""

import os
import sys
import requests
import chromadb
from typing import Optional

# 添加父目录到路径
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from rag.document_loader import load_all_docs, chunk_all_docs


class RAGPipeline:
    """RAG 完整流程：加载 → 分片 → 向量化 → 存储 → 检索"""

    def __init__(self, knowledge_dir: str = "data/knowledge",
                 chroma_path: str = "./chroma_data",
                 chunk_size: int = 500, chunk_overlap: int = 50):
        self.knowledge_dir = knowledge_dir
        self.chunk_size = chunk_size
        self.chunk_overlap = chunk_overlap

        # 初始化 Chroma（RAG 场景推荐余弦距离：范围固定，0.5 可作通用阈值）
        self.client = chromadb.PersistentClient(path=chroma_path)
        self.collection = self.client.get_or_create_collection(
            "knowledge_base",
            metadata={"hnsw:space": "cosine"}  # 默认是 l2，这里显式切余弦
        )

        # 检索结果缓存：相同查询不重复调 Embedding API
        self._embed_cache: dict[str, list[float]] = {}

    # ── Embedding ──
    def _embed(self, text: str) -> list[float]:
        """调用 ollama 获取文本的 embedding 向量（带缓存：相同文本不重复调用 API）"""
        if text in self._embed_cache:
            return self._embed_cache[text]  # 命中缓存，省一次 HTTP 请求

        resp = requests.post(
            "http://localhost:11434/api/embeddings",
            json={"model": "nomic-embed-text", "prompt": text},
            timeout=30
        )
        resp.raise_for_status()
        vec = resp.json()["embedding"]
        self._embed_cache[text] = vec  # 写入缓存
        return vec

    # ── 构建索引（一次性） ──
    def build_index(self, rebuild: bool = False):
        """将知识库所有文档分片→向量化→存入 Chroma

        Args:
            rebuild: 是否重建索引（删除旧数据重新插入）
        """
        if rebuild:
            self.client.delete_collection("knowledge_base")
            self.collection = self.client.get_or_create_collection(
                "knowledge_base",
                metadata={"hnsw:space": "cosine"}
            )

        # 如果已有数据，跳过
        if self.collection.count() > 0:
            print(f"索引已存在，共 {self.collection.count()} 条。如需重建请传 rebuild=True")
            return

        # 加载 + 分片
        docs = load_all_docs(self.knowledge_dir)
        chunks = chunk_all_docs(docs, self.chunk_size, self.chunk_overlap)
        print(f"加载 {len(docs)} 个文档，分片 {len(chunks)} 条")

        # 批量向量化 + 插入
        ids = []
        embeddings = []
        documents = []
        metadatas = []

        for i, chunk in enumerate(chunks):
            embedding = self._embed(chunk["content"])
            ids.append(f"chunk_{i}")
            embeddings.append(embedding)
            documents.append(chunk["content"])
            metadatas.append({
                "file": chunk["file"],
                "chunk_index": chunk["chunk_index"],
            })
            if (i + 1) % 5 == 0:
                print(f"  已向量化 {i + 1}/{len(chunks)} 条")

        self.collection.add(
            ids=ids,
            embeddings=embeddings,
            documents=documents,
            metadatas=metadatas,
        )
        print(f"索引构建完成，共 {self.collection.count()} 条")

    # ── 增量添加文档 ──
    def add_document(self, file_path: str):
        """增量添加单个文档，不重建整个索引"""
        filename = os.path.basename(file_path)
        with open(file_path, encoding="utf-8") as f:
            content = f.read()

        chunks = chunk_all_docs(
            [{"file": filename, "content": content}],
            self.chunk_size, self.chunk_overlap
        )

        # 获取当前最大 ID，避免冲突
        existing_count = self.collection.count()

        ids = []
        embeddings = []
        documents = []
        metadatas = []

        for i, chunk in enumerate(chunks):
            embedding = self._embed(chunk["content"])
            ids.append(f"chunk_{existing_count + i}")
            embeddings.append(embedding)
            documents.append(chunk["content"])
            metadatas.append({
                "file": chunk["file"],
                "chunk_index": chunk["chunk_index"],
            })

        self.collection.add(
            ids=ids,
            embeddings=embeddings,
            documents=documents,
            metadatas=metadatas,
        )
        print(f"增量添加 {filename}：{len(chunks)} 条片段，总计 {self.collection.count()} 条")

    # ── 检索 ──
    def search(self, query: str, top_k: int = 3, min_similarity: float = 0.5) -> list[dict]:
        """语义检索：输入自然语言，返回 Top-K 相关片段

        Args:
            query: 用户自然语言查询
            top_k: 最多返回条数
            min_similarity: 余弦相似度阈值（范围 [-1,1]）。低于该值视为不相关并丢弃。
                            0.5 是通用经验值，可跨项目复用（Day33 结论）。
                            余弦距离 = 1 - 余弦相似度，Chroma 返回的是距离，需换算。
        """
        query_vec = self._embed(query)
        results = self.collection.query(
            query_embeddings=[query_vec],
            n_results=top_k,
            include=["documents", "metadatas", "distances"]
        )

        output = []
        for i in range(len(results["ids"][0])):
            cos_dist = results["distances"][0][i]
            similarity = 1 - cos_dist  # 余弦距离 → 余弦相似度
            if similarity < min_similarity:  # 相似度过低 → 不相关，丢弃
                continue
            output.append({
                "content": results["documents"][0][i],
                "file": results["metadatas"][0][i]["file"],
                "chunk_index": results["metadatas"][0][i]["chunk_index"],
                "similarity": similarity,
            })
        return output

    # ── 格式化输出 ──
    def search_pretty(self, query: str, top_k: int = 3):
        """检索并格式化打印结果"""
        results = self.search(query, top_k)
        print(f"\n查询: {query}")
        print("=" * 50)
        for i, r in enumerate(results):
            preview = r["content"][:100].replace("\n", " ")
            print(f"\n  [{i+1}] {r['file']} (片段{r['chunk_index']}, 相似度: {r['similarity']:.3f})")
            print(f"      {preview}...")
        return results

    # ── LLM 底层调用（生成模型，用于精筛和回答） ──
    def _llm(self, prompt: str, model: str = "deepseek-r1:7b", max_tokens: int = 300) -> str:
        """调用 ollama 生成模型，返回文本"""
        resp = requests.post(
            "http://localhost:11434/api/generate",
            json={"model": model, "prompt": prompt, "stream": False,
                  "options": {"num_predict": max_tokens}},
            timeout=120
        )
        resp.raise_for_status()
        return resp.json()["response"]

    # ── LLM 精筛（第二层过滤） ──
    def rerank(self, query: str, candidates: list[dict], rerank_model: str = "deepseek-r1:7b") -> list[dict]:
        """把粗筛后的候选片段交给 LLM，逐个判断是否与查询相关，返回相关的片段

        精筛用 deepseek-r1（推理模型，判断最可靠）。关键参数是 num_predict 必须给足
        （400+）：推理模型会先输出冗长思考，token 配额不足时正文没输出就返回空。
        （踩过坑：lfm2 太弱会复述片段，llama3.2 判断抖动，只有 R1 给足 token 最稳）

        解析策略（宁可漏，不可错）：R1 结论在输出末尾，从尾部提取。
        ⚠️ 坑：不能直接用 endswith("相关") —— "不相关".endswith("相关") 是 True！
           必须先排除"不相关"，再看尾部是否"相关"。

        Args:
            query: 用户查询
            candidates: search() 余弦粗筛后的候选片段
            rerank_model: 精筛用的生成模型（默认 deepseek-r1:7b）
        """
        relevant = []
        for r in candidates:
            prompt = (
                "你是知识库相关性判断助手。判断下面的【问题】和【片段】是否相关。\n"
                "只回答两个字：相关 或 不相关。\n\n"
                f"【问题】{query}\n"
                f"【片段】{r['content'][:300]}\n"
            )
            answer = self._llm(prompt, model=rerank_model, max_tokens=400)  # 必须给足，否则思考占满返回空
            cleaned = answer.strip()
            # 从尾部提取结论（R1 思考在中间，结论在末尾）
            tail = cleaned[-10:]
            if "不相关" in tail:
                is_relevant = False
            elif "相关" in tail:
                is_relevant = True
            else:
                is_relevant = False  # 提取不到结论 → 宁可漏
            if is_relevant:
                relevant.append(r)
            print(f"    精筛 [{r['file']}#{r['chunk_index']}] 相似度 {r['similarity']:.3f} → 判断: {tail!r}")
        return relevant

    # ── 基于检索片段生成回答（RAG 完整流程的"生成"环节） ──
    def generate(self, query: str, contexts: list[dict], model: str = "deepseek-r1:7b") -> str:
        """把检索到的相关片段作为上下文，让 LLM 生成回答"""
        context_text = "\n\n".join(
            f"[来源: {r['file']}]\n{r['content'][:400]}" for r in contexts
        )
        prompt = (
            "你是基于知识库回答问题的助手。\n"
            "只能根据下面提供的知识库片段回答，不要编造。如果片段不足以回答，就说'知识库中没有相关信息'。\n\n"
            f"【知识库片段】\n{context_text}\n\n"
            f"【问题】{query}\n"
            f"【回答】"
        )
        return self._llm(prompt, model=model, max_tokens=500)

    # ── 完整 RAG 流程：粗筛 → 精筛 → 生成 ──
    def ask(self, query: str, top_k: int = 5, min_similarity: float = 0.5,
            model: str = "deepseek-r1:7b") -> dict:
        """端到端问答：余弦粗筛 → LLM 精筛 → 基于片段回答

        Returns:
            {"query", "retrieved": 粗筛结果, "relevant": 精筛后相关片段,
             "answer": 最终回答, "empty": 是否无相关内容}
        """
        print(f"\n查询: {query}")
        print("─" * 50)

        # 1. 余弦粗筛（宁松勿紧：多取候选，交给 LLM 判断）
        candidates = self.search(query, top_k=top_k, min_similarity=min_similarity)
        print(f"[粗筛] 余弦检索到 {len(candidates)} 条候选")

        # 2. 没有候选 → 直接返回空
        if not candidates:
            print("[精筛] 无候选片段，跳过")
            return {"query": query, "retrieved": [], "relevant": [],
                    "answer": "知识库中没有相关内容。", "empty": True}

        # 3. LLM 精筛
        relevant = self.rerank(query, candidates)
        print(f"[精筛] 通过 {len(relevant)} 条")

        # 4. 精筛为空 → 不生成回答
        if not relevant:
            return {"query": query, "retrieved": candidates, "relevant": [],
                    "answer": "知识库中没有与问题相关的内容。", "empty": True}

        # 5. 基于相关片段生成回答
        answer = self.generate(query, relevant, model=model)
        print(f"[生成] 基于 {len(relevant)} 条片段生成回答\n")
        return {"query": query, "retrieved": candidates, "relevant": relevant,
                "answer": answer, "empty": False}


# ══════════════════════════════════════════════════════════
# 端到端测试
# ══════════════════════════════════════════════════════════

if __name__ == "__main__":
    # 路径配置
    base_dir = os.path.dirname(__file__)
    knowledge_dir = os.path.join(base_dir, "..", "data", "knowledge")
    chroma_path = os.path.join(base_dir, "..", "chroma_data")

    rag = RAGPipeline(knowledge_dir=knowledge_dir, chroma_path=chroma_path)

    # CLI 用法：
    #   python rag_pipeline.py "查询"         → 余弦检索（只看 Top-K 片段）
    #   python rag_pipeline.py ask "查询"      → 完整 RAG：粗筛 + LLM 精筛 + 生成回答
    #   python rag_pipeline.py                  → 构建索引 + 5 个测试查询
    if len(sys.argv) > 2 and sys.argv[1] == "ask":
        query = sys.argv[2]
        result = rag.ask(query)
        print("=" * 50)
        print(result["answer"])
    elif len(sys.argv) > 1:
        query = sys.argv[1]
        rag.search_pretty(query)
    else:
        # 交互模式：构建索引 + 测试
        rag.build_index()  # 首次运行构建索引，之后自动跳过

        # 端到端测试
        test_queries = [
            "Python 变量怎么命名",
            "API 接口设计有什么规范",
            "数据库查询应该注意什么",
            "Git 提交信息格式",
            "代码出错怎么处理",
        ]

        for q in test_queries:
            rag.search_pretty(q, top_k=2)
