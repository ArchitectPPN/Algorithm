# RAG 学习笔记（第 5 周 Day 29-35）

> 一周知识浓缩版。面试前 / 写代码前，先读这一篇。
> 详细踩坑记录见 `docs/plans/rag/week5/day34-pitfalls.md`。

---

## 1. Embedding 原理

**一句话**：文本 → 数字向量（本教程 768 维），语义相近的文本 → 向量距离近。

- 类比：地图上"北京"和"上海"的经纬度比"北京"和"纽约"更近。
- **维度固定，跟字数无关**：9 字的查询和 500 字的片段都是 768 维，在同一个向量空间才能算相似度。
- 相似度来自**语义重合**，不是长度重合。实测：9 字查询「如何防止SQL注入」vs 500 字片段相似度 0.78；无关的「怎么做红烧肉」只有 0.61。
- **黑盒 + 局限**：内部是神经网络，只保证"语义近 → 向量近"，不保证完美：
  - 中文效果一般（nomic-embed-text 是英文模型）
  - 语义会被稀释（500 字片段是"混合语义"）
  - 区分度有限（相关 0.64 / 无关 0.68 会重叠）→ 纯向量判断不可靠，需要 LLM 精筛兜底

---

## 2. 余弦相似度

**公式**：cos(θ) = (A·B) / (|A| × |B|)

- 只看**方向**（夹角），不看模长 → 适合比文本语义。
- 范围 **[-1, 1]**：1 完全一致，0 无关，-1 完全相反。
- **阈值 0.5 可跨项目复用**（范围固定，通用经验值）。
- vs **L2 欧氏距离**：范围 [0, +∞)，受模长影响大，阈值不可复用。

⚠️ 局限：纯余弦阈值有数学天花板，**相关性判断必须靠 LLM 精筛**（见第 5 节）。

```python
def cosine_similarity(a, b):
    dot = sum(x * y for x, y in zip(a, b))
    mag_a = (sum(x * x for x in a)) ** 0.5
    mag_b = (sum(x * x for x in b)) ** 0.5
    return dot / (mag_a * mag_b)
```

---

## 3. 分片策略

**为什么分片**：太长的文本语义被稀释（一条向量装不下整本书的信息），太短则上下文丢失。

- **chunk_size = 500**：语义完整 vs 检索精度的平衡
- **chunk_overlap = 50**：片段间重叠，防止关键信息恰好卡在分片边界被切断
- 中文约 1 字符 ≈ 1.5 tokens（token 估算用）

⚠️ 局限：固定长度盲切，可能在**句子中间**切断，把完整语义切碎——这是当前方案的天花板，进阶方案是按标题/段落/句号切。

---

## 4. 向量数据库 Chroma

**与传统数据库的区别**：存的是**向量**，查的是**相似度**（ANN 近似最近邻），不是精确匹配。O(n) 全量遍历 → O(log n)。

**一条记录存 4 样东西**：

| 字段 | 内容 | 作用 |
|------|------|------|
| `ids` | 唯一标识（`chunk_0`） | 定位 / 去重 / 删除 |
| `embeddings` | 768 维向量 | **相似度检索的核心** |
| `documents` | 原文文本 | 检索到后返回给用户 / LLM |
| `metadatas` | 来源信息（文件名、片段号） | 标注来源，可过滤 |

**向量负责"找"，原文负责"用"**——只存向量不存原文，检索到了也不知道对应什么文字。

⚠️ 两个坑：
1. 余弦度量要显式 `metadata={"hnsw:space": "cosine"}`（默认是 l2）
2. **已存在的集合不会应用新度量**——改度量必须 `build_index(rebuild=True)` 删除重建

```python
self.client = chromadb.PersistentClient(path=chroma_path)
self.collection = self.client.get_or_create_collection(
    "knowledge_base",
    metadata={"hnsw:space": "cosine"}
)
results = self.collection.query(
    query_embeddings=[query_vec],
    n_results=top_k,
    include=["documents", "metadatas", "distances"]
)
# 相似度 = 1 - 余弦距离（Chroma 返回的是距离）
similarity = 1 - results["distances"][0][i]
```

