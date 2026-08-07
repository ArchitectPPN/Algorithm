# Day 35：RAG 复盘 + 检验

> 目标：整理本周学习笔记，用检验三步法自测。

---

## 学习路线（约 60 分钟）

```
整理笔记（20min）→ 检验自测（30min）→ 预习第6周（10min）
```

---

## 第一步：整理本周笔记（20min）

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

### 一句话讲清楚

- [ ] Embedding 是什么？
- [ ] 为什么需要分片？
- [ ] Chroma 和传统数据库的区别？
- [ ] RAG 的完整流程？

### 回答追问

- [ ] 为什么不用关键词匹配而用 Embedding？
- [ ] chunk_overlap 设多大合适？为什么？
- [ ] 如果检索结果不相关，可能是什么原因？
- [ ] Embedding 模型能本地跑吗？生产环境怎么选？

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