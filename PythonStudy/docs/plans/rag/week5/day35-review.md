# Day 35：RAG 复盘 + 检验

> 目标：整理本周学习笔记，用检验三步法自测。

---

## 学习路线（约 60 分钟）

```
整理笔记（20min）→ 检验自测（30min）→ 预习第6周（10min）
```

---

## 第一步：整理本周笔记（20min）

✅ 已完成 → `docs/notes/rag/rag-notes.md`（第 5 周知识浓缩版：Embedding / 余弦 / 分片 / Chroma / 三层过滤 / 踩坑速查 / 面试速答）

把 Day 29-34 的关键知识点整理成一篇笔记 `docs/notes/rag/rag-notes.md`，包含：

1. **Embedding 原理**：文字 → 向量，语义相近 → 距离近
2. **余弦相似度**：`cos(θ) = (A·B) / (|A| × |B|)`，结果范围 -1 到 1
3. **分片策略**：chunk_size（片段大小）+ chunk_overlap（重叠，防止关键信息卡边界）
4. **向量数据库**：Chroma，存向量 + 查相似度，ANN 索引
5. **RAG 完整流程**：加载 → 分片 → 向量化 → 存 Chroma → 查询 → 检索 → 返回

---

## 第二步：检验自测（30min）

按三步法逐项检验：

### 默写代码

关掉所有文件，新建空白文件，凭记忆写出：

- [ ] `cosine_similarity(a, b)` — 点积 / (模长A × 模长B)
- [ ] `split_text(text, chunk_size, chunk_overlap)` — 按字符数分片
- [ ] `get_embedding(text)` — 调 ollama API
- [ ] `RAGPipeline.search(query)` — Chroma 检索

卡住了可以看原代码，但标记为"需复习"。

> 📌 **自测记录**：用户反馈"知道思路但写不出来" → **4 项全部标为「需复习」**。
> 建议下周一前用「看代码 → 关文件 → 默写 → 对照」循环过一遍，先从 `RAGPipeline.search()` 开始（最核心）。

### 一句话讲清楚

- [ ] Embedding 是什么？
- [ ] 为什么需要分片？
- [ ] Chroma 和传统数据库的区别？
- [x] RAG 的完整流程？ ✅ 已复述并校正（严谨版见 `day34-pipeline.md`「全流程逻辑」节）

### 回答追问

- [ ] 为什么不用关键词匹配而用 Embedding？
- [ ] chunk_overlap 设多大合适？为什么？
- [ ] 如果检索结果不相关，可能是什么原因？
- [ ] Embedding 模型能本地跑吗？生产环境怎么选？

> 📌 **测验记录**：`day35-quiz.md`（覆盖 Day 29-33）已完成并严格评分，参考解答带例子论证。

### 回答追问 · 参考解答

**Q1. 为什么不用关键词匹配，而用 Embedding？**

关键词匹配是**字面匹配**，只能命中"字面上出现同样词"的内容。三个致命短板：
1. **同义词失效**：用户问「如何防 SQL 注入」，文档里写的是「参数化查询」——字面一个词都不重合，但语义高度相关。关键词匹配直接漏掉。
2. **口语/变形失效**：「咋防注入」「防止注入攻击」字面不同，关键词匹配对不上。
3. **跨表达失效**：同一个概念换说法、换语言，字面完全对不上。

Embedding 把文本变成**语义向量**，语义相近 → 向量距离近，**不看字面**。实测（Day 29）：「今天天气真好」和「今天天气不错」字面不一样，但向量相似度很高。所以 RAG 用语义检索而不是关键词匹配。

⚠️ 但 Embedding 也不完美（中文效果一般），所以需要 LLM 精筛兜底——**用语义找候选，用 LLM 判真假**。

---

**Q2. chunk_overlap 设多大合适？为什么不能是 0 或者特别大？**

本教程用 `chunk_size=500, overlap=50`（overlap 占 size 的 **10%**），这是常用经验值。

- **为什么不能是 0**：关键信息可能恰好卡在分片边界，被一刀切断。比如一句话「该函数接受两个参数」恰好横跨两个分片，两个分片都只剩一半，语义就丢了。overlap 让边界内容在**相邻两个分片里都出现**，至少有一个是完整的。
- **为什么不能特别大**：overlap 本质是**重复存储**——同一段内容被存两遍，浪费存储和 embedding 成本；而且检索时可能返回两段几乎一样的片段，语义重复（这正是第 6 周 MMR 多样性检索要解决的）。
- **经验区间**：overlap 取 chunk_size 的 **10%-20%**。好比两段胶带拼接，重叠一点防止断开，但重叠太长就是浪费。

---

**Q3. 如果检索结果不相关，可能是什么原因？**

