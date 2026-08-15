"""
Day40：知识库检索服务（FastAPI + RAG）

把 Day36-39 的检索能力封装成 HTTP 接口，任何客户端（前端/机器人/命令行）
通过 POST JSON 就能检索知识库，不需要知道 Chroma/embedding 细节。

端点一览：
  GET  /health          健康检查 + 集合计数
  GET  /collections     查看知识库集合信息（名称/条数/维度）
  POST /search          语义检索（Top-K + 余弦距离阈值）
  POST /search/filter   带元数据过滤的检索（按 file）
  POST /search/mmr      MMR 多样性检索（结果覆盖更多主题）
  POST /ask             RAG 问答：检索片段 + 本地 LLM 生成回答（Day40 延伸）

复用 Day38 选型结论：bge-m3（本地、免费、中文强）+ kb_bge 集合（cosine 空间）。
⚠️ 建库：先跑 `python rag/embedding_compare.py --rebuild` 生成 kb_bge 集合。

启动：
  uvicorn api.rag_service:app --reload --port 8000
接口文档（Swagger）：
  http://localhost:8000/docs

⚠️ 线程安全约束：chromadb PersistentClient 非线程安全，本服务为单 worker + 低并发学习场景。
   若对外提供高并发服务，需对 collection 操作加 threading.Lock（Day40 审查 G1）。
"""

from __future__ import annotations

import os
import sys
import time
from contextlib import asynccontextmanager

import numpy as np
import chromadb
import requests
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, ConfigDict, Field

# Windows 控制台默认 GBK，强制 UTF-8（服务日志中文不乱码）
if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8")

# 添加父目录到路径，方便导入 rag 包（api/ 的父目录 = PythonStudy/）
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from rag.embedding_compare import get_embedding_bge, CHROMA_PATH


# ══════════════════════════════════════════════════════════
# 配置
# ══════════════════════════════════════════════════════════

COLLECTION_NAME = "kb_bge"      # Day38 选型：bge-m3（本地、免费、中文强）
DEFAULT_TOP_K = 3               # 默认返回条数
# 余弦距离阈值（范围 0~2，越小越相关），超过丢弃。
# 实测 kb_bge 距离分布（Day40）：强相关 0.28~0.42，弱相关 0.47~0.54，明显不相关 >0.6。
# 取 0.6 折中：保留弱相关、过滤明显不相关。之前试过 0.8 太松（不过滤）。
DEFAULT_MAX_DISTANCE = 0.6

# /ask 问答用的生成模型（ollama 本地，免费无需 key）
# qwen2.5:3b 轻快够用；想要更聪明可换 qwen3:8b / phi4-mini（ollama 已安装）。
# ⚠️ 不用 deepseek-r1:7b：此环境未安装，且推理模型会先输出冗长思考、生成慢。
LLM_MODEL = "qwen2.5:3b"
LLM_MAX_TOKENS = 500          # 回答最大 token 数（快模型足够；太大反而拖慢）

# /ask 允许的生成模型白名单（防外部任意指定模型名；uvicorn 默认绑 127.0.0.1，仍预留校验，Day40 审查 G2）
ALLOWED_LLM_MODELS = {"qwen2.5:3b", "qwen3:8b", "qwen3:4b", "phi4-mini", "gemma3:4b"}

# 服务启动时加载集合（只连不建；缺失时给出清晰提示，不静默建空库）
try:
    client = chromadb.PersistentClient(path=CHROMA_PATH)
    collection = client.get_collection(name=COLLECTION_NAME)
    print(f"[服务] 已加载集合 {COLLECTION_NAME}（{collection.count()} 条分片）")
except Exception:
    collection = None
    client = None
    print(f"[服务] ⚠️ 集合 {COLLECTION_NAME} 不可用（chroma 路径异常或集合不存在）！")
    print(f"[服务]    请确认 CHROMA_PATH 正确，并先运行: python rag/embedding_compare.py --rebuild")


# ══════════════════════════════════════════════════════════
# 请求/响应模型
# ══════════════════════════════════════════════════════════

class SearchRequest(BaseModel):
    """通用检索请求：query + top_k + 阈值"""

    query: str = Field(..., min_length=1, max_length=200, description="查询内容")
    top_k: int = Field(DEFAULT_TOP_K, ge=1, le=20, description="返回条数")
    max_distance: float | None = Field(
        DEFAULT_MAX_DISTANCE, ge=0, le=2,
        description="余弦距离阈值，超过该值视为不相关丢弃；传 null 表示不过滤",
    )


