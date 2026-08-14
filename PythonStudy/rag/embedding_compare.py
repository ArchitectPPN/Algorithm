"""
Day38：Embedding 模型对比实验

用同一批"已知答案"的查询，对比不同 embedding 模型的检索效果，用数据决定选型。

参与对比的模型：
  1. nomic-embed-text  —— 本地 Ollama，768 维，通用（中文一般）
  2. bge-m3            —— 本地 Ollama，1024 维，多语言（中文强）
  3. zhipu embedding-3 —— 云 API，2048 维，中文强，按量收费

评估方法（Day38 核心思路）：
  同一批查询，用每个模型分别建集合 → 检索 → 看"期望命中的文件"是否出现在 Top-3 里。

⚠️ 关键约束：不同模型的向量不在同一空间，必须【每个模型各建一个集合】，不能混用。

用法：
  python embedding_compare.py              # 对比全部有 key 的模型（nomic + bge 必跑，zhipu 看环境变量）
  python embedding_compare.py --rebuild    # 强制重建所有集合（默认已有数据则跳过）

环境变量（智谱必填）：
  PowerShell: $env:ZHIPU_API_KEY="你的key"
  bash:       export ZHIPU_API_KEY="你的key"
"""

from __future__ import annotations

import os
import sys
import time
from functools import partial
import requests
import chromadb

# Windows 控制台默认 GBK，强制 UTF-8 输出避免中文乱码（Python 3.7+）
if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8")

# 添加父目录到路径，方便导入 document_loader
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from rag.document_loader import load_all_docs, chunk_all_docs


# ══════════════════════════════════════════════════════════
# 配置
# ══════════════════════════════════════════════════════════

# 路径（与 query_demo.py / rag_pipeline.py 保持一致）
base_dir = os.path.dirname(__file__)
CHROMA_PATH = os.path.join(base_dir, "..", "chroma_data")
KNOWLEDGE_DIR = os.path.join(base_dir, "..", "data", "knowledge")

CHUNK_SIZE = 500       # 分片大小（沿用 Day34 结论）
CHUNK_OVERLAP = 50     # 分片重叠
TOP_K = 3              # 命中判定：期望文件是否出现在 Top-3

# ══════════════════════════════════════════════════════════
# ⚠️ 智谱 API Key
# 三种填法，按优先级：
#   1. 下面直接填（最简单，但 ⚠️ 填了就别 git commit 这个文件！key 会泄露）
#   2. 环境变量 ZHIPU_API_KEY（PowerShell: $env:ZHIPU_API_KEY="xxx"）
#   3. PythonStudy/.env 文件里写 ZHIPU_API_KEY=xxx（推荐，且 .env 已被 gitignore）
# 优先取顺序：下面常量 > 环境变量 > .env
# ══════════════════════════════════════════════════════════
ZHIPU_API_KEY = ""


