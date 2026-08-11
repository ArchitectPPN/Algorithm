---
name: rag-pipeline-three-layer-filter
description: RAG Pipeline 三层过滤架构——余弦粗筛 + LLM 精筛 + 生成
metadata:
  type: project
---

RAG Pipeline（`rag/rag_pipeline.py`）的核心架构是三层过滤，截至 Day 36 稳定运行。

**三层流程**：
1. **余弦粗筛** `search()` — 查询转向量 → Chroma 余弦检索 top_k=5 → 相似度 < 0.5 丢弃（宁松勿紧，多取候选）
2. **LLM 精筛** `rerank()` — deepseek-r1:7b 逐条判断，强制输出 JSON `{"related": true/false}`，`_is_relevant()` 解析，只认布尔 true，解析失败默认不相关（宁可漏不可错）
3. **生成** `generate()` — 相关片段作上下文 + 提示词「只能根据片段回答，不要编造」→ LLM 回答

**安全闸**：精筛为空 → 直接返回"知识库中没有相关内容"，不调用生成（防幻觉，检索是上限）。

**关键参数/坑**：
- 精筛模型 deepseek-r1:7b 是推理模型，`num_predict` 必须给足 400+，否则思考占满配额返回空字符串
- 精筛结论用结构化 JSON 解析，不用子串判断——`"不存在相关性"` 含"相关"字但语义是否定（Day34 坑 7）
- 余弦度量要 `metadata={"hnsw:space": "cosine"}`，改度量必须 `rebuild=True` 重建索引

**CLI 模式**：`ask "查询"` / `repl` 交互 / 直接查询 / build+test

详细踩坑记录见 `docs/plans/rag/week5/day34-pitfalls.md`（7 个坑）。相关：[[rag-week6-progress]]