---

## 5. RAG 完整流程（三层过滤）

```
用户问题
  │
  ├─ 第1层 粗筛：search()     问题→向量 → Chroma 余弦检索 top_k=5 → 相似度 < 0.5 丢弃
  │     ↓ 候选片段（可能相关，宁松勿紧）
  ├─ 第2层 精筛：rerank()     deepseek-r1:7b 逐条判断 {"related": true/false}，只留 true
  │     ↓ 相关片段
  ├─ [安全闸] 精筛为空 → 拒绝回答，不调用生成
  │
  └─ 第3层 生成：generate()   相关片段作上下文 + 提示词 → LLM 回答
```

**各层对应函数**：`search()`（粗筛，含 `min_similarity=0.5` 阈值）→ `rerank()`（精筛）→ `generate()`（生成），由 `ask()` 编排。

**三处易错**：
1. 精筛模型 `deepseek-r1:7b` 是**推理模型**，先思考再回答 → `num_predict` 必须给足 **400+**，否则思考占满配额返回空
2. **安全闸不能省**：精筛为空 → 直接返回"知识库中没有相关内容"，不生成（防幻觉，检索是上限）
3. 精筛结论用**结构化 JSON** 解析（`{"related": true}`），不用子串判断——`"不存在相关性"` 含"相关"字但语义是否定（Day34 坑 7）

```python
def ask(self, query):
    candidates = self.search(query, top_k=5)        # 第1层 余弦粗筛
    if not candidates:
        return {"answer": "知识库中没有相关内容。"}   # 没有候选
    relevant = self.rerank(query, candidates)       # 第2层 LLM 精筛
    if not relevant:
        return {"answer": "知识库中没有与问题相关的内容。"}  # 安全闸
    answer = self.generate(query, relevant)         # 第3层 生成
    return {"answer": answer}
```

**一句话**："加载 → 分片 → 向量化 → 存库（向量+原文+元数据）→ 查询转向量 → 余弦粗筛（阈值 0.5）→ LLM 精筛（判相关）→ 相关片段为空则拒绝回答 → 否则喂给 LLM 基于片段生成回答"

---

## 6. 实战踩坑速查（Day 34）

| # | 坑 | 一句话解法 |
|---|-----|-----------|
| 1 | 余弦 0.5 阈值挡不住无关查询 | 阈值要校准，根本解法是 LLM 精筛 |
| 2 | 切余弦度量后查询还是 L2 | 已存在的集合不应用新度量，`rebuild=True` 重建 |
| 3 | 推理模型返回空字符串 | `num_predict` 给足 400+（思考占配额） |
| 4 | 小模型不遵守二分类指令 | 精筛用判断稳的 R1，不强约束的小模型不行 |
| 5 | `"不相关".endswith("相关")` 是 True | 子串陷阱，否定词包含肯定词 |
| 6 | 垃圾片段进上下文 → LLM 编造 | 精筛把关 + 生成 prompt 明确"片段不足就说没有" |
| 7 | `"不存在相关性"` 被判成相关 | 别用子串猜语义，让 LLM 输出结构化 JSON 结论 |

**最重要的教训**：**RAG 的准确性不取决于生成模型多聪明，取决于检索到的片段多可靠——检索是上限。**

---

## 面试速答（Day 29-34 覆盖）

| 面试题 | 速答 |
|--------|------|
| RAG 完整流程？ | 第 5 节一句话 |
| 为什么需要分片？ | 太长语义稀释，太短上下文丢失 |
| 余弦相似度怎么算？ | cos(θ) = (A·B) / (|A|×|B|)，范围 [-1,1] |
| Embedding 模型怎么选？ | 看中文效果、维度、能否本地跑 |
| Chroma 与传统库区别？ | 存向量查相似度，ANN 索引 |
