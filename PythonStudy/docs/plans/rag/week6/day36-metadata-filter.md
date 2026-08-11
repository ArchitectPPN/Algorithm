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

## 实战踩坑（跑通 query_demo.py 后记录）

### 坑 1：`dict | None` 注解在 Python 3.9 报 TypeError

**现象**：`def search_with_filter(query: str, where: dict | None = None)` 直接报
`TypeError: unsupported operand type(s) for |: 'type' and 'NoneType'`，文件都跑不起来。

**根因**：`dict | None` 是 Python **3.10+** 的联合类型写法，3.9 的 `dict` 类型不支持 `|` 运算。而函数定义时会先求值注解，所以一执行到 `def` 就炸（不是调用时才炸）。

**解决**：文件顶部加一行 `from __future__ import annotations`——让所有类型注解变成惰性字符串，3.9 也能用 3.10 的注解写法，**一个文件以后都不用管这问题**。
```python
from __future__ import annotations   # 必须放文件最顶部（其他 import 之前）
```
> 💡 同类报错通用解法：要么加这行 future import，要么把 `dict | None` 改成 `Optional[dict]`。

### 坑 2：全库检索的 top1 不一定是目标类别

**现象**：查「如何防止注入攻击」，全库检索 top1 是 `error-handling.md#2`（距离 0.239），而不是 `sql-best-practices.md`（0.419）。错误处理片段语义上和"注入"很近，抢了第一名。

**根因**：error-handling.md#2 大概率也提到了注入/安全相关内容，语义重合度高。纯向量检索**只看语义，不看类别**。

**结论**：这正是**元数据过滤的价值**——全库检索会把语义相近但类别不对的片段排前面；加了 `where={"category":"数据库"}` 后，噪音直接被过滤，只返回真正相关的 sql 片段。**知识库越大，过滤价值越明显。**

---

## FAQ：include 参数返回什么？还有哪些可选？

**问**：`include=["documents", "metadatas", "distances"]` 是返回的数据类型吗？还有别的吗？

**答**：对，就是"告诉 Chroma 这次查询要返回哪些字段"。Chroma 存了 4 样（ids/embeddings/documents/metadatas），加上查询时算出来的 distances，**默认不全返回**——include 什么才返回什么，省传输和内存。

完整可选值 **5 个**：

| include 值 | 返回什么 | 备注 |
|-----------|---------|------|
| `"ids"` | 片段 ID（`chunk_0`…） | **总是返回**，不写也有 |
| `"documents"` | 原文文本 | 给 LLM 看的内容 |
| `"metadatas"` | 元数据（file/category…） | 来源信息 |
| `"distances"` | 距离（余弦距离 = 1-相似度） | **只有 `query()` 有**，`get()` 没有 |
| `"embeddings"` | 向量本身（768 维 float） | 很少用，一般不取向量 |

两个要点：
1. **`ids` 永远返回**——所以代码里拿 `results["ids"]` 当主轴遍历，总有值。
2. **`distances` 只有 `query()`（相似检索）有，`get()`（条件取）没有**——get 不算相似度，没距离概念。所以 demo 里 `get` 的调用都没 include distances，`query` 的才有。

```python
# query：检索相似片段，有距离
collection.query(query_embeddings=[vec], n_results=3,
                 include=["documents", "metadatas", "distances"])
# get：按条件取，不算相似度，没距离
collection.get(where={"category": "数据库"},
               include=["documents", "metadatas"])
```

---

## FAQ：插入的元数据，返回时结构怎么不一样了？

**问**：插入时元数据是 file/chunk_index/category，返回时"不一样"——这是怎么规定的？

**答**：分清两件事——**字段**和**结构**。

**① 字段：插啥返啥，Chroma 不规定字段名**

你插入 `{"file":..., "chunk_index":..., "category":...}`，返回还是这三个字段，一一对应。字段名你自己定，Chroma 只负责存和返。

**② 结构：query 返回多包一层 `[0]`，get 是平铺的**

```python
# 插入时（平铺列表）
metadatas = [
    {"file": "a.md", "chunk_index": 0, "category": "数据库"},
    {"file": "a.md", "chunk_index": 1, "category": "数据库"},
]

# query 返回时（多包一层 [0]）
results["metadatas"] = [
    [                                              # ← [0] 这一层
        {"file": "a.md", "chunk_index": 0, ...},
        {"file": "a.md", "chunk_index": 1, ...},
    ]
]
```

**为什么多一层 `[0]`？** 因为 `query` 支持**批量查询**——一次传多个查询向量，每个查询各返回一组结果。最外层是"第几个查询"，`[0]` 就是"第一个（通常也是唯一一个）查询的结果"。你只传一个查询向量，所以永远取 `[0]`。

```python
for i in range(len(results["ids"][0])):     # results["ids"][0] = 第1个查询的ID列表
    meta = results["metadatas"][0][i]       # 第1个查询的第i条元数据
```

而 `get` 不存在批量问题，返回就是平铺的，不用 `[0]`。这也是 `print_results` 里要判断 `is_query` 的原因。

**一句话**：字段你自己定（插啥返啥），返回的嵌套结构是 Chroma 规定的——**query 多包一层 `[0]`（批量查询接口），get 平铺**。

---

## FAQ：返回的元数据字段顺序怎么变了？

**问**：插入时写 `{"file":..., "chunk_index":..., "category":...}`，返回时顺序变了，有关系吗？

**答**：**没关系，别被顺序带偏。**

**原因**：Chroma 内部用字典存元数据，**字典是无序的**（存储/序列化过程中不保证顺序）。所以写进去的顺序和取出来的顺序**没有任何关系**。

**为什么无所谓**：因为取字段用的是**键名**，不是位置：
```python
meta = {"category": "数据库", "file": "sql.md", "chunk_index": 0}  # 顺序变了
meta["category"]      # "数据库" ✅  用键名取，顺序怎么变都对
meta["file"]          # "sql.md" ✅
meta["chunk_index"]   # 0 ✅
```
对比**顺序才重要**的场景——列表按位置取：
```python
row = ["数据库", "sql.md", 0]   # 列表：靠位置取
row[0]   # 必须记住 0 是 category，顺序变就全错
```

**这就是元数据用 dict 而不用 list 存的原因**——字段名自带含义，不依赖位置。

> 💡 判断标准：以后看到"字典字段顺序变了"都别慌——只要用 `dict["key"]` 取值（不是 `dict[0]`），顺序就和你无关。只有列表/元组按位置取值时，顺序才要命。

---

## 检验标准

1. **默写**：`collection.query(where={"category": "数据库"})` 的写法
2. **一句话**："where 让检索先在元数据上缩小范围，再做相似度匹配，又快又准"
3. **追问**：过滤条件太严返回空结果怎么办？（降级为全库检索 / 放宽条件）

---

## 产出文件

`rag/query_demo.py` — 元数据过滤检索 demo
