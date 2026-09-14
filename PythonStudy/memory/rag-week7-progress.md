---
name: rag-week7-progress
description: RAG Week7 实际进度——Day42-45 已完成（4/7），下次从 Day46 开始；2026-09-09 起改"学习者动手"模式
metadata: 
  node_type: memory
  type: project
  modified: 2026-09-09T00:00:00.000Z
---

RAG 学习第7周（RAG + Agent 整合）**实际进度，2026-09-09 核对**（以 git log + 教程实验结果小节为准）。

**已完成（4/7，教程均含实测数据，已提交推送）**：
- Day 42 RAG 链组件化 ✅（a671f83，08-29）`rag/rag_chain.py`（RAGChain 类）
  + 温度稳定性实验（8b5734c，09-02，**学习者亲手采样**：t=1 → 20 次 20 个版本；t=0 → 30 次 2 版本仅首跑差异；`run10.py`；rag_chain.py 已加 temperature: 0）
- Day 43 审查 Prompt 模板 + A/B 对照 ✅（6cc1062/95f1cc4，09-01）`rag/review_prompts.py` + `rag/ab_compare.py`
  关键：无 RAG 组 basis 全幻觉；3b 证据在眼前也漏检（vuln_log 密码明文）；3b JSON 三坑修俩（format:json 治引号、模板限篇幅治截断）
- Day 44 RAG 集成 Agent ✅（f634fcf，09-01）`myagent/rag_agent_practice.py`（ReAct + search_knowledge 工具 + 跨轮编号）
  关键：Agent"该查才查"标准 = 通用知识答不了才查；不检索 ≠ basis=null（编 [1]，prompt 三轮迭代治不住）；ollama 实测 arguments 是 dict、think:false、t=0 硬前提
- Day 45 引用校验器 ✅（09d1171 + e769f66，09-09）`rag/citation_check.py`（R1-R4 四规则 + verdict 三态）
  含学习者攻击测试驱动的加固：'根据[2]和[5]' 多编号漏检 bug → extract_bases 全编号逐个校验
  三段实测：vuln_sql PASS / vuln_auth PASS_WITH_WARNINGS / vuln_log REJECTED（R1 拦幻觉）

**下次起点：Day 46**（LangChain RAG 对比 + /review 端点，教程 `docs/plans/rag/week7/day46-langchain-compare-and-service.md`）
之后：Day 47 综合实战（10 段测试代码 + 有/无 RAG 对比报告——**简历核心素材**）、Day 48 复盘 + 预习 Week8（评估专题）。

**⚠️ 模式调整（2026-09-09 和学习者确认）**：
- 学习者中心 = **转型应用层 Agent 开发（求职导向）**，不是 PHP 日常
- Day42-45 代码主要由 AI 执行 → 面试追问有露馅风险；**Day46 起改"学习者写、AI 审"模式**，AI 不再整篇代写
- 概念讨论落 `docs/interview/` 面试卡（候选：幻觉四形态 / 温度与稳定性 / 校验分层与不完备 / Agent vs 固定管道 / RAG 面试一句话）
- 原计划日期已失效（两周走完 4 天）→ 按 transition-plan 硬止损规则顺延，**不追日期只追完成度**

**环境备忘**：Python 3.13；ollama http://localhost:11434（bge-m3 / qwen2.5:3b / qwen3:8b）；kb_bge 23 条分片；rag_chain.py 已 t=0；根目录"数据对比工具.html"为学习者个人文件勿提交。
相关：[[rag-week6-progress]] [[rag-pipeline-three-layer-filter]]
