---
name: rag-week7-progress
description: RAG 学习第7周计划已排好——Day42-48 RAG+Agent 整合（规范驱动的代码审查），待从 Day42 开始执行
metadata: 
  node_type: memory
  type: project
  modified: 2026-08-28T00:00:00.000Z
---

RAG 学习第7周（RAG + Agent 整合）计划，2026-08-28 排定，待执行。

**主题**：把 Week6 的检索能力推进到真实场景——**规范驱动的 AI 代码审查 Agent**。
主线仍是裸写，RAGChain 成为 Agent 的一个工具（search_knowledge）。

**每日计划**（Day42-48，教程已落 `docs/plans/rag/week7/`）：
- Day 42：裸写 RAG 链组件化——从 /ask 抽取可复用的 `rag/rag_chain.py`（RAGChain 类）
- Day 43：审查 Prompt 模板——`rag/review_prompts.py`（四要素 + 编号引用 + JSON 输出）
- Day 44：RAG 集成进 Agent——`myagent/rag_agent_practice.py`（ReAct loop + search_knowledge 工具，三方案对照）
- Day 45：规范引用 + 校验治幻觉——`rag/citation_check.py`（编号核对 + 超范围识别）
- Day 46：LangChain RAG 对比 + `/review` 端点服务化 + git 提交（压缩日，LangChain 了解级已完成）
- Day 47：综合实战——10 段测试代码 + 有/无 RAG 对比报告（里程碑产出，简历素材）
- Day 48：Week7 复盘 + 预习 Week8（RAG 评估专题）+ LangGraph 了解级

**与 transition-plan 的偏差**（已在 rag-week-plan.md 说明）：
- 原计划第7周是 Day43-49；实际 Week6 执行到 Day41，故本周顺延为 Day42-48
- "裸写 RAG 链"降级为"抽取重构"——Day34 `rag_pipeline.ask()` 和 Day40 `/ask` 已裸写过链
- LangChain 了解级压缩——`myagent/langchain/` 已有 4 个示例，Day46 只补 RAG 场景对比

**起点**：Day 42，计划文档 `docs/plans/rag/week7/day42-rag-chain-lib.md`

**可复用地基**（Week1-6 已就位）：
- 检索：`rag/embedding_compare.py`（bge-m3 + kb_bge 集合，cosine，阈值 0.6）
- ReAct loop 骨架：`myagent/git_agent_practice.py`
- LLM 适配器：`myagent/llm_adapter.py`（OpenAI/Anthropic）
- FastAPI 服务：`api/rag_service.py`（6 端点含 /ask，Day46 扩 /review）
- 知识库：`data/knowledge/` 11 篇规范文档
- LangChain 示例：`myagent/langchain/` 4 个（quickstart/create_agent/git_agent/three_ways）

**环境备忘**：Python 3.13；ollama http://localhost:11434；模型 bge-m3 / qwen2.5:3b / qwen3:8b / phi4-mini；
智谱 key 在 `PythonStudy/.env`（gitignore）。相关：[[rag-week6-progress]] [[rag-pipeline-three-layer-filter]]
