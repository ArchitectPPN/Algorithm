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
- Day 39（效果评估 + 查询改写）✅ — `rag/evaluate.py` + `rag/query_rewrite.py` 跑通。基线 MRR 0.836 → HyDE 0.900、Top-1 80%→87%，验证"先改查询再改模型"。详见 [[day39-evaluation-experiment]]
- Day 40（FastAPI 检索服务）✅ — `api/rag_service.py` 跑通，6 端点（health/collections/search/search/filter/search/mmr/**ask**）。实测距离分布定阈值 0.6、MMR λ=0.3 覆盖 5 文件、FastAPI 自动 422 校验；`/ask` 用 ollama qwen2.5:3b 完成 RAG 问答闭环。详见 [[day40-retrieval-service-experiment]]

**下次起点**：Day 41（Week6 复盘）
- 计划文档：`docs/plans/rag/week6/day41-week6-review.md`
- 可选延伸：给 rag_service 加 `/ask` 端点已完成（检索 + LLM 生成回答，闭环 RAG）

**Week6 剩余**：Day 41 复盘 → 进入 Week7（裸写 RAG 链 + Agent 整合）

**环境备忘**：Python 3.13（注意：Day36 记录说 3.9，实际环境已更新为 3.13，`|` 注解可直接用，但脚本里 `from __future__ import annotations` 无害可留）；ollama 地址 http://localhost:11434；模型 nomic-embed-text / bge-m3 / deepseek-r1:7b；智谱 key 在 `PythonStudy/.env`（已 gitignore）。相关：[[rag-pipeline-three-layer-filter]]
