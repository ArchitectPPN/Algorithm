"""
Day33：向量数据库 Chroma

功能：
1. 创建 Chroma 集合
2. 加载分片 → 向量化 → 插入
3. 语义检索 Top-K
4. 验证检索效果
"""

import os
import sys
import requests
import chromadb

# 添加父目录到路径，方便导入
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from rag.document_loader import load_all_docs, chunk_all_docs


# ══════════════════════════════════════════════════════════
# 工具函数
# ══════════════════════════════════════════════════════════

def get_embedding(text: str) -> list[float]:
    """调用 ollama 获取文本的 embedding 向量"""
    resp = requests.post(
        "http://localhost:11434/api/embeddings",
        json={"model": "nomic-embed-text", "prompt": text},
        timeout=30
    )
    resp.raise_for_status()
    return resp.json()["embedding"]


# ══════════════════════════════════════════════════════════
# 第一步：创建 Chroma 集合
# ══════════════════════════════════════════════════════════

print("=" * 60)
print("第一步：创建 Chroma 集合")
print("=" * 60)

chroma_path = os.path.join(os.path.dirname(__file__), "..", "chroma_data")
client = chromadb.PersistentClient(path=chroma_path)

# 如果已存在则先删除（方便重复运行）
try:
    client.delete_collection("knowledge_base")
except Exception:
    pass  # 集合不存在时忽略，首次运行正常

collection = client.get_or_create_collection(
    name="knowledge_base",
    metadata={"description": "RAG 知识库"}
)

print(f"  集合名称: {collection.name}")
print(f"  当前文档数: {collection.count()}")


# ══════════════════════════════════════════════════════════
# 第二步：加载分片 → 向量化 → 插入
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第二步：向量化 + 插入")
print("=" * 60)

knowledge_dir = os.path.join(os.path.dirname(__file__), "..", "data", "knowledge")
docs = load_all_docs(knowledge_dir)
chunks = chunk_all_docs(docs, chunk_size=500, chunk_overlap=50)

print(f"  加载 {len(docs)} 个文档，分片 {len(chunks)} 条")

# 批量向量化并插入
ids = []
embeddings = []
documents = []
metadatas = []

for i, chunk in enumerate(chunks):
    embedding = get_embedding(chunk["content"])
    ids.append(f"chunk_{i}")
    embeddings.append(embedding)
    documents.append(chunk["content"])
    metadatas.append({
        "file": chunk["file"],
        "chunk_index": chunk["chunk_index"],
    })
    if (i + 1) % 5 == 0:
        print(f"  已向量化 {i + 1}/{len(chunks)} 条")

# 批量插入
collection.add(
    ids=ids,
    embeddings=embeddings,
    documents=documents,
    metadatas=metadatas,
)

print(f"  插入完成，共 {collection.count()} 条")


# ══════════════════════════════════════════════════════════
# 第三步：语义检索
# ══════════════════════════════════════════════════════════

def search(query: str, top_k: int = 3) -> list[dict]:
    """语义检索：输入自然语言，返回 Top-K 相关片段"""
    query_embedding = get_embedding(query)
    results = collection.query(
        query_embeddings=[query_embedding],
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


# ══════════════════════════════════════════════════════════
# 第四步：验证检索效果
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第三步：验证检索效果")
print("=" * 60)

queries = [
    "Python 变量命名规范",
    "API 接口如何设计",
    "如何防止 SQL 注入",
    "Git 提交规范",
    "错误处理怎么做",
]

for q in queries:
    print(f"\n  查询: {q}")
    results = search(q, top_k=2)
    for r in results:
        preview = r["content"][:80].replace("\n", " ")
        print(f"    [{r['file']}] 距离={r['distance']:.3f} | {preview}...")

print("\n验证：返回的片段是否和查询相关？相关度高说明检索跑通了。")
