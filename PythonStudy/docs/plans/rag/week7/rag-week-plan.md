# 第7周：RAG + Agent 整合（先裸写再对比 LangChain）

> Week6 已掌握检索进阶（过滤/MMR/选型/评估/服务化），`/ask` 端点已跑通最小 RAG 闭环。
> Week7 把 RAG 从"检索问答服务"推进到**真实应用场景：规范增强的代码审查 Agent**。
> 主线仍是裸写——Week6 攒下的检索能力（RAGChain）成为 Agent 的一个工具。
>
> 建议基准日期：2026-08-31（周一）。实际进度较 transition-plan 原历（08-17 起）顺延约 2 周，按自己节奏推即可。

---

## 为什么必须学本周内容？

| 问题 | Week6 的水平 | Week7 要达到 |
|------|------------|------------|
| 检索结果只是"片段列表"？ | ✅ 能检索、能问答 | ✅ 片段变成**审查意见的规范依据**（生产级用法） |
| RAG 链散落在 pipeline/服务里？ | ❌ 逻辑重复、不能复用 | ✅ 抽成独立 `RAGChain` 类，进程内直接调 |
| LLM 只会"凭记忆"审查代码？ | ❌ 通用知识，说不出依据 | ✅ Agent 自主检索规范，引用具体条目 |
| 敢引用规范，但敢信吗？ | ❌ 模型会编不存在的条目 | ✅ 编号引用 + 代码校验，防幻觉 |
| 裸写和 LangChain 差在哪？ | ⚠️ Git Agent 对比过，RAG 没比过 | ✅ RAG 场景下逐项对比封装边界 |

**面试价值：** "你做的 RAG 项目落地到什么场景？"——本周产出直接回答这个问题：
规范驱动的 AI 代码审查（RAG + Agent），且能讲清有/无 RAG 的对比实验数据。

---

## 本周学习路线

```
Day 42（周一）：裸写 RAG 链组件化——从 /ask 抽取出可复用的 RAGChain 类
  ↓
Day 43（周二）：Prompt 模板设计——代码审查场景（角色/上下文/结构化输出）
  ↓
Day 44（周三）：RAG 集成进 Agent——search_knowledge 工具 + ReAct loop，三方案对比
  ↓
Day 45（周四）：规范引用输出——编号引用 + 引用校验，治幻觉
  ↓
Day 46（周五）：LangChain RAG 对比 + /review 端点服务化 + git 提交
  ↓
Day 47（周六，5-6h）：综合实战——RAG 增强代码审查 Agent 完善（10 段测试代码 + 对比报告）
  ↓
Day 48（周日）：Week7 复盘 + 预习 Week8（RAG 评估专题）+ LangGraph 了解级
```

---

## 与 transition-plan 的映射关系

| 本周内容 | 原计划（第7周 Day 43-49） | 调整说明 |
|---------|------------------------|---------|
| Day 42 RAG 链组件化 | 原 Day 43 裸写 RAG 链 | **降级为"抽取重构"**——Day34 `rag_pipeline.ask()`、Day40 `/ask` 已裸写过链，缺的是可复用类 |
| Day 43 审查 Prompt 模板 | 原 Day 44 | 保留，审查角色从"PHP"改为覆盖多规范（语料是 Python 为主） |
| Day 44 RAG 集成 Agent | 原 Day 45 | 保留为核心日，复用 git_agent_practice 的 ReAct loop 骨架 |
| Day 45 规范引用 + 校验 | 原 Day 46 | 保留，补充"引用编号 → 真实来源"校验代码 |
| Day 46 LangChain 对比 + 服务化 | 原 Day 47 | **压缩**——LangChain 了解级已完成（myagent/langchain 4 个示例），只补 RAG 链对比 |
| Day 47 综合实战 | 原 Day 48 | 保留不动 |
| Day 48 复盘 + 预习 | 原 Day 49 | 保留（原计划"了解 LangGraph"放这天） |

> ⚠️ 编号依据实际进度：Week6 实际执行到 Day 41（周日复盘），故本周为 Day 42-48。
> transition-plan 里"第7周末：RAG 增强 Agent 能引用具体规范条目"的里程碑 = Day 45/47。

---

## 本周产出物

| 产出 | 说明 |
|------|------|
| `rag/rag_chain.py` | 独立 RAGChain 类（检索→拼 prompt→生成，进程内复用） |
| `rag/review_prompts.py` | 代码审查 Prompt 模板（有/无 RAG 两套 + 结构化输出 + 引用校验） |
| `myagent/rag_agent_practice.py` | RAG 增强代码审查 Agent（ReAct loop + search_knowledge 工具） |
| `data/test_code/*.py` | 10 段测试代码（埋 SQL 注入/鉴权缺失/日志明文等问题，2 干净 + 8 问题） |
| `myagent/langchain/rag_langchain_compare.py` | LangChain RAG 链 vs 裸写对比实验 |
| `api/rag_service.py`（扩展） | 新增 `POST /review` 端点（复用 RAGChain） |
| `rag/compare_report.md`（或 docs 笔记） | 有 RAG vs 无 RAG 审查质量对比报告 |
| `docs/plans/rag/week7/day*.md` | 每日学习教程 |

---

## 面试考点覆盖

| 面试题 | 对应 |
|--------|------|
| 你的 RAG 项目落地场景是什么？ | 全周：规范驱动的代码审查 |
| Agent 检索（工具）和固定 RAG 管道怎么选？ | Day 44 三方案对比实验 |
| 怎么防止 RAG 回答幻觉/编造引用？ | Day 45 编号引用 + 校验 |
| LangChain 的 RAG 封装帮你做了什么？ | Day 46 对比笔记 |
| 怎么证明加 RAG 比不加好？ | Day 47 对比报告（为 Week8 评估专题埋钩子） |

---

## 环境依赖（开工前检查）

- ollama 已启动（`http://localhost:11434`），已装模型：`bge-m3`、`qwen2.5:3b`、`qwen3:8b`
- `kb_bge` 集合存在（`python rag/embedding_compare.py --rebuild` 重建）
- `data/knowledge/` 11 篇规范文档（Day38 已扩充）
- 智谱 API key 在 `PythonStudy/.env`（Day 44 备用：更强的 function calling）
