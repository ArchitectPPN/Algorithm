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

### 距离阈值过滤

`top_k` 会强制返回 N 条结果，即使某些结果完全不相关。实际使用必须加**距离阈值**：

```python
def search(query: str, top_k: int = 3, max_distance: float = 350.0) -> list[dict]:
    """语义检索，距离超过阈值的结果会被丢弃"""
    query_embedding = get_embedding(query)
    results = collection.query(
        query_embeddings=[query_embedding],
        n_results=top_k,
        include=["documents", "metadatas", "distances"]
    )

    output = []
    for i in range(len(results["ids"][0])):
        distance = results["distances"][0][i]
        if distance > max_distance:  # 距离太大 → 不相关，丢弃
            continue
        output.append({
            "content": results["documents"][0][i],
            "file": results["metadatas"][0][i]["file"],
            "distance": distance,
        })
    return output
```

**距离含义：** Chroma 默认用 L2 距离（欧几里得距离），距离越小越相关，越大越不相关。

> ⚠️ L2 距离**没有固定上界**，阈值取决于 embedding 模型和文本领域，换模型或换文档就不同。

### 如何确定阈值？

L2 距离没有固定上界，所以"350 这个数"不是拍脑袋定的，业界有三种系统化方法：

#### 方法一：用余弦相似度代替 L2（推荐）

L2 距离没有固定范围，但**余弦相似度**有：固定在 [-1, 1]，阈值天然好定。

| 余弦相似度 | 含义 |
| --- | --- |
| 1.0 | 完全相同 |
| 0.8–1.0 | 高度相关 |
| 0.5–0.8 | 有一定相关性 |
| < 0.5 | 基本不相关 |

Chroma 支持在创建集合时切换距离度量：

```python
collection = client.get_or_create_collection(
    name="knowledge_base",
    metadata={"hnsw:space": "cosine"}  # 切换为余弦距离
)
# 余弦距离 = 1 - 余弦相似度，范围 [0, 2]
# 0 = 完全相同，> 0.5 就可以过滤
```

**为什么推荐？** 余弦相似度只看方向不看大小，对文本长度不敏感。两段意思相同但长度不同的文本，L2 距离可能差很远，但余弦相似度很接近。文本检索场景下余弦通常比 L2 更稳定。

#### 方法二：Elbow Method 找拐点（无需标注数据）

不需要提前知道"正确答案"，只需要对一次查询的候选结果按距离排序，找"急剧上升"的拐点：

```
距离排序：210, 228, 232, 315, 327, 329, 363, 390
                                    ↑ 拐点
```

- 前面 210→228→232 增长缓慢 → 都是相关结果
- 329→363 突然跳了一大截 → 从"相关"变成"不相关"的分界
- 阈值定在 330–360 之间

**原理：** 相关文档之间的距离通常比较接近（因为语义相似），不相关文档的距离会突然拉开。拐点就是"相关"和"不相关"的自然分界。

**代码实现：**

```python
def find_elbow(distances: list[float]) -> float:
    """用 Elbow Method 找距离拐点，返回建议阈值"""
    if len(distances) < 3:
        return distances[-1] if distances else float('inf')

    sorted_d = sorted(distances)
    # 计算相邻距离的差值（一阶导数）
    diffs = [sorted_d[i+1] - sorted_d[i] for i in range(len(sorted_d)-1)]
    # 找差值最大的位置 → 拐点
    max_diff_idx = diffs.index(max(diffs))
    # 阈值 = 拐点处两个距离的中间值
    return (sorted_d[max_diff_idx] + sorted_d[max_diff_idx + 1]) / 2

# 使用：先多取一些候选，再找拐点
results = collection.query(
    query_embeddings=[get_embedding("Git 提交规范")],
    n_results=10,
    include=["distances"]
)
threshold = find_elbow(results["distances"][0])
print(f"建议阈值: {threshold:.1f}")
```

#### 方法三：已知答案校准法

最直接的方法——用你**提前知道答案**的查询来校准：

1. 准备一批查询，每个查询你提前知道应该命中哪个文档
2. 跑检索，记录相关结果的距离和不相关结果的距离
3. 找到两者的分界点，就是你的阈值

例如我们的实际数据：

| 查询 | 相关结果距离 | 不相关结果距离 |
| --- | --- | --- |
| Python 变量命名规范 | 210 | 329 |
| API 接口如何设计 | 232 | 300 |
| 如何防止 SQL 注入 | 209 | 363 |
| Git 提交规范 | 228 | 390 |

相关结果距离 209–232，不相关 300–390，分界线大约在 350 → 阈值设 350。

**局限：** 需要人工标注"正确答案"，数据量大时成本高。适合知识库规模较小（几十到几百篇文档）的初期校准。

### 实际项目的做法：两层过滤

大多数 RAG 项目不会只靠一个固定阈值，而是组合使用：

