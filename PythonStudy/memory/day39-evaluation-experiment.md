---
name: day39-evaluation-experiment
description: Day39 检索评估 + 查询改写实验的结论与方法论——MRR 指标体系、改写优先级、测试集歧义判断
metadata: 
  node_type: memory
  type: project
  modified: 2026-08-14T00:00:00.000Z
---

Day 39（2026-08-14）检索效果评估 + 查询改写的核心结论。产出：`rag/evaluate.py` + `rag/query_rewrite.py`，文档：`docs/plans/rag/week6/day39-evaluation.md`。

**实测结论**（知识库 11 文档 23 分片，15 查询，bge-m3）：
- 基线：Recall@5=93%、MRR=0.836、Top-1=80%
- 同义词扩展：MRR→0.883、Top-1→87%（零成本，救回"上线后→部署后/监控"这类用词问题）
- HyDE：MRR→0.900、Top-1→87%（救回"登录密码→哈希存储"，排第5→第1）
- **RAG 优化优先级：先改查询 → 再调参 → 最后才换模型/加精筛**

**方法论（最值钱的）**：
- 指标三件套：Recall@K 看"找没找到"，MRR 看"排得靠不靠前"，Top-1 看"最好的排第一没"
- 失败分析要先分类：**用词对不上 → 改写**；**语义指向别处 → 改测试集/补文档**
- 「请求太慢怎么排查」两方法都救不回——是测试集歧义（日志/查询优化/缓存全是"性能"干扰），不是检索问题
- HyDE 原理：答案准不准不重要，重要的是"长度句式像文档"，向量更近

**技术坑**：
- HyDE 用 ollama 生成模型 `qwen2.5:3b`（比 deepseek-r1 轻，生成够用）
- 多路检索合并策略：同片段取最小距离，再按距离排序
- 只有问题查询才改写，其余保持原句（避免改写好的查询反而变差）

**下一步衔接**：Day 40 把 bge-m3 检索链路包成 FastAPI 服务。相关：[[rag-week6-progress]]、[[day38-embedding-compare-experiment]]、[[rag-pipeline-three-layer-filter]]
