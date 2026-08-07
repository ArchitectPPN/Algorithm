# Day 33：向量数据库 Chroma

> 目标：安装 Chroma，把分片后的文本向量化并存入，实现语义检索。

---

## 学习路线（约 60 分钟）

```
概念（10min）→ 安装 + 创建集合（10min）→ 向量化 + 插入（20min）→ 检索（15min）→ 验证（5min）
```

---

## 第一步：理解向量数据库（10min）

### 和传统数据库的区别

| | MySQL / SQLite | Chroma |
|---|---|---|
| 存什么 | 文本、数字 | 向量（float 数组） |
| 怎么查 | `WHERE name = '张三'` | 给一个向量，找最相似的 N 个 |
| 查什么 | 精确匹配 | 语义相似 |
| 索引方式 | B-Tree | ANN（近似最近邻） |

### 为什么需要向量数据库？

知识库有 1000 个片段，每次查询：
- 不用向量库：遍历 1000 个片段，逐个算余弦相似度 → O(n)
- 用 Chroma：HNSW 索引，秒出结果 → O(log n)

---

## 第二步：安装 + 创建集合（10min）

```bash
pip install chromadb
```

```python
import chromadb

# 创建客户端（本地模式，数据存在当前目录）
client = chromadb.PersistentClient(path="./chroma_data")

# 创建或获取集合
collection = client.get_or_create_collection(
    name="knowledge_base",
    metadata={"description": "我的知识库"}
)

print(f"集合名称: {collection.name}")
print(f"当前文档数: {collection.count()}")
```

---

## 第三步：向量化 + 插入（20min）

用 Day 30 的 `get_embedding()` 和 Day 32 的分片结果：

```python
import requests

def get_embedding(text: str) -> list[float]:
    resp = requests.post(
        "http://localhost:11434/api/embeddings",
        json={"model": "nomic-embed-text", "prompt": text}
    )
    return resp.json()["embedding"]

# 加载之前的分片结果
from document_loader import load_all_docs, chunk_all_docs

docs = load_all_docs("data/knowledge")
chunks = chunk_all_docs(docs, chunk_size=500, chunk_overlap=50)

# 逐条向量化并插入
for i, chunk in enumerate(chunks):
    embedding = get_embedding(chunk["content"])
    collection.add(
        ids=[f"chunk_{i}"],                    # 唯一 ID
        embeddings=[embedding],                 # 向量
        documents=[chunk["content"]],           # 原文
        metadatas=[{                            # 元数据
            "file": chunk["file"],
            "chunk_index": chunk["chunk_index"],
        }]
    )
    if (i + 1) % 10 == 0:
        print(f"已插入 {i + 1}/{len(chunks)} 条")

print(f"插入完成，共 {collection.count()} 条")
```

---

## 第四步：执行检索（15min）

```python
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
            "content": results["documents"][0][i][:200],
            "file": results["metadatas"][0][i]["file"],
            "distance": results["distances"][0][i],
        })
    return output

# 测试
results = search("如何防止 SQL 注入")
for r in results:
    print(f"[{r['file']}] 距离={r['distance']:.3f}")
    print(f"  {r['content']}...")
    print()
```

---

## 第五步：验证（5min）

用 3 个不同查询测试检索效果：

```python
queries = [
    "Python 变量命名规范",
    "API 接口如何设计",
    "数据库查询优化",
]

for q in queries:
    print(f"\n查询: {q}")
    results = search(q, top_k=2)
    for r in results:
        print(f"  [{r['file']}] 距离={r['distance']:.3f}")
```

人工判断：返回的片段是否和查询相关？如果相关度高，说明 RAG 检索部分跑通了。

---

## 检验标准

1. **默写**：能写出 `collection.add()` + `collection.query()` 的核心用法
2. **一句话**："Chroma 把向量存起来，查询时用 ANN 算法快速找最相似的 N 个"
3. **追问**：Chroma 和 FAISS 有什么区别？（Chroma 轻量自带持久化，FAISS 纯算法库需要自己管理存储）

---

## 产出文件

`rag/chroma_demo.py`