# ── .env 加载（不引入 python-dotenv，简单解析 KEY=VALUE）──
def load_env(env_path: str) -> dict:
    """读取 .env 文件为 dict（忽略空行和 # 注释），失败返回空 dict"""
    env = {}
    try:
        with open(env_path, encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith("#") or "=" not in line:
                    continue
                key, _, value = line.partition("=")
                env[key.strip()] = value.strip().strip('"').strip("'")
    except FileNotFoundError:
        pass
    return env


# 最终生效的 key：常量 > 环境变量 > .env（.env 在 PythonStudy/ 下）
if not ZHIPU_API_KEY:
    _env = load_env(os.path.join(base_dir, "..", ".env"))
    ZHIPU_API_KEY = os.environ.get("ZHIPU_API_KEY") or _env.get("ZHIPU_API_KEY", "")


# ══════════════════════════════════════════════════════════
# 三个模型的 embedding 函数
# ══════════════════════════════════════════════════════════

# 首次调用 ollama 要加载模型进内存，可能较慢，timeout 给足 120s
OLLAMA_TIMEOUT = 120


def get_embedding_nomic(text: str) -> list[float]:
    """调用 ollama 获取 nomic-embed-text 的 embedding（768 维）"""
    resp = requests.post(
        "http://localhost:11434/api/embeddings",
        json={"model": "nomic-embed-text", "prompt": text},
        timeout=OLLAMA_TIMEOUT
    )
    resp.raise_for_status()
    return resp.json()["embedding"]


def get_embedding_bge(text: str) -> list[float]:
    """调用 ollama 获取 bge-m3 的 embedding（1024 维，多语言）"""
    resp = requests.post(
        "http://localhost:11434/api/embeddings",
        json={"model": "bge-m3", "prompt": text},
        timeout=OLLAMA_TIMEOUT
    )
    resp.raise_for_status()
    return resp.json()["embedding"]


def get_embedding_zhipu(text: str, dimensions: int | None = None) -> list[float]:
    """调用智谱 embedding-3 获取单条文本的向量

    Args:
        text: 输入文本
        dimensions: 目标维度。embedding-3 默认 2048 维，
                    传 1024 可降维（省存储/算力，但信息量少）。
                    返回结构是 resp.data[0].embedding（和通义的 resp.output[...] 不一样）。
    """
    from zhipuai import ZhipuAI
    client = ZhipuAI(api_key=ZHIPU_API_KEY)
    kwargs = {"input": text}
    if dimensions is not None:
        kwargs["dimensions"] = dimensions
    resp = client.embeddings.create(model="embedding-3", **kwargs)
    return resp.data[0].embedding


def get_embedding_zhipu_batch(texts: list[str], dimensions: int | None = None) -> list[list[float]]:
    """智谱批量向量化（比逐条调 API 快得多，建库时用）

    Args:
        texts: 输入文本列表
        dimensions: 目标维度（同 get_embedding_zhipu，不传则默认 2048）

    返回：[[...], [...]]，第 i 个向量对应 texts[i]
    """
    from zhipuai import ZhipuAI
    client = ZhipuAI(api_key=ZHIPU_API_KEY)
    kwargs = {"input": texts}
    if dimensions is not None:
        kwargs["dimensions"] = dimensions
    resp = client.embeddings.create(model="embedding-3", **kwargs)
    return [item.embedding for item in resp.data]


# ══════════════════════════════════════════════════════════
# 模型注册表：模型 → 向量化函数 + 集合名 + 属性
# ══════════════════════════════════════════════════════════

EMBED_MODELS = {
    "nomic": {
        "embed_fn": get_embedding_nomic,
        "batch_fn": None,                        # 本地模型逐条即可
        "collection_name": "kb_nomic",
        "source": "本地 Ollama",
        "cost": "免费",
    },
    "bge": {
        "embed_fn": get_embedding_bge,
        "batch_fn": None,
        "collection_name": "kb_bge",
        "source": "本地 Ollama",
        "cost": "免费",
    },
    "zhipu-1024": {
        "embed_fn": partial(get_embedding_zhipu, dimensions=1024),
        "batch_fn": partial(get_embedding_zhipu_batch, dimensions=1024),  # 降维版
        "collection_name": "kb_zhipu_1024",
        "source": "云 API（智谱）",
        "cost": "按量收费（约 0.5 元/百万 tokens，以官方账单为准）",
        "note": "embedding-3 降维到 1024",
    },
    "zhipu-2048": {
        "embed_fn": partial(get_embedding_zhipu, dimensions=None),
        "batch_fn": partial(get_embedding_zhipu_batch, dimensions=None),  # 默认全维度
        "collection_name": "kb_zhipu_2048",
        "source": "云 API（智谱）",
        "cost": "按量收费（约 0.5 元/百万 tokens，以官方账单为准）",
        "note": "embedding-3 默认 2048",
    },
}


# ══════════════════════════════════════════════════════════
# 工具：计时器
# ══════════════════════════════════════════════════════════

class Timer:
    """极简计时器：with Timer() as t: ... 然后 t.elapsed 取秒"""

    def __enter__(self):
        self.start = time.perf_counter()
        return self

    def __exit__(self, *args):
        self.elapsed = time.perf_counter() - self.start


# ══════════════════════════════════════════════════════════
# 建集合（每个模型各建一个，用对应模型向量化，不能混用）
# ══════════════════════════════════════════════════════════

def build_collection(model_key: str, rebuild: bool = False) -> dict:
    """用指定模型向量化知识库并建集合

    Args:
        model_key: EMBED_MODELS 的 key（"nomic" / "bge" / "zhipu"）
        rebuild: 是否强制重建（删掉旧数据重新向量化）

    Returns:
        {"collection", "build_time", "chunk_count", "total_tokens"}
    """
    spec = EMBED_MODELS[model_key]
    client = chromadb.PersistentClient(path=CHROMA_PATH)
    name = spec["collection_name"]

    if rebuild:
        try:
            client.delete_collection(name)
        except Exception:
            pass

    collection = client.get_or_create_collection(
        name=name,
        metadata={"hnsw:space": "cosine"}  # RAG 场景推荐余弦（Day33 结论）
    )

    # 已有数据 → 跳过向量化（除非 --rebuild）
    if collection.count() > 0:
        print(f"  [跳过] {model_key}: 集合已有 {collection.count()} 条，如需重建加 --rebuild")
        return {
            "collection": collection,
            "build_time": 0.0,
            "chunk_count": collection.count(),
            "total_tokens": 0,
        }

    # 加载 + 分片
    docs = load_all_docs(KNOWLEDGE_DIR)
    chunks = chunk_all_docs(docs, chunk_size=CHUNK_SIZE, chunk_overlap=CHUNK_OVERLAP)
    total_tokens = sum(c["token_estimate"] for c in chunks)
    print(f"  [{model_key}] 加载 {len(docs)} 个文档，分片 {len(chunks)} 条，约 {total_tokens} tokens")

    # 向量化（云 API 走真批量，本地逐条）+ 插入
    # ⚠️ 预热：本地模型首次调用要加载进内存，先调一次，避免主循环第一条就卡超时
    print(f"    [预热] {model_key}: 加载模型进内存...")
    spec["embed_fn"]("预热")

    ids, embeddings, documents, metadatas = [], [], [], []
    BATCH_SIZE = 16  # 云 API 批量大小（每次调这么多条文本）

    def push(chunk: dict, embedding: list[float], idx: int):
        """把一条分片及其向量追加进平行列表"""
        ids.append(f"chunk_{idx}")
        embeddings.append(embedding)
        documents.append(chunk["content"])
        metadatas.append({
            "file": chunk["file"],
            "chunk_index": chunk["chunk_index"],
        })

    with Timer() as timer:
        if spec["batch_fn"] is not None:
            # 云 API 真批量：攒够 BATCH_SIZE 条再调一次，省 HTTP 开销
            pending_texts, pending_chunks = [], []
            for i, chunk in enumerate(chunks):
                pending_texts.append(chunk["content"])
                pending_chunks.append(chunk)
                if len(pending_texts) >= BATCH_SIZE:
                    for emb, c in zip(spec["batch_fn"](pending_texts), pending_chunks):
                        push(c, emb, len(ids))
                    pending_texts, pending_chunks = [], []
                    print(f"    已向量化 {i + 1}/{len(chunks)} 条")
            if pending_texts:  # 不足一批的尾巴
                for emb, c in zip(spec["batch_fn"](pending_texts), pending_chunks):
                    push(c, emb, len(ids))
        else:
            # 本地模型逐条
            for i, chunk in enumerate(chunks):
                push(chunk, spec["embed_fn"](chunk["content"]), len(ids))
                if (i + 1) % 5 == 0:
                    print(f"    已向量化 {i + 1}/{len(chunks)} 条")

        collection.add(ids=ids, embeddings=embeddings,
                       documents=documents, metadatas=metadatas)

    print(f"  [{model_key}] 插入完成：{collection.count()} 条，耗时 {timer.elapsed:.1f}s")
    return {
        "collection": collection,
        "build_time": timer.elapsed,
        "chunk_count": collection.count(),
        "total_tokens": total_tokens,
    }


# ══════════════════════════════════════════════════════════
# 评估模型：用"已知答案"的查询测检索准确率
# ══════════════════════════════════════════════════════════

def evaluate_model(model_key: str, collection, queries: list[dict]) -> dict:
    """用一组"已知答案"的查询评估某个 embedding 模型

    Args:
        model_key: EMBED_MODELS 的 key
        collection: 已用该模型建好的集合
        queries: [{"query": "...", "expected_file": "应该命中的文件"}, ...]

    Returns:
        {"accuracy", "hit_count", "query_time": 平均单条查询耗时}
    """
    embed_fn = EMBED_MODELS[model_key]["embed_fn"]
    correct = 0
    query_times = []

    print(f"\n  --- 评估 {model_key} ---")
    for item in queries:
        with Timer() as t:
            vec = embed_fn(item["query"])          # 用该模型向量化查询
            results = collection.query(
                query_embeddings=[vec],
                n_results=TOP_K,
                include=["metadatas", "distances"]
            )
        query_times.append(t.elapsed)

        hit_files = set(m["file"] for m in results["metadatas"][0])
        hit = item["expected_file"] in hit_files
        correct += int(hit)
        # 打印 Top-3 命中的文件 + 命中标记
        top_files = [m["file"] for m in results["metadatas"][0]]
        mark = "✅" if hit else "❌"
        print(f"    {mark} {item['query']}")
        print(f"      期望: {item['expected_file']} | Top-{TOP_K}: {top_files}")

    accuracy = correct / len(queries)
    print(f"  [{model_key}] 准确率 {accuracy:.0%} ({correct}/{len(queries)})，"
          f"单条查询平均 {sum(query_times)/len(query_times)*1000:.0f}ms")
    return {
        "accuracy": accuracy,
        "hit_count": correct,
        "avg_query_ms": sum(query_times) / len(query_times) * 1000,
    }


# ══════════════════════════════════════════════════════════
# 测试查询（已知答案，覆盖知识库 5 个文件）
# ══════════════════════════════════════════════════════════

TEST_QUERIES = [
    # ── 原有 5 条（简单直接）──
    {"query": "Python 变量怎么命名", "expected_file": "python-coding-style.md"},
    {"query": "如何防止 SQL 注入", "expected_file": "sql-best-practices.md"},
    {"query": "API 接口怎么设计", "expected_file": "api-design.md"},
    {"query": "Git 提交信息格式", "expected_file": "git-workflow.md"},
    {"query": "代码出错怎么处理", "expected_file": "error-handling.md"},
    # ── 新增 6 条（覆盖面更全）──
    {"query": "接口怎么加登录鉴权", "expected_file": "http-api-auth.md"},
    {"query": "前端怎么防 XSS 攻击", "expected_file": "frontend-security.md"},
    {"query": "日志怎么打才能快速排错", "expected_file": "log-best-practices.md"},
    {"query": "代码评审要看哪些点", "expected_file": "code-review-guide.md"},
    {"query": "缓存穿透怎么解决", "expected_file": "cache-design.md"},
    {"query": "上线前要检查什么", "expected_file": "deployment-checklist.md"},
    # ── 易混淆 4 条（考验检索区分度，故意跨文件）──
    # 期望文件是"唯一最相关"的那个，其他相似文档是干扰项
    {"query": "登录密码怎么安全存储", "expected_file": "sql-best-practices.md"},   # 干扰：http-api-auth.md（登录/鉴权）
    {"query": "请求太慢怎么排查", "expected_file": "log-best-practices.md"},       # 干扰：sql-best-practices.md（查询优化）、cache-design.md（缓存加速）
    {"query": "接口防重放怎么设计", "expected_file": "http-api-auth.md"},           # 干扰：api-design.md（接口设计）
    {"query": "上线后怎么知道服务出问题", "expected_file": "deployment-checklist.md"},  # 干扰：log-best-practices.md（监控/告警）
]


# ══════════════════════════════════════════════════════════
# 主流程
# ══════════════════════════════════════════════════════════

def main():
    rebuild = "--rebuild" in sys.argv

    print("=" * 60)
    print("Day38：Embedding 模型对比实验")
    print("=" * 60)

    # 确定参与对比的模型（zhipu 两个维度变体，都需要环境变量）
    active_models = []
    for key in ["nomic", "bge", "zhipu-1024", "zhipu-2048"]:
        if key.startswith("zhipu") and not ZHIPU_API_KEY:
            print(f"\n[跳过] {key}: 未设置 ZHIPU_API_KEY 环境变量")
            print("        PowerShell: $env:ZHIPU_API_KEY=\"你的key\"")
            print("        bash: export ZHIPU_API_KEY=\"你的key\"")
            continue
        active_models.append(key)

    if not active_models:
        print("没有可对比的模型，退出。")
        return

    # 每个模型建集合并评估（⚠️ 每个集合的向量必须由对应模型生成，不能混用）
    results = {}
    for key in active_models:
        print(f"\n{'─' * 60}")
        print(f"建集合: {key} → {EMBED_MODELS[key]['collection_name']}")
        print(f"{'─' * 60}")
        built = build_collection(key, rebuild=rebuild)
        ev = evaluate_model(key, built["collection"], TEST_QUERIES)
        results[key] = {**built, **ev}

    # ═══ 汇总对比表 ═══
    print("\n" + "=" * 60)
    print("对比汇总")
    print("=" * 60)
    print(f"{'模型':<12}{'来源':<14}{'准确率':<9}{'建库耗时':<10}{'平均查询':<10}{'维度':<7}{'备注'}")
    print("-" * 80)
    for key, r in results.items():
        # 用一次向量化拿维度（任意文本即可）
        dim = len(EMBED_MODELS[key]["embed_fn"]("测"))
        note = EMBED_MODELS[key].get("note", "")
        print(f"{key:<12}{EMBED_MODELS[key]['source']:<14}"
              f"{r['accuracy']:.0%}{'':<5}"
              f"{r['build_time']:.1f}s{'':<6}"
              f"{r['avg_query_ms']:.0f}ms{'':<6}"
              f"{dim:<7}{note}")

    # ═══ 结论 ═══
    print("\n" + "=" * 60)
    print("结论")
    print("=" * 60)
    best = max(results, key=lambda k: results[k]["accuracy"])
    print(f"  准确率最高: {best} ({results[best]['accuracy']:.0%})")
    print()
    print("  选型参考：")
    print("  - 中文效果：bge-m3 / zhipu 明显强于 nomic（中文专用/多语言 vs 通用）")
    print("  - 本地 vs 云：本地免费无延迟；云 API 质量高但花钱、依赖网络")
    print("  - 维度：nomic 768 < bge-m3 1024 < zhipu 2048，维度越高信息越丰富但存储/算力成本越高")
    print("  - 数据敏感/离线 → 本地（bge-m3）；追求极致效果且有钱 → 云（zhipu）")
    print("  - 本项目建议：知识库不大、中文为主 → 优先 bge-m3（本地、免费、中文强）")


if __name__ == "__main__":
    main()