```text
检索结果 → 距离/相似度阈值（粗筛）→ LLM 判断相关性（精筛）
```

- **第一层（粗筛）**：余弦相似度 > 0.5 才进入候选（过滤明显不相关的，成本低、速度快）
- **第二层（精筛）**：把候选片段交给 LLM，让 LLM 判断"这个片段是否和问题相关"（成本高但准确）

第二层虽然多一次 LLM 调用，但准确率远高于纯阈值过滤——LLM 能理解语义，而距离只是数学近似。实际项目中，粗筛能把候选从 1000 条压到 5–10 条，精筛再从 5–10 条中挑出真正相关的 2–3 条，兼顾效率和准确率。

---

## 第六步：L2 vs 余弦相似度对比实验（15min）

Chroma 支持两种距离度量，创建集合时通过 `metadata={"hnsw:space": "..."}` 指定：

```python
# L2 距离（默认）
col_l2 = client.get_or_create_collection(name="kb_l2", metadata={"hnsw:space": "l2"})

# 余弦距离
col_cosine = client.get_or_create_collection(name="kb_cosine", metadata={"hnsw:space": "cosine"})
```

### 两种度量的数学含义

| | L2 距离（欧几里得距离） | 余弦相似度 |
| --- | --- | --- |
| **公式** | √(Σ(aᵢ - bᵢ)²) | (A·B) / (|A|×|B|) |
| **直觉** | 空间中两点的直线距离 | 两个向量方向的夹角 |
| **范围** | [0, +∞) 无上界 | [-1, 1] 有固定范围 |
| **受向量长度影响** | ✅ 受影响 | ❌ 不受影响 |
| **Chroma 返回值** | L2 距离（越小越相关） | 余弦距离 = 1 - 余弦相似度（越小越相关） |

### 对比实验代码

用同样的数据插入两个集合，同样的查询分别检索，对比结果：

```python
queries = [
    "Python 变量命名规范",
    "API 接口如何设计",
    "如何防止 SQL 注入",
    "Git 提交规范",
    "错误处理怎么做",
]

for q in queries:
    query_embedding = get_embedding(q)

    # L2 检索
    l2_results = col_l2.query(
        query_embeddings=[query_embedding],
        n_results=5,
        include=["documents", "metadatas", "distances"]
    )

    # 余弦检索
    cosine_results = col_cosine.query(
        query_embeddings=[query_embedding],
        n_results=5,
        include=["documents", "metadatas", "distances"]
    )

    print(f"\n  查询: {q}")
    print(f"  {'排名':>4}  {'L2距离':>10}  {'余弦距离':>10}  {'余弦相似度':>12}  {'文件':>25}  相关?")
    for i in range(5):
        l2_dist = l2_results["distances"][0][i]
        cos_dist = cosine_results["distances"][0][i]
        cos_sim = 1 - cos_dist
        file = l2_results["metadatas"][0][i]["file"]
        is_relevant = "✅" if cos_sim > 0.5 else "❌"
        print(f"  {i+1:>4}  {l2_dist:>10.3f}  {cos_dist:>10.4f}  {cos_sim:>12.4f}  {file:>25}  {is_relevant}")
```

### 阈值过滤对比

```python
# L2 阈值过滤（阈值需要校准，没有通用值）
def search_l2(query, top_k=3, max_distance=350.0):
    results = col_l2.query(...)
    return [r for r in results if r["distance"] <= max_distance]

# 余弦阈值过滤（0.5 是通用经验值）
def search_cosine(query, top_k=3, min_similarity=0.5):
    results = col_cosine.query(...)
    return [r for r in results if (1 - r["distance"]) >= min_similarity]
```

### 实验预期结论

1. **排序基本一致**：两种度量返回的 Top-1 文件几乎相同，因为语义最相关的片段在两种度量下都排第一
2. **阈值可操作性不同**：余弦相似度 0.5 是通用经验值，L2 的 350 需要针对数据校准
3. **边界情况有差异**：当片段长度差异大时，L2 可能受向量长度影响导致排序偏差

### 优劣对比与使用场景

| 维度 | L2 距离 | 余弦相似度 |
| --- | --- | --- |
| **优点** | 计算简单直观；对绝对位置敏感，能区分"近且方向相同"和"远但方向相同" | 对文本长度不敏感；有固定范围，阈值好定；文本检索场景更稳定 |
| **缺点** | 无固定上界，阈值需校准；受向量长度影响，长文本和短文本的距离不可比 | 只看方向不看大小，可能把"方向相同但距离很远"的也判为相似 |
| **适合场景** | 向量长度统一（如归一化后的 embedding）；需要区分"距离近"和"方向同" | 文本检索（RAG）；片段长度不统一；需要通用阈值 |
| **不适合场景** | 片段长度差异大；需要跨项目复用阈值 | 需要区分"方向相同但距离远"的边界情况 |