class FilterSearchRequest(SearchRequest):
    """带元数据过滤的检索请求"""

    file: str | None = Field(None, description="按来源文件过滤，如 sql-best-practices.md")
    category: str | None = Field(None, description="按类别过滤（kb_bge 集合无此字段时无匹配）")


class MMRSearchRequest(SearchRequest):
    """MMR 多样性检索请求。请求体里用 lambda 字段（如 {\"lambda\": 0.7}）"""

    model_config = ConfigDict(populate_by_name=True)

    lambda_: float = Field(
        0.7, ge=0, le=1, alias="lambda",
        description="MMR 平衡参数：越大越看重相关，越小越看重多样",
    )
    candidate_pool: int = Field(
        20, ge=1, le=50,
        description="候选池大小：先取 Top-N 候选再 MMR 重排（越大越耗时）",
    )


class SearchResult(BaseModel):
    """单条检索结果"""

    file: str                       # 来源文件，如 sql-best-practices.md
    chunk_index: int                # 文件内分片序号
    distance: float                 # 余弦距离（越小越相关）
    content: str                    # 片段正文


class SearchResponse(BaseModel):
    """统一响应结构"""

    query: str
    count: int
    results: list[SearchResult]
    elapsed_ms: float
    note: str | None = None         # 附加说明（如过滤无匹配时提示）


class AskRequest(SearchRequest):
    """RAG 问答请求：检索 + LLM 生成回答"""

    model: str = Field(LLM_MODEL, description="生成模型（ollama 本地模型名）")
    max_tokens: int = Field(LLM_MAX_TOKENS, ge=50, le=2000, description="回答最大 token 数")


class AskSource(BaseModel):
    """回答引用的知识库片段（供前端展示来源）"""

    file: str
    chunk_index: int
    distance: float


class AskResponse(BaseModel):
    """RAG 问答响应"""

    query: str
    answer: str                  # LLM 生成的回答
    sources: list[AskSource]     # 引用的片段
    empty: bool                  # 知识库无相关内容时为 True
    elapsed_ms: float


# ══════════════════════════════════════════════════════════
# FastAPI 应用
# ══════════════════════════════════════════════════════════

@asynccontextmanager
async def lifespan(app: FastAPI):
    """服务启动时预热 embedding 模型，避免首个请求卡在模型加载上"""
    if collection is not None:
        try:
            get_embedding_bge("预热")
            print("[服务] bge-m3 模型预热完成（首个请求不再卡加载）")
        except Exception as e:
            print(f"[服务] 预热失败，首次检索可能较慢: {e}")
    yield


app = FastAPI(
    title="知识库检索服务",
    description="基于 bge-m3 + Chroma 的 RAG 检索接口（Day40）",
    version="0.1.0",
    lifespan=lifespan,
)


# ══════════════════════════════════════════════════════════
# 内部工具
# ══════════════════════════════════════════════════════════

def _ensure_ready():
    """集合未初始化时抛 503，避免空指针"""
    if collection is None:
        raise HTTPException(status_code=503, detail=f"集合 {COLLECTION_NAME} 未初始化，请先运行建库脚本")


def _query_collection(query_vec: list[float], top_k: int, where: dict | None = None) -> list[dict]:
    """执行检索，返回 [{id, file, chunk_index, distance, content}, ...]

    内部 id 用于 MMR 取候选向量，不出现在对外响应里。
    """
    results = collection.query(
        query_embeddings=[query_vec],
        n_results=top_k,
        where=where,
        include=["documents", "metadatas", "distances"],
    )
    out = []
    for i in range(len(results["ids"][0])):
        out.append({
            "id": results["ids"][0][i],
            "file": results["metadatas"][0][i]["file"],
            "chunk_index": results["metadatas"][0][i]["chunk_index"],
            "distance": results["distances"][0][i],
            "content": results["documents"][0][i],
        })
    return out


def _filter_by_distance(results: list[dict], max_distance: float | None) -> list[dict]:
    """余弦距离阈值过滤（distance 越小越相关）"""
    if max_distance is None:
        return results
    return [r for r in results if r["distance"] <= max_distance]


def _call_llm(prompt: str, model: str = LLM_MODEL, max_tokens: int = LLM_MAX_TOKENS) -> str:
    """调用 ollama 生成模型，返回回答文本（/ask 用）"""
    resp = requests.post(
        "http://localhost:11434/api/generate",
        json={"model": model, "prompt": prompt, "stream": False,
              "options": {"num_predict": max_tokens}},
        timeout=120,
    )
    resp.raise_for_status()
    return resp.json()["response"]


