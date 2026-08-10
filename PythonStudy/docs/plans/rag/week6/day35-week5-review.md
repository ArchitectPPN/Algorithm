# Day 35：Week5 复盘 + 检验自测

> 目标：整理本周学习笔记，用检验三步法自测，为 Week6 打基础。

---

## 学习路线（约 60-90 分钟）

```
整理笔记（20min）→ 检验自测（30min）→ 预习 Week6（10min）
```

---

## 第一步：整理笔记（20min）

把 Day 29-34 的关键知识点整理成一篇笔记 `docs/notes/rag/rag-notes.md`：

1. **Embedding 原理**：文字 → 向量，语义相近 → 距离近
2. **余弦相似度**：`cos(θ) = (A·B) / (|A| × |B|)`，结果范围 -1 到 1
3. **分片策略**：chunk_size（片段大小）+ chunk_overlap（重叠，防止关键信息卡边界）
   - ⚠️ overlap 只是补丁，无法根治句子截断 → 按标题/段落切更优
4. **向量数据库**：Chroma，存向量 + 查相似度，ANN（HNSW）索引
5. **距离度量**：L2 vs 余弦，RAG 场景推荐余弦（对文本长度不敏感、阈值好定）
6. **阈值过滤**：top_k 会强制返回 N 条，必须加 max_distance/相似度阈值
7. **RAG 完整流程**：加载 → 分片 → 向量化 → 存 Chroma → 查询 → 检索 → 返回

---

## 第二步：检验自测（30min）

按三步法逐项检验：

### 默写代码

关掉所有文件，新建空白文件，凭记忆写出：

- [ ] `cosine_similarity(a, b)` — 点积 / (模长A × 模长B)
- [ ] `split_text(text, chunk_size, chunk_overlap)` — 按字符数分片
- [ ] `get_embedding(text)` — 调 ollama API
- [ ] `collection.add(...)` + `collection.query(...)` — Chroma 插入 + 检索
- [ ] `search(query, top_k, max_distance)` — 检索 + 阈值过滤

卡住了可以看原代码，但标记为"需复习"。

### 一句话讲清楚

- [ ] Embedding 是什么？
- [ ] 为什么需要分片？
- [ ] overlap 为什么不能根治句子截断？
- [ ] Chroma 和传统数据库的区别？
- [ ] L2 和余弦相似度的区别？RAG 推荐哪个？
- [ ] 距离阈值怎么定？（三种方法：余弦固定范围、Elbow Method、已知答案校准）
- [ ] RAG 的完整流程？

### 回答追问

- [ ] 为什么不用关键词匹配而用 Embedding？
- [ ] chunk_overlap 设多大合适？为什么？
- [ ] 如果检索结果不相关，可能是什么原因？（分片不合理 / 模型不合适 / 阈值太松 / 知识库缺内容）
- [ ] Embedding 模型能本地跑吗？生产环境怎么选？

---

## 第三步：预习 Week6（10min）

Week6 内容：向量存储与检索进阶
- 元数据过滤（按来源文件、类别筛选）
- MMR 多样性检索（避免结果重复）
- Embedding 模型对比
- 检索效果评估（量化准确率）
- FastAPI 检索服务

思考题：当前检索是"全库搜"，如果知识库有几百篇文档，怎么快速筛出某类文档的片段？