**RAG 场景推荐余弦相似度**，原因：

1. 知识库片段长度通常不统一（有的 100 字，有的 500 字），L2 会受长度影响
2. 余弦相似度有固定范围，0.5 可以作为起步阈值，跨项目可复用
3. 文本检索关注的是"语义方向是否一致"，而不是"向量绝对距离"

> 💡 如果 embedding 模型输出的向量已经归一化（如 OpenAI 的 text-embedding-ada-002），L2 和余弦的排序结果完全等价，选哪个都一样。nomic-embed-text 也是归一化的，所以实验中两种度量的排序会基本一致，差异主要体现在阈值的可操作性上。

### 其他距离度量（了解即可）

除了 L2 和余弦，还有几种距离度量，但初学者不需要深入：

| 度量 | 原理 | 用在哪 | 初学者需要学吗 |
| --- | --- | --- | --- |
| **内积（IP / Dot Product）** | A·B = Σ(aᵢ×bᵢ)，同时考虑方向和长度 | 向量已归一化时等价于余弦；推荐系统 | ❌ 归一化向量下等价余弦 |
| **汉明距离（Hamming）** | 两个二进制串有多少位不同 | 二值向量检索（LSH），文本场景几乎不用 | ❌ Chroma 不支持 |
| **Jaccard 距离** | 1 - (交集/并集)，衡量集合差异 | 短文本去重、关键词匹配 | ❌ 不基于 embedding，和 RAG 流程无关 |

**为什么只关注余弦和 L2：**

1. Chroma 只支持 3 种度量：l2、cosine、ip，汉明和 Jaccard 根本不支持
2. 内积在归一化向量下等价于余弦，nomic-embed-text 输出的就是归一化向量，没必要单独学
3. 汉明和 Jaccard 不基于 embedding，属于传统信息检索方法，和 RAG 的向量检索流程无关

**初学者结论：RAG 项目用余弦相似度就够了，L2 知道区别就行，其他的不用管。**

---

## 第七步：浏览数据库内容（5min）

和 MySQL 有 Navicat 类似，Chroma 也有查看数据的方式。

### 代码查看（最直接）

| MySQL 操作 | Chroma 对应 |
| --- | --- |
| `SHOW TABLES` | `client.list_collections()` |
| `SELECT COUNT(*) FROM table` | `collection.count()` |
| `SELECT * FROM table LIMIT 5` | `collection.peek(limit=5)` |
| `SELECT * FROM table WHERE file='xxx'` | `collection.get(where={"file": "xxx"})` |
| `GROUP BY file` | `collection.get(include=["metadatas"])` + Python 计数 |

```python
# 查看所有集合
# list_collections() 返回 Collection 对象，直接用 .name 和 .count()
for col in client.list_collections():
    print(f"  - {col.name} ({col.count()} 条记录)")

# 查看前 5 条（类似 SELECT * LIMIT 5）
peek = collection.peek(limit=5)
for i in range(len(peek["ids"])):
    preview = peek["documents"][i][:60].replace("\n", " ")
    meta = peek["metadatas"][i]
    print(f"  [{peek['ids'][i]}] {meta['file']} #{meta['chunk_index']} | {preview}...")

# 按文件统计（类似 GROUP BY file）
all_data = collection.get(include=["metadatas"])
file_counts = {}
for meta in all_data["metadatas"]:
    f = meta["file"]
    file_counts[f] = file_counts.get(f, 0) + 1
for f, count in sorted(file_counts.items()):
    print(f"  {f}: {count} 条片段")

# 条件查询（类似 WHERE file = 'xxx'）
filtered = collection.get(
    where={"file": "sql-best-practices.md"},
    include=["documents", "metadatas"]
)
```

### 可视化工具

和 MySQL 有 Navicat/DBeaver 类似，向量数据库也有可视化工具：

| 工具 | 说明 |
| --- | --- |
| **Chroma 自带 UI** | `chroma run --host 0.0.0.0 --port 8000` 启动后浏览器访问 `http://localhost:8000`，能看到集合、文档数、元数据 |
| **Attu** | Milvus 的官方 GUI，也支持 Chroma，功能更丰富 |
| **Chroma Inspector** | 专门给 Chroma 的轻量 Web UI，`pip install chroma-inspector` |

> ⚠️ 向量数据库的可视化比关系型数据库难做——768 维的向量没法像表格一样直观展示，通常只能看元数据和文本内容，向量本身需要降维后用散点图展示。

---

## 检验标准

1. **默写**：能写出 `collection.add()` + `collection.query()` 的核心用法
2. **一句话**："Chroma 把向量存起来，查询时用 ANN 算法快速找最相似的 N 个"
3. **追问**：Chroma 和 FAISS 有什么区别？（Chroma 轻量自带持久化，FAISS 纯算法库需要自己管理存储）

---

## 产出文件

`rag/chroma_demo.py`