def mmr_select(query_vec: list[float], candidate_vecs: list[list[float]],
               k: int = 3, lambda_: float = 0.7) -> list[int]:
    """Day37 MMR：贪心选 K 个"既相关又不重复"的候选，返回下标列表

    MMR = λ × 相关性 - (1-λ) × 冗余度
    冗余度取"与已选集合的最大相似度"（取最大才能真正防重复）。
    """
    q = np.array(query_vec, dtype=float)
    cand = np.array(candidate_vecs, dtype=float)

    # 预计算：候选与查询的相关性、候选两两之间的相似度（均用余弦相似度）
    q_norm = q / (np.linalg.norm(q) + 1e-8)
    cand_norm = cand / (np.linalg.norm(cand, axis=1, keepdims=True) + 1e-8)
    sim_query = cand_norm @ q_norm
    sim_matrix = cand_norm @ cand_norm.T

    selected: list[int] = []
    remaining = list(range(len(candidate_vecs)))

    for _ in range(min(k, len(candidate_vecs))):
        best_idx: int | None = None
        best_score = float("-inf")   # -1 是余弦相似度理论下界，用 -inf 更稳健（Day40 审查 A1）
        for i in remaining:
            relevance = float(sim_query[i])
            # 冗余度 = 与已选集合中相似度最大的那个（没有已选时为 0）
            max_dup = max(sim_matrix[i][j] for j in selected) if selected else 0.0
            score = lambda_ * relevance - (1 - lambda_) * max_dup
            if score > best_score:
                best_score, best_idx = score, i
        selected.append(best_idx)
        remaining.remove(best_idx)

    return selected


# ══════════════════════════════════════════════════════════
# 接口
# ══════════════════════════════════════════════════════════

@app.get("/health", tags=["基础"])
def health() -> dict:
    """健康检查：服务 + 集合状态"""
    _ensure_ready()
    return {
        "status": "ok",
        "collection": COLLECTION_NAME,
        "docs_count": collection.count(),
        "embedding_model": "bge-m3",
    }


@app.get("/collections", tags=["基础"])
def list_collections() -> dict:
    """列出 Chroma 里所有知识库集合（名称/条数/向量维度）"""
    cols = []
    for c in client.list_collections():
        dim = 0
        try:
            sample = c.get(limit=1, include=["embeddings"])
            if sample["ids"]:
                dim = len(sample["embeddings"][0])
        except Exception:
            pass
        cols.append({"name": c.name, "count": c.count(), "dimension": dim})
    return {"collections": cols}


@app.post("/search", response_model=SearchResponse, tags=["检索"])
def search(req: SearchRequest) -> SearchResponse:
    """语义检索：bge-m3 向量化查询 → 余弦相似 → 距离阈值过滤"""
    _ensure_ready()
    start = time.perf_counter()

    results = _query_collection(_embed_query(req.query), req.top_k)
    results = _filter_by_distance(results, req.max_distance)

    return SearchResponse(
        query=req.query,
        count=len(results),
        results=[SearchResult(**r) for r in results],
        elapsed_ms=(time.perf_counter() - start) * 1000,
    )


@app.post("/search/filter", response_model=SearchResponse, tags=["检索"])
def search_filter(req: FilterSearchRequest) -> SearchResponse:
    """带元数据过滤的检索：where 先在元数据上缩小范围，再做相似匹配

    过滤太严无匹配 → 返回空结果并给出 note（不静默降级，调用方自己决定是否重试）。
    """
    _ensure_ready()

    where: dict = {}
    if req.file:
        where["file"] = req.file
    if req.category:
        where["category"] = req.category    # kb_bge 无 category 字段 → 该条件下无匹配

    start = time.perf_counter()
    results = _query_collection(_embed_query(req.query), req.top_k, where=where or None)

    note = None
    if not results and where:
        note = "过滤条件无匹配，已返回空。如需全库检索，请去掉 file/category 参数"

    results = _filter_by_distance(results, req.max_distance)

    return SearchResponse(
        query=req.query,
        count=len(results),
        results=[SearchResult(**r) for r in results],
        elapsed_ms=(time.perf_counter() - start) * 1000,
        note=note,
    )


