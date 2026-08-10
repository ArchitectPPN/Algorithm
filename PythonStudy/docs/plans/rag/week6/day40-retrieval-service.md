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