从四个角度排查：
1. **Embedding 模型**：模型中文效果差（nomic 是英文模型）、模型太小语义捕捉弱 → 换模型/评估模型
2. **分片**：chunk_size 太大 → 语义稀释（查询只命中片段一小部分，相似度被拉低）；太小 → 上下文丢失；盲切可能在句子中间切断
3. **阈值**：`min_similarity` 设太低，放进了大量低相似度垃圾（0.5 只是通用起步值，具体项目要校准）
4. **知识库本身**：库里根本没有相关内容——检索再准也搜不到不存在的东西（搜「红烧肉」就是这种情况）

补充：查询本身太短/太模糊也会拉低效果（如只搜「数据库」）。

---

**Q4. Embedding 模型能本地跑吗？生产环境怎么选？**

**能本地跑**：你现在用的 `nomic-embed-text` 就是通过 ollama **本地运行**的——免费、数据不出机器、无 API key。Day 30 就是在本地调通的。

**本地 vs 云端 API 对比**：

| | 本地（ollama nomic/bge） | 云端 API（OpenAI/智谱/通义） |
|---|---|---|
| 成本 | 免费 | 按 token 收费 |
| 隐私 | 数据不出机器 | 数据出网 |
| 中文效果 | 一般（nomic 是英文模型） | 好（专门中文模型） |
| 运维 | 自己起服务 | 免运维 |

**生产选型四要素**：① **中文效果**（中文项目别选纯英文模型）② **维度**（768/1024/1536，越高越精确但越贵越占存储）③ **成本与延迟** ④ **隐私合规**（数据能不能出网）。

⚠️ 关键：**Embedding 模型是 RAG 的"眼睛"，决定检索质量的上限**。换 embedding 模型意味着**维度可能变化、整个索引要重建**。

---

### 默写 · 参考答案（背完这 4 段）

**① cosine_similarity(a, b)** —— 点积 / (模长A × 模长B)
```python
def cosine_similarity(a, b):
    dot = sum(x * y for x, y in zip(a, b))
    mag_a = sum(x * x for x in a) ** 0.5
    mag_b = sum(x * x for x in b) ** 0.5
    return dot / (mag_a * mag_b)
```

**② split_text(text, chunk_size, chunk_overlap)** —— 按字符数滑动分片
```python
def split_text(text, chunk_size=500, chunk_overlap=50):
    chunks = []
    start = 0
    while start < len(text):
        chunks.append(text[start:start + chunk_size])
        start += chunk_size - chunk_overlap  # 每次前进 size-overlap，片段间留重叠
    return chunks
```

**③ get_embedding(text)** —— 调 ollama API
```python
def get_embedding(text):
    resp = requests.post(
        "http://localhost:11434/api/embeddings",
        json={"model": "nomic-embed-text", "prompt": text},
        timeout=30,
    )
    resp.raise_for_status()
    return resp.json()["embedding"]
```

**④ RAGPipeline.search(query)** —— Chroma 余弦检索 + 阈值过滤
```python
def search(self, query, top_k=3, min_similarity=0.5):
    query_vec = self._embed(query)          # 1. 查询转向量
    results = self.collection.query(        # 2. Chroma 查 Top-K
        query_embeddings=[query_vec],
        n_results=top_k,
        include=["documents", "metadatas", "distances"],
    )
    output = []
    for i in range(len(results["ids"][0])):
        similarity = 1 - results["distances"][0][i]  # 3. 余弦距离 → 相似度
        if similarity < min_similarity:              # 4. 阈值过滤
            continue
        output.append({
            "content": results["documents"][0][i],
            "file": results["metadatas"][0][i]["file"],
            "chunk_index": results["metadatas"][0][i]["chunk_index"],
            "similarity": similarity,
        })
    return output
```

---

## 第三步：预习第6周（10min）

第6周内容：向量存储与检索进阶
- Chroma 高级检索（MMR 多样性检索）
- 元数据过滤（按来源文件、类别筛选）
- 检索效果评估（人工评估 10-20 个查询）

---

## 本周产出总览

| 文件 | 说明 |
|------|------|
| `learning/rag/cosine_similarity.py` | 手写余弦相似度 + ollama 验证 |
| `learning/rag/text_splitter.py` | 文本分片脚本 |
| `learning/rag/document_loader.py` | 文档加载 + 分片 + 统计 |
| `learning/rag/chroma_demo.py` | Chroma 增删查 |
| `myagent/rag_pipeline.py` | 端到端 RAG Pipeline |
| `myagent/search.py` | CLI 检索入口 |
| `data/knowledge/` | 知识库文档（3-5 篇 .md） |
| `docs/notes/rag/rag-notes.md` | 本周学习笔记 |