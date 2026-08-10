# Day 41：Week6 复盘 + 预习 Week7

> 目标：整理 Week6 学习成果，检验自测，预习 Week7（RAG + Agent 整合）。

---

## 学习路线（约 60-90 分钟）

```
整理笔记（20min）→ 检验自测（30min）→ 预习 Week7（10min）
```

---

## 第一步：整理笔记（20min）

整理 Week6 关键知识点：

1. **元数据过滤**：`where` 条件（`$eq`/`$ne`/`$gt`/`$in`/`$and`/`$or`），检索前缩小范围
2. **MMR 多样性检索**：`MMR = λ×相关性 - (1-λ)×冗余度`，解决 Top-K 结果重复
3. **Embedding 选型**：中文效果、本地/云、维度、成本，用已知答案实测准确率
4. **效果评估**：Recall@K（找没找到）、MRR（排得靠不靠前）、Top-1 命中率
5. **检索服务化**：FastAPI 包一层 HTTP 接口

---

## 第二步：检验自测（30min）

### 默写代码

- [ ] `collection.query(where={"category": "数据库"})` — 元数据过滤检索
- [ ] `mmr_select(query_vec, candidates, k, lambda_)` — MMR 核心循环（⚠️ 参数名用 `lambda_`，`lambda` 是 Python 保留字）
- [ ] `evaluate(queries, search_fn, k)` — 评估脚本（Recall@K + MRR）
- [ ] FastAPI 路由 + Pydantic 模型 + 调 RAG

### 一句话讲清楚

- [ ] 元数据过滤能解决什么问题？
- [ ] MMR 和普通 Top-K 的区别？
- [ ] λ 参数的作用？
- [ ] Recall@K 和 MRR 各自衡量什么？
- [ ] 为什么评估检索效果要用"已知答案"的测试集？

### 回答追问

- [ ] 过滤条件太严返回空结果怎么办？
- [ ] λ=0 和 λ=1 时 MMR 会怎样？
- [ ] 能混用两个 embedding 模型的向量吗？为什么？
- [ ] Recall@5=100% 但 MRR=0.3，说明什么？

---

## 第三步：预习 Week7（10min）

Week7 主题：**RAG + Agent 整合**（先裸写再对比 LangChain）

核心问题：
- 检索到片段后，怎么让 LLM"基于这些片段"回答？→ 拼 prompt
- 检索只是工具，Agent 怎么自主决定"要不要检索、检索什么"？
- LangChain 帮你封装了哪几步？裸写和框架的差距在哪？

**思考题：** 现在我们有"检索服务"（返回片段），Week7 要做的是"基于片段生成答案"。答案的质量取决于什么？

---

## Week6 产出总览

| 文件 | 说明 |
|------|------|
| `rag/query_demo.py` | 元数据过滤检索 demo |
| `rag/mmr_demo.py` | MMR 多样性检索 demo |
| `rag/embedding_compare.py` | 三种 Embedding 模型对比实验 |
| `rag/evaluate.py` | 检索效果评估脚本 |
| `api/rag_service.py` | FastAPI 知识库检索服务 |
| `docs/plans/rag/week6/day*.md` | 每日学习笔记 |
