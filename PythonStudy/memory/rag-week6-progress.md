---
name: rag-week6-progress
description: RAG 学习第6周进度——下次从 Day 39 效果评估开始
metadata: 
  node_type: memory
  type: project
  originSessionId: 47a3daae-5b68-4293-8177-0f801fa68e46
  modified: 2026-08-13T00:00:00.000Z
---

RAG 学习第6周（向量存储与检索进阶）进度，截至 2026-08-13。

**已完成**：
- Day 35（Week5 复盘）✅
- Day 36（元数据过滤）✅ — `rag/query_demo.py` 跑通，踩坑+FAQ 落 `docs/plans/rag/week6/day36-metadata-filter.md`。注意此文件顶部有 `from __future__ import annotations`（兼容 Python 3.9 的 `dict | None` 注解），别删。
- Day 37（MMR 多样性检索）✅
- Day 38（Embedding 模型对比）✅ — `rag/embedding_compare.py` 跑通，知识库扩到 11 文档。结论：选 bge-m3。详见 [[day38-embedding-compare-experiment]]

**下次起点**：Day 39（效果评估）
- 计划文档：`docs/plans/rag/week6/day39-evaluation.md`
- 前置依赖：Day38 的对比脚本已就绪，知识库 11 文档 23 分片可直接复用

**Week6 剩余**：Day 39 效果评估 → Day 40 FastAPI 服务 → Day 41 复盘

**环境备忘**：Python 3.13（注意：Day36 记录说 3.9，实际环境已更新为 3.13，`|` 注解可直接用，但脚本里 `from __future__ import annotations` 无害可留）；ollama 地址 http://localhost:11434；模型 nomic-embed-text / bge-m3 / deepseek-r1:7b；智谱 key 在 `PythonStudy/.env`（已 gitignore）。相关：[[rag-pipeline-three-layer-filter]]
