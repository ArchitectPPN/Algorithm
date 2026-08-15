# Day 40：综合实战——知识库检索服务（FastAPI + RAG）

> 目标：把 Day 36-39 的检索能力封装成 FastAPI 服务，让任何客户端（前端/机器人）能通过 HTTP 调用。

---

## 学习路线（约 4-5 小时）

```
设计 API（20min）→ 服务实现（60min）→ 流式检索（30min）→ 测试（30min）→ 综合验证（40min）
```

---

## 第一步：设计 API（20min）

把检索能力暴露成 HTTP 接口，设计如下：

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/search` | 语义检索，返回 Top-K 片段 |
| POST | `/search/filter` | 带元数据过滤的检索 |
| POST | `/search/mmr` | MMR 多样性检索 |
| POST | `/ask` | RAG 问答（检索 + LLM 生成回答） |
| GET | `/collections` | 查看知识库集合信息 |
| GET | `/health` | 健康检查 |

### 请求/响应模型

**请求**（`POST /search`）：

```json
{
  "query": "如何防止 SQL 注入",
  "top_k": 3,
  "max_distance": 350.0
}
```

**响应**：

```json
{
  "query": "如何防止 SQL 注入",
  "results": [
    {
      "file": "sql-best-practices.md",
      "chunk_index": 0,
      "distance": 209.19,
      "content": "永远使用参数化查询..."
    }
  ],
  "elapsed_ms": 32.5
}
```

---

## 第二步：服务实现（60min）

```python
"""api/rag_service.py —— 知识库检索服务"""
import os
import sys
import time
import numpy as np

# 复用 Week5 的 Pipeline
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from rag.rag_pipeline import RAGPipeline

from fastapi import FastAPI, Query
from pydantic import BaseModel, Field
from typing import Optional

app = FastAPI(title="知识库检索服务", version="0.1.0")

# 初始化 RAG Pipeline（服务启动时加载）
base_dir = os.path.dirname(__file__)
rag = RAGPipeline(
    knowledge_dir=os.path.join(base_dir, "..", "data", "knowledge"),
    chroma_path=os.path.join(base_dir, "..", "chroma_data"),
)


# ── 请求/响应模型 ──
class SearchRequest(BaseModel):
    query: str = Field(..., min_length=1, max_length=200, description="查询内容")
    top_k: int = Field(3, ge=1, le=20)
    max_distance: float = Field(350.0, ge=0, description="L2 距离阈值，超过丢弃")


class SearchResult(BaseModel):
    file: str
    chunk_index: int
    distance: float
    content: str


class SearchResponse(BaseModel):
    query: str
    results: list[SearchResult]
    elapsed_ms: float


# ── 接口 ──
@app.get("/health")
def health():
    return {"status": "ok", "docs_count": rag.collection.count()}


@app.post("/search", response_model=SearchResponse)
def search(req: SearchRequest):
    """语义检索 + 阈值过滤"""
    start = time.time()
    results = rag.search(req.query, req.top_k)

    # 阈值过滤
    filtered = [r for r in results if r["distance"] <= req.max_distance]

    return SearchResponse(
        query=req.query,
        results=[SearchResult(**r) for r in filtered],
        elapsed_ms=(time.time() - start) * 1000,
    )
```

---

## 第三步：元数据过滤 + MMR 接口（30min）

```python
class FilterSearchRequest(SearchRequest):
    category: Optional[str] = Field(None, description="按类别过滤，如 数据库/安全")
    file: Optional[str] = Field(None, description="按来源文件过滤")


@app.post("/search/filter", response_model=SearchResponse)
def search_filter(req: FilterSearchRequest):
    """带元数据过滤的检索"""
    # 组装 where 条件
    where = {}
    if req.category:
        where["category"] = req.category
    if req.file:
        where["file"] = req.file

    query_vec = rag._embed(req.query)
    results = rag.collection.query(
        query_embeddings=[query_vec],
        n_results=req.top_k,
        where=where or None,  # 无过滤时传 None
        include=["documents", "metadatas", "distances"]
    )
    # ... 格式化返回
```

```python
class MMRSearchRequest(SearchRequest):
    lambda_: float = Field(0.7, ge=0, le=1, description="MMR 平衡参数")


@app.post("/search/mmr", response_model=SearchResponse)
def search_mmr(req: MMRSearchRequest):
    """MMR 多样性检索"""
    # 1. 先取 Top-20 候选
    candidates = rag.search(req.query, top_k=20)
    # 2. 用 MMR 重排，选 Top-K
    query_vec = rag._embed(req.query)
    cand_vecs = [rag._embed(c["content"]) for c in candidates]  # 或从 Chroma 拿向量
    selected = mmr_select(query_vec, cand_vecs, k=req.top_k, lambda_=req.lambda_)
    # 3. 返回选中的结果
    ...
```

> 提示：`rag.collection.get(ids=...)` 可以直接拿向量，避免重复调用 embedding API。

---

## 第四步：测试（30min）

```bash
# 启动服务
uvicorn api.rag_service:app --reload --port 8000

# 健康检查
curl http://localhost:8000/health

# 检索测试
curl -X POST http://localhost:8000/search \
  -H "Content-Type: application/json" \
  -d '{"query": "如何防止 SQL 注入", "top_k": 3}'

