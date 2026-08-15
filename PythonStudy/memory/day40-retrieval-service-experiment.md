---
name: day40-retrieval-service-experiment
description: Day40 FastAPI 检索服务实验结论——kb_bge 余弦距离分布/默认阈值、MMR λ 验证、服务实现要点
metadata: 
  node_type: memory
  type: project
  modified: 2026-08-15T00:00:00.000Z
---

Day 40（2026-08-15）把 Day36-39 检索能力封装成 FastAPI 服务。产出：`api/rag_service.py`（api 包新建），文档：`docs/plans/rag/week6/day40-retrieval-service.md`。

**服务架构**：6 端点——`GET /health`、`GET /collections`、`POST /search`（语义检索+阈值）、`POST /search/filter`（按 file 元数据过滤）、`POST /search/mmr`（MMR 多样性）、`POST /ask`（RAG 问答：检索片段 + ollama 生成）。复用 Day38 选型结论：bge-m3 + `kb_bge` 集合 + cosine 空间，**不用 Day34 旧 RAGPipeline**（计划文档写的是 nomic + L2，实现时已对齐修正）。

**实测关键数据**：
- **kb_bge 余弦距离分布**：强相关 0.28~0.42，弱相关 0.47~0.54，明显不相关 >0.6 → 默认阈值 **0.6**（先试 0.8 太松几乎不过滤）
- **MMR λ 验证**（查询"API 接口设计"）：λ=1.0 退化普通 Top-K（api-design 占 2 条）、λ=0.3 覆盖 5 个文件（普通只有 4 个）、λ=0.7 推荐折中
- 参数校验：FastAPI+Pydantic 自动 422（空/超长 200/top_k 0~20/lambda 0~1）
- /ask（Day40 延伸）：qwen2.5:3b 生成"防止 SQL 注入"→ 参数化查询回答 + 引用 sql-best-practices.md；无相关片段 empty=true 不调 LLM；无效模型 503

**实现要点（复用价值）**：
- MMR 候选向量用 `collection.get(ids=..., include=["embeddings"])` 一次取回，**避免对每个候选重复调 embedding API**
- ⚠️ `collection.get(ids=...)` 返回顺序**不等于**传入 ids 顺序（Chroma 按插入序返回，实测错位 8/10），必须 `dict(zip(ids, embeddings))` 按 id 对齐，否则 MMR 在错位向量上计算（Day40 审查 S1，已修；修复后 λ 实验结论不变）
- `/ask` 生成模型做了白名单校验（ALLOWED_LLM_MODELS），非法 model 返回 422（Day40 审查 G2）
- FastAPI `lifespan` 里预热 bge-m3（首次加载进内存 20s+），首个请求不卡顿
- `kb_bge` 的 metadata 只有 `file`/`chunk_index`（无 category），filter 按 file 过滤，category 是预留参数

**服务运行**：`python -m uvicorn api.rag_service:app --port 8000`，Swagger 在 `/docs`。

**`/ask` 端点（Day40 延伸，已完成）**：检索 Top-K → 拼 prompt（复用 Day34 generate 结构）→ ollama `qwen2.5:3b` 生成 → 返回 `{answer, sources}`。无相关片段不调 LLM 直接返回 empty；LLM 失败 503。⚠️ 不用 `deepseek-r1:7b`——此环境未安装（ollama 实际生成模型是 qwen2.5:3b / qwen3:8b / phi4-mini 等）。实测首次 35s（加载模型）、驻留后 8.8s。

**下一步衔接**：Day 41（Week6 复盘）。相关：[[rag-week6-progress]]、[[day39-evaluation-experiment]]、[[rag-pipeline-three-layer-filter]]
