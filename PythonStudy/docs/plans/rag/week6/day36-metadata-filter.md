# Day 36：元数据过滤（where 条件检索）

> 目标：学会用 Chroma 的元数据过滤，实现"按文件/类别/时间筛选后再检索"。

---

## 学习路线（约 60-90 分钟）

```
概念（10min）→ where 基础语法（20min）→ 组合过滤（20min）→ 检索+过滤结合（20min）
```

---

## 第一步：理解元数据（10min）

Chroma 每条记录除了向量 + 文本，还能挂**元数据**（metadata），类似 MySQL 的字段：

```
id: chunk_0
document: "永远使用参数化查询，禁止拼接 SQL 字符串"
embedding: [0.12, -0.03, ...]（768 维）
metadata: {
    "file": "sql-best-practices.md",   ← 来源文件
    "chunk_index": 0,                   ← 片段序号
    "category": "数据库",               ← 类别（可加）
    "author": "团队规范"                ← 作者（可加）
}
```

**核心价值：** 检索前先用 metadata 缩小范围，避免"全库搜"。比如知识库有 100 篇文档，用户只关心"数据库规范"，先 `where file 包含 sql` 过滤，再检索。

---

## 第二步：where 基础语法（20min）

```python
import chromadb

client = chromadb.PersistentClient(path="./chroma_data")
collection = client.get_or_create_collection("knowledge_base")

# 精确匹配（类似 WHERE category = '数据库'）
results = collection.get(
    where={"category": "数据库"},
    include=["documents", "metadatas"]
)
print(f"命中 {len(results['ids'])} 条")

# 不等（类似 WHERE category != '数据库'）
results = collection.get(
    where={"category": {"$ne": "数据库"}}
)

# 范围（类似 WHERE chunk_index > 5 AND chunk_index < 10）
# ⚠️ 注意：Chroma 不支持同一字段多个运算符写在一起，必须用 $and 组合
results = collection.get(
    where={"$and": [
        {"chunk_index": {"$gt": 5}},
        {"chunk_index": {"$lt": 10}},
    ]}
)
```

### 支持的运算符

| 运算符 | 含义 | 类似 SQL |
|--------|------|---------|
| `$eq` | 等于 | `=` |
| `$ne` | 不等于 | `!=` |
| `$gt` / `$gte` | 大于 / 大于等于 | `>` / `>=` |
| `$lt` / `$lte` | 小于 / 小于等于 | `<` / `<=` |
| `$in` | 在列表中 | `IN (...)` |
| `$nin` | 不在列表中 | `NOT IN (...)` |

---

## 第三步：组合过滤（20min）

```python
# AND 组合（类似 WHERE a=1 AND b=2）
results = collection.get(
    where={"$and": [
        {"category": "数据库"},
        {"chunk_index": {"$lt": 5}},
    ]}
)

# OR 组合（类似 WHERE a=1 OR b=2）
results = collection.get(
    where={"$or": [
        {"category": "数据库"},
        {"category": "安全"},
    ]}
)

# 文档内容包含（类似 WHERE content LIKE '%sql%'）
# ⚠️ 注意：$contains 只对文档内容(where_document)和数组元数据有效，
#    对标量字符串元数据（如 file）做子串匹配会静默返回空结果！
results = collection.get(
    where_document={"$contains": "sql"}
)
```

---

## 第四步：检索 + 过滤结合（20min）

真实场景：检索时同时指定 where，只在符合条件的结果里找相似。

```python
def search_with_filter(query: str, category: str, top_k: int = 3):
    """按类别过滤后检索"""
    query_vec = get_embedding(query)
    results = collection.query(
        query_embeddings=[query_vec],
        n_results=top_k,
        where={"category": category},  # 先过滤，再相似检索
        include=["documents", "metadatas", "distances"]
    )
    return results

# 测试：只在"数据库"类别中检索 SQL 注入
hits = search_with_filter("如何防止注入攻击", category="数据库", top_k=2)
for i in range(len(hits["ids"][0])):
    print(f"[{hits['metadatas'][0][i]['file']}] 距离={hits['distances'][0][i]:.3f}")
    print(f"  {hits['documents'][0][i][:80]}...")
```

**注意：** 如果过滤条件太严（如 category 根本不存在），会返回空结果。此时应该降级为"去掉过滤条件重试"。

---

## 实验任务

1. 给知识库的 metadata 加上 `category` 字段（每个文件对应一个类别）
2. 实现 3 种过滤检索：精确匹配、`$in` 列表、`$and` 组合
3. 对比"全库检索"和"过滤后检索"的结果差异
4. 测试边界：过滤条件无匹配时返回什么？

---

## 检验标准

1. **默写**：`collection.query(where={"category": "数据库"})` 的写法
2. **一句话**："where 让检索先在元数据上缩小范围，再做相似度匹配，又快又准"
3. **追问**：过滤条件太严返回空结果怎么办？（降级为全库检索 / 放宽条件）

---

## 产出文件

`rag/query_demo.py` — 元数据过滤检索 demo