@app.post("/search/mmr", response_model=SearchResponse, tags=["检索"])
def search_mmr(req: MMRSearchRequest) -> SearchResponse:
    """MMR 多样性检索：先取大候选池，再按"相关且不重复"重排选 Top-K

    解决普通 Top-K 全是同一文件、内容重复的问题。
    λ 越大越相关（λ=1 退化普通 Top-K），越小越多样。
    """
    _ensure_ready()
    start = time.perf_counter()

    query_vec = _embed_query(req.query)

    # 1. 先取大候选池（普通余弦检索，多取点给 MMR 挑）
    candidates = _query_collection(query_vec, req.candidate_pool)

    # 2. 用 ids 取候选向量（query 结果不带向量，必须重新 get；
    #    这一步避免对每个候选再调一遍 embedding API，Day40 提示的优化点）
    # ⚠️ Chroma 的 get(ids=...) 不保证返回顺序与传入 ids 一致（实测按插入序返回），
    #    必须用 id 映射对齐到 candidates 顺序，否则 MMR 会在错位向量上计算（Day40 审查 S1）
    fetched = collection.get(ids=[c["id"] for c in candidates], include=["embeddings"])
    vec_by_id = dict(zip(fetched["ids"], fetched["embeddings"]))
    candidate_vecs = [vec_by_id[c["id"]] for c in candidates]

    # 3. MMR 重排，选前 top_k
    selected_idx = mmr_select(query_vec, candidate_vecs, k=req.top_k, lambda_=req.lambda_)
    results = [candidates[i] for i in selected_idx]

    results = _filter_by_distance(results, req.max_distance)

    return SearchResponse(
        query=req.query,
        count=len(results),
        results=[SearchResult(**r) for r in results],
        elapsed_ms=(time.perf_counter() - start) * 1000,
    )


@app.post("/ask", response_model=AskResponse, tags=["问答"])
def ask(req: AskRequest) -> AskResponse:
    """RAG 问答：检索知识库片段 → 拼 prompt → 本地 LLM 生成回答（Day40 延伸）

    回答严格基于检索到的片段，不编造；片段不足时 LLM 会说明无相关信息。
    返回 sources（引用片段）供前端展示来源，回答格式由生成模型决定。
    """
    _ensure_ready()
    # /ask 允许的生成模型白名单校验（防外部指定任意模型名，Day40 审查 G2）
    if req.model not in ALLOWED_LLM_MODELS:
        raise HTTPException(
            status_code=422,
            detail=f"model 不在白名单内，可用: {sorted(ALLOWED_LLM_MODELS)}",
        )
    start = time.perf_counter()

    # 1. 检索（复用 /search 链路：余弦相似 + 距离阈值）
    results = _query_collection(_embed_query(req.query), req.top_k)
    results = _filter_by_distance(results, req.max_distance)

    # 2. 无相关片段 → 直接返回空回答，不调 LLM
    if not results:
        return AskResponse(
            query=req.query,
            answer="知识库中没有相关信息。",
            sources=[], empty=True,
            elapsed_ms=(time.perf_counter() - start) * 1000,
        )

    # 3. 拼上下文 + 生成（prompt 结构与 Day34 rag_pipeline.generate 一致，已验证）
    context_text = "\n\n".join(
        f"[来源: {r['file']}]\n{r['content'][:400]}" for r in results
    )
    prompt = (
        "你是基于知识库回答问题的助手。\n"
        "只能根据下面提供的知识库片段回答，不要编造。"
        "如果片段不足以回答，就说'知识库中没有相关信息'。\n\n"
        f"【知识库片段】\n{context_text}\n\n"
        f"【问题】{req.query}\n"
        f"【回答】"
    )
    try:
        answer = _call_llm(prompt, req.model, req.max_tokens)
    except Exception as e:
        # LLM 调用失败（ollama 未启动 / 模型不存在）→ 503 + 明确提示
        raise HTTPException(
            status_code=503,
            detail=f"生成模型调用失败（model={req.model}），请确认 ollama 已启动且模型已安装: {e}",
        )

    return AskResponse(
        query=req.query,
        answer=answer,
        sources=[{"file": r["file"], "chunk_index": r["chunk_index"], "distance": r["distance"]}
                 for r in results],
        empty=False,
        elapsed_ms=(time.perf_counter() - start) * 1000,
    )


# ── 小工具：查询向量化 ──

def _embed_query(query: str) -> list[float]:
    """查询向量化（bge-m3）"""
    return get_embedding_bge(query)
