# Day 34：端到端 RAG Pipeline

> 目标：把 Day 29-33 串联成完整的 RAG Pipeline。

---

## 学习路线（约 4 小时）

```
封装 Pipeline（40min）→ 端到端测试（30min）→ CLI 入口（20min）→ 代码整理（30min）→ 休息 → 可选优化（60min）
```

---

## 第一步：封装 RAG Pipeline 类（40min）

```python
"""myagent/rag_pipeline.py —— RAG 全流程封装"""
import os
import requests
import chromadb
from typing import Optional

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

    # ── 文档加载 ──
    def _load_docs(self) -> list[dict]:
        docs = []
        for filename in os.listdir(self.knowledge_dir):
            if filename.endswith(".md"):
                with open(os.path.join(self.knowledge_dir, filename), encoding="utf-8") as f:
                    docs.append({"file": filename, "content": f.read()})
        return docs

    # ── 分片 ──
    def _split(self, text: str) -> list[str]:
        chunks = []
        start = 0
        while start < len(text):
            chunks.append(text[start:start + self.chunk_size])
            start += self.chunk_size - self.chunk_overlap
        return chunks

    # ── Embedding ──
    def _embed(self, text: str) -> list[float]:
        resp = requests.post(
            "http://localhost:11434/api/embeddings",
            json={"model": "nomic-embed-text", "prompt": text}
        )
        return resp.json()["embedding"]

    # ── 构建索引（一次性） ──
    def build_index(self):
        """将知识库所有文档分片→向量化→存入 Chroma"""
        docs = self._load_docs()
        print(f"加载了 {len(docs)} 个文档")

        total_chunks = 0
        for doc in docs:
            chunks = self._split(doc["content"])
            for i, chunk in enumerate(chunks):
                embedding = self._embed(chunk)
                self.collection.add(
                    ids=[f"{doc['file']}_{i}"],
                    embeddings=[embedding],
                    documents=[chunk],
                    metadatas=[{"file": doc["file"], "chunk_index": i}]
                )
                total_chunks += 1
            print(f"  {doc['file']}: {len(chunks)} 个片段")

        print(f"索引构建完成，共 {total_chunks} 个片段")

    # ── 检索 ──
    def search(self, query: str, top_k: int = 3) -> list[dict]:
        """语义检索"""
        query_vec = self._embed(query)
        results = self.collection.query(
            query_embeddings=[query_vec],
            n_results=top_k,
            include=["documents", "metadatas", "distances"]
        )
        output = []
        for i in range(len(results["ids"][0])):
            output.append({
                "content": results["documents"][0][i][:200],
                "file": results["metadatas"][0][i]["file"],
                "distance": results["distances"][0][i],
            })
        return output

    # ── 格式化输出 ──
    def search_pretty(self, query: str, top_k: int = 3):
        results = self.search(query, top_k)
        print(f"\n查询: {query}\n{'='*50}")
        for i, r in enumerate(results):
            print(f"\n[{i+1}] {r['file']}  (距离: {r['distance']:.3f})")
            print(f"    {r['content']}...")
        return results
```

---

## 第二步：端到端测试（30min）

```python
# 测试脚本
from rag_pipeline import RAGPipeline

# 1. 构建索引（只需运行一次）
rag = RAGPipeline(knowledge_dir="data/knowledge")
# rag.build_index()  # 首次运行需要，之后注释掉

# 2. 检索测试
test_queries = [
    "Python 变量怎么命名",
    "API 接口设计有什么规范",
    "数据库查询应该注意什么",
    "Git 提交信息格式",
    "代码出错怎么处理",
]

for q in test_queries:
    rag.search_pretty(q, top_k=2)
```

---

## 第三步：CLI 入口（20min）

```python
"""search.py —— 命令行检索入口"""
import sys
from rag_pipeline import RAGPipeline

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("用法: python search.py <查询内容>")
        sys.exit(1)

    query = sys.argv[1]
    rag = RAGPipeline()
    rag.search_pretty(query)

# 使用: python search.py "如何防止 SQL 注入"
```

---

## 第四步：代码整理（30min）

- 检查每个函数有 docstring
- 检查异常处理（文件不存在、API 失败）
- 确认 `data/knowledge/` 有至少 3 篇文档
- 代码提交到 GitHub

---

## 第五步（可选优化，60min）

- 增量添加文档（不重建整个索引）
- 检索结果缓存（相同查询不重复调 Embedding）
- 加分片去重（完全相同的内容不重复存储）

---

## 检验标准

1. **默写**：能写出 `RAGPipeline` 的核心结构（load → split → embed → store → search）
2. **一句话**："文档加载→分片→向量化→存 Chroma→用户查询→向量化→检索→返回 Top-K"
3. **追问**：如果知识库更新了怎么办？（调 `build_index()` 重建，或实现增量添加）

---

## 产出文件

- `rag/rag_pipeline.py` — 完整 Pipeline 类 + CLI 入口
- `rag/chroma_demo.py` — Chroma 向量化 + 检索演示