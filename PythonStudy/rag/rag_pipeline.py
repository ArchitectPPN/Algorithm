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

        # 初始化 Chroma
        self.client = chromadb.PersistentClient(path=chroma_path)
        self.collection = self.client.get_or_create_collection("knowledge_base")

    # ── Embedding ──
    def _embed(self, text: str) -> list[float]:
        """调用 ollama 获取文本的 embedding 向量"""
        resp = requests.post(
            "http://localhost:11434/api/embeddings",
            json={"model": "nomic-embed-text", "prompt": text},
            timeout=30
        )
        resp.raise_for_status()
        return resp.json()["embedding"]

    # ── 构建索引（一次性） ──
    def build_index(self, rebuild: bool = False):
        """将知识库所有文档分片→向量化→存入 Chroma

        Args:
            rebuild: 是否重建索引（删除旧数据重新插入）
        """
        if rebuild:
            self.client.delete_collection("knowledge_base")
            self.collection = self.client.get_or_create_collection("knowledge_base")

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
    def search(self, query: str, top_k: int = 3) -> list[dict]:
        """语义检索：输入自然语言，返回 Top-K 相关片段"""
        query_vec = self._embed(query)
        results = self.collection.query(
            query_embeddings=[query_vec],
            n_results=top_k,
            include=["documents", "metadatas", "distances"]
        )

        output = []
        for i in range(len(results["ids"][0])):
            output.append({
                "content": results["documents"][0][i],
                "file": results["metadatas"][0][i]["file"],
                "chunk_index": results["metadatas"][0][i]["chunk_index"],
                "distance": results["distances"][0][i],
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
            print(f"\n  [{i+1}] {r['file']} (片段{r['chunk_index']}, 距离: {r['distance']:.3f})")
            print(f"      {preview}...")
        return results


# ══════════════════════════════════════════════════════════
# 端到端测试
# ══════════════════════════════════════════════════════════

if __name__ == "__main__":
    # 路径配置
    base_dir = os.path.dirname(__file__)
    knowledge_dir = os.path.join(base_dir, "..", "data", "knowledge")
    chroma_path = os.path.join(base_dir, "..", "chroma_data")

    # CLI 模式：python rag_pipeline.py "查询内容"
    if len(sys.argv) > 1:
        rag = RAGPipeline(knowledge_dir=knowledge_dir, chroma_path=chroma_path)
        query = sys.argv[1]
        rag.search_pretty(query)
    else:
        # 交互模式：构建索引 + 测试
        rag = RAGPipeline(knowledge_dir=knowledge_dir, chroma_path=chroma_path)
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
