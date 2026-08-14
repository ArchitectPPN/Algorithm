---
name: day38-embedding-compare-experiment
description: Day38 Embedding 模型对比实验的结论与方法论——选型要用设计过的实验拉差距
metadata: 
  node_type: memory
  type: project
  modified: 2026-08-13T00:00:00.000Z
---

Day 38（2026-08-13）Embedding 模型对比实验的核心结论。产出：`rag/embedding_compare.py`，文档：`docs/plans/rag/week6/day38-embedding-compare.md`。

**实测结论**（知识库 11 文档 23 分片，15 查询，含 4 条易混淆题）：
- nomic-embed-text：67% / 768 维 / 本地免费
- bge-m3：87% / 1024 维 / 本地免费
- 智谱 embedding-3：87% / 1024 和 2048 维结果一样 / 按量收费
- **选型：bge-m3**（中文强、本地免费、1024 维够用）

**方法论：怎么拉出模型差距**（这是最值的教训）：
- 知识库太小（5 文档）→ 三模型全 100%，什么都说明不了
- 扩到 11 文档 + 加"易混淆"查询（主题词重叠的题）→ 差距才出来（67% vs 87%）
- 实验设计三要素：**样本量、难度梯度、干扰项**
- 维度翻倍（1024→2048）在小知识库无收益 → 维度够用即可，高维只在数据量大时值

**关键技术坑**：
- `ollama pull bge-small-zh` 不存在 → 中文 BGE 用 `bge-m3`
- 智谱 API key 绝不能提交 git：脚本常量 / 环境变量 / `PythonStudy/.env`（已 gitignore）
- 云 API 批量调用（攒 16 条一批）比逐条快一个量级
- Windows 控制台中文乱码 → `sys.stdout.reconfigure(encoding="utf-8")`

**下一步衔接**：Day 39 效果评估直接复用这个对比脚本和 11 文档知识库。相关：[[rag-week6-progress]]、[[rag-pipeline-three-layer-filter]]
