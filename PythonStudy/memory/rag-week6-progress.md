---
name: rag-week6-progress
description: RAG 学习第6周进度——下次从 Day 37 MMR 开始
metadata: 
  node_type: memory
  type: project
  originSessionId: 47a3daae-5b68-4293-8177-0f801fa68e46
  modified: 2026-08-11T15:51:54.577Z
---

RAG 学习第6周（向量存储与检索进阶）进度，截至 2026-08-11。

**已完成**：
- Day 35（Week5 复盘）✅
- Day 36（元数据过滤）✅ — `rag/query_demo.py` 跑通，踩坑+FAQ 落 `docs/plans/rag/week6/day36-metadata-filter.md`。注意此文件顶部有 `from __future__ import annotations`（兼容 Python 3.9 的 `dict | None` 注解），别删。

**下次起点**：Day 37（MMR 多样性检索）
- 计划文档：`docs/plans/rag/week6/day37-mmr.md`（原理+手写 MMR+对比效果，已就绪）
- 产出文件：`rag/mmr_demo.py`（待写）
- 核心公式：MMR = λ×相关性 - (1-λ)×冗余度
- 前置依赖：numpy 已装（2.0.2），ollama 本地跑 nomic-embed-text
- 三条推进路线待用户选：A.我直接写 / B.用户先读原理 / C.用户先自己写

**Week6 剩余**：Day 38 模型对比 → Day 39 效果评估 → Day 40 FastAPI 服务 → Day 41 复盘

**环境备忘**：Python 3.9（不是 3.10+，类型注解用 `|` 要加 `from __future__ import annotations`）；ollama 地址 http://localhost:11434；模型 nomic-embed-text / deepseek-r1:7b。相关：[[rag-pipeline-three-layer-filter]]