# 浏览器访问 Swagger 文档
# http://localhost:8000/docs
```

---

## 第五步：综合验证（40min）

1. 用 5 个查询测试 `/search`，验证返回格式
2. 测试 `/search/filter`（带类别过滤）
3. 测试 `/search/mmr`（对比普通检索的多样性）
4. 测试边界：空查询、超长查询、不存在的过滤条件
5. 记录响应时间，观察是否可接受

---

## 检验标准

1. **默写**：FastAPI 路由 + Pydantic 模型 + 调 RAG 检索的完整结构
2. **一句话**："把检索能力包成 HTTP 接口，前端/机器人通过 POST JSON 就能检索知识库"
3. **追问**：服务启动时怎么初始化 Chroma？（在模块级创建 RAGPipeline 实例，启动即加载）
4. **追问**：检索慢怎么优化？（向量化缓存、ANN 索引参数、结果缓存）

---

## 产出文件

`api/rag_service.py` — FastAPI 知识库检索服务

---

## 实验结果（2026-08-15 实测）

运行 `uvicorn api.rag_service:app --port 8000`，5 个端点全部测试通过。

### ⚠️ 计划文档与现状的偏差（实现时已对齐）

计划是按 Day34 旧 `RAGPipeline`（nomic-embed-text + L2 距离）写的，但 Day36-39 已迭代到 **bge-m3 + `kb_bge` 集合 + cosine 空间**。服务直接复用 Day38 选型结论的检索链路，不引旧代码：

- 检索：`embedding_compare.py` 的 `get_embedding_bge` + 常驻 `kb_bge` 集合（23 分片）
- 距离：Chroma 余弦距离（范围 0~2，越小越相关）；计划里的 `max_distance=350` 是 L2 距离的误用

### 各端点测试结果

| 端点 | 结果 |
|------|------|
| `GET /health` | `{"status":"ok","collection":"kb_bge","docs_count":23}` |
| `GET /collections` | 6 个集合，维度正确（kb_nomic 768 / kb_bge 1024 / kb_zhipu_2048 2048） |
| `POST /search` | "如何防止 SQL 注入" → Top-1 `sql-best-practices.md` dist=0.31 ✅ |
| `POST /search/filter` | 按 `file` 过滤只在目标文件里检索；无匹配返回空 + `note` 提示 |
| `POST /search/mmr` | λ=0.3 覆盖 5 个文件（普通只有 4 个），λ=1 退化普通 Top-K |
| 参数校验 | 空查询/超长(>200)/top_k 越界/lambda 越界/无 body 全部返回 422 + 明确信息 |

### 关键发现

**1. kb_bge 余弦距离分布（决定阈值）**

实测"如何防止 SQL 注入 / API 接口设计 / 日志排查"等查询：

- **强相关**：0.28 ~ 0.42
- **弱相关**：0.47 ~ 0.54
- **明显不相关**：> 0.6

默认阈值定为 **0.6**（过滤明显不相关，保留弱相关）。先试过 0.8 太松（几乎不过滤）。

**2. MMR lambda 验证（λ 越大越相关，越小越多样）**

查询"API 接口设计有什么规范"，普通 Top-5 覆盖 4 个文件（api-design 占 2 条）：

| 方法 | 文件覆盖 | 观察 |
|------|---------|------|
| 普通 Top-5 | 4 个 | api-design 占 2 条（dist 0.284 + 0.374） |
| MMR λ=1.0 | 4 个 | 退化普通检索（多样权重=0） |
| MMR λ=0.7 | 4 个 | 相关为主，尾部换入不同文件 |
| MMR λ=0.3 | **5 个** | api-design 只留 1 条，其余全来自不同文件 |

**3. 实现要点**

- **MMR 候选向量不重复调 embedding API**：query 结果不带向量，用 `collection.get(ids=..., include=["embeddings"])` 一次取回候选向量（计划文档第 3 步提示的优化点）。
- **⚠️ `collection.get(ids=...)` 不保证返回顺序**（Day40 审查 S1，已修复）：Chroma 按插入序返回，与传入 ids 顺序可能错位（实测 8/10）。必须 `dict(zip(ids, embeddings))` 按 id 对齐到 candidates 顺序，否则 MMR 在错位向量上计算。修复后重测 λ 实验，结论不变（λ=1/0.7/0.3 → 4/4/5 文件覆盖）。
- **服务启动预热**：FastAPI `lifespan` 里调一次 `get_embedding_bge("预热")`，bge-m3 首次加载进内存 20s+，预热后首个请求不再卡顿。

**4. `/ask` 端点：RAG 问答闭环（Day40 延伸）**

`POST /ask`：检索 Top-K 片段 → 拼 prompt → ollama 本地生成回答，返回 `{answer, sources, empty}`。

- prompt 结构复用 Day34 `rag_pipeline.generate`（"只能根据知识库片段回答，不要编造"），已验证
- 默认生成模型 `qwen2.5:3b`（ollama 本地免费、无需 key，可传 `model` 换 `qwen3:8b` 等）；⚠️ 不用 `deepseek-r1:7b`——此环境未安装，且推理模型先输出冗长思考、生成慢
- 无相关片段（阈值过滤后为空）**不调 LLM**，直接返回 `empty=true` + "知识库中没有相关信息"
- LLM 调用失败（ollama 未启动/模型不存在）→ 503 + 明确提示
- 实测："如何防止 SQL 注入" → 回答"永远使用参数化查询…"并引用 `sql-best-practices.md`；首次调用 35s（模型加载），模型驻留后 8.8s

### 结论

检索能力已封装为 HTTP 服务，前端/机器人通过 POST JSON 即可检索知识库（`/docs` 有 Swagger 文档）。阈值 0.6、MMR λ=0.7 为推荐默认，可作查询参数随时调整。`/ask` 已补上"检索 + 生成"的完整 RAG 问答闭环，服务共 6 端点。下一步 Day 41（Week6 复盘）。
