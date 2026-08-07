"""
Day33：向量数据库 Chroma

功能：
1. 创建 Chroma 集合（L2 + 余弦两种距离度量）
2. 加载分片 → 向量化 → 插入
3. 语义检索 Top-K + 距离阈值过滤
4. L2 vs 余弦相似度对比实验
5. 浏览数据库内容
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
# 第一步：创建两个集合（L2 + 余弦）
# ══════════════════════════════════════════════════════════

print("=" * 60)
print("第一步：创建 Chroma 集合（L2 vs 余弦）")
print("=" * 60)

chroma_path = os.path.join(os.path.dirname(__file__), "..", "chroma_data")
client = chromadb.PersistentClient(path=chroma_path)

# 清除旧集合（方便重复运行）
for name in ["knowledge_base_l2", "knowledge_base_cosine"]:
    try:
        client.delete_collection(name)
    except Exception:
        pass

# L2 距离集合（Chroma 默认）
col_l2 = client.get_or_create_collection(
    name="knowledge_base_l2",
    metadata={"hnsw:space": "l2"}  # 默认值，可省略
)

# 余弦距离集合
col_cosine = client.get_or_create_collection(
    name="knowledge_base_cosine",
    metadata={"hnsw:space": "cosine"}
)

print(f"  L2 集合: {col_l2.name}")
print(f"  余弦集合: {col_cosine.name}")


# ══════════════════════════════════════════════════════════
# 第二步：加载分片 → 向量化 → 插入（两个集合都插入）
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第二步：向量化 + 插入")
print("=" * 60)

knowledge_dir = os.path.join(os.path.dirname(__file__), "..", "data", "knowledge")
docs = load_all_docs(knowledge_dir)
chunks = chunk_all_docs(docs, chunk_size=500, chunk_overlap=50)

print(f"  加载 {len(docs)} 个文档，分片 {len(chunks)} 条")

# 批量向量化
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

# 插入两个集合（同样的数据）
col_l2.add(ids=ids, embeddings=embeddings, documents=documents, metadatas=metadatas)
col_cosine.add(ids=ids, embeddings=embeddings, documents=documents, metadatas=metadatas)

print(f"  插入完成：L2 {col_l2.count()} 条，余弦 {col_cosine.count()} 条")


# ══════════════════════════════════════════════════════════
# 第三步：L2 vs 余弦 对比实验
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第三步：L2 vs 余弦 对比实验")
print("=" * 60)

queries = [
    "Python 变量命名规范",
    "API 接口如何设计",
    "如何防止 SQL 注入",
    "Git 提交规范",
    "错误处理怎么做",
]

# 对每个查询，分别在两个集合中检索，对比结果
for q in queries:
    query_embedding = get_embedding(q)

    # L2 检索
    l2_results = col_l2.query(
        query_embeddings=[query_embedding],
        n_results=5,  # 多取一些，方便对比
        include=["documents", "metadatas", "distances"]
    )

    # 余弦检索
    cosine_results = col_cosine.query(
        query_embeddings=[query_embedding],
        n_results=5,
        include=["documents", "metadatas", "distances"]
    )

    print(f"\n  查询: {q}")
    print(f"  {'排名':>4}  {'L2 距离':>10}  {'余弦距离':>10}  {'余弦相似度':>12}  {'文件':>25}  是否相关")
    print(f"  {'-'*4}  {'-'*10}  {'-'*10}  {'-'*12}  {'-'*25}  {'-'*6}")

    for i in range(5):
        l2_dist = l2_results["distances"][0][i]
        cos_dist = cosine_results["distances"][0][i]
        cos_sim = 1 - cos_dist  # 余弦相似度 = 1 - 余弦距离
        file = l2_results["metadatas"][0][i]["file"]

        # 简单判断是否相关：L2 < 350 或 余弦相似度 > 0.5
        is_relevant = "✅" if (l2_dist < 350 or cos_sim > 0.5) else "❌"

        print(f"  {i+1:>4}  {l2_dist:>10.3f}  {cos_dist:>10.4f}  {cos_sim:>12.4f}  {file:>25}  {is_relevant}")


# ══════════════════════════════════════════════════════════
# 第四步：阈值过滤对比
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第四步：阈值过滤对比")
print("=" * 60)

def search_l2(query: str, top_k: int = 3, max_distance: float = 350.0) -> list[dict]:
    """L2 距离检索 + 阈值过滤"""
    query_embedding = get_embedding(query)
    results = col_l2.query(
        query_embeddings=[query_embedding],
        n_results=top_k,
        include=["documents", "metadatas", "distances"]
    )
    output = []
    for i in range(len(results["ids"][0])):
        distance = results["distances"][0][i]
        if distance > max_distance:
            continue
        output.append({
            "content": results["documents"][0][i],
            "file": results["metadatas"][0][i]["file"],
            "distance": distance,
        })
    return output


def search_cosine(query: str, top_k: int = 3, min_similarity: float = 0.5) -> list[dict]:
    """余弦相似度检索 + 阈值过滤"""
    query_embedding = get_embedding(query)
    results = col_cosine.query(
        query_embeddings=[query_embedding],
        n_results=top_k,
        include=["documents", "metadatas", "distances"]
    )
    output = []
    for i in range(len(results["ids"][0])):
        cos_dist = results["distances"][0][i]
        cos_sim = 1 - cos_dist  # 转换为相似度
        if cos_sim < min_similarity:  # 相似度太低 → 不相关，丢弃
            continue
        output.append({
            "content": results["documents"][0][i],
            "file": results["metadatas"][0][i]["file"],
            "distance": cos_dist,
            "similarity": cos_sim,
        })
    return output


# 对比两种检索的过滤结果
print("\n  L2 阈值=350 vs 余弦相似度阈值=0.5")
print(f"  {'查询':>20}  {'L2 结果数':>10}  {'余弦结果数':>10}  {'结果一致':>8}")
print(f"  {'-'*20}  {'-'*10}  {'-'*10}  {'-'*8}")

for q in queries:
    l2_hits = search_l2(q, top_k=3, max_distance=350.0)
    cos_hits = search_cosine(q, top_k=3, min_similarity=0.5)

    # 检查返回的文件是否一致
    l2_files = set(r["file"] for r in l2_hits)
    cos_files = set(r["file"] for r in cos_hits)
    match = "✅" if l2_files == cos_files else "⚠️"

    print(f"  {q:>20}  {len(l2_hits):>10}  {len(cos_hits):>10}  {match:>8}")

print("\n  结论：")
print("  - 两种度量返回的文件基本一致（语义排序相同）")
print("  - 但余弦相似度有固定范围 [0,1]，阈值 0.5 是通用经验值")
print("  - L2 距离没有固定上界，阈值需要针对数据校准")


# ══════════════════════════════════════════════════════════
# 第五步：浏览数据库内容
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第五步：浏览数据库内容")
print("=" * 60)

# 查看所有集合
print("\n  所有集合:")
for col in client.list_collections():
    print(f"    - {col.name} ({col.count()} 条记录)")

# 查看前 5 条
print(f"\n  knowledge_base_cosine 前 5 条记录:")
peek = col_cosine.peek(limit=5)
for i in range(len(peek["ids"])):
    preview = peek["documents"][i][:60].replace("\n", " ")
    meta = peek["metadatas"][i]
    print(f"    [{peek['ids'][i]}] {meta['file']} #{meta['chunk_index']} | {preview}...")

# 按文件统计
print(f"\n  按文件统计:")
all_data = col_cosine.get(include=["metadatas"])
file_counts = {}
for meta in all_data["metadatas"]:
    f = meta["file"]
    file_counts[f] = file_counts.get(f, 0) + 1
for f, count in sorted(file_counts.items()):
    print(f"    {f}: {count} 条片段")

# 条件查询
print(f"\n  条件查询：只看 sql-best-practices.md 的片段:")
filtered = col_cosine.get(
    where={"file": "sql-best-practices.md"},
    include=["documents", "metadatas"]
)
for i in range(len(filtered["ids"])):
    preview = filtered["documents"][i][:80].replace("\n", " ")
    print(f"    [{filtered['ids'][i]}] | {preview}...")
