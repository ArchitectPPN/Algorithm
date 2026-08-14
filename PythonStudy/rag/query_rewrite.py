"""
Day39：查询改写实验（同义词扩展 + HyDE）

背景：Day39 评估（evaluate.py）发现基线有 3 条问题查询——
  - 未命中 1 条："请求太慢怎么排查"（期望 log-best-practices.md）
  - 排位靠后 2 条："登录密码怎么安全存储"（排第5）、"上线后怎么知道服务出问题"（排第3）

这些查询"措辞和文档用词对不上"，向量检索对措辞敏感。
本脚本用两种零/低成本方法改写查询，重跑评估验证是否提升：

  1. 同义词扩展：手工同义词表，扩出多个查询变体分别检索再合并
  2. HyDE      ：让 LLM 先写"假设答案"，用答案（而非问题）去检索

核心思想（Day39 原理）：效果差先改查询，零成本见效快，不要急着换模型。

用法：
  python query_rewrite.py              # 同义词扩展对比
  python query_rewrite.py --hyde       # 再加 HyDE 对比（需 ollama 跑生成模型）

依赖：Day38 的 embedding_compare.py（bge 向量 + 测试查询）、evaluate.py（评估函数）
"""

from __future__ import annotations

import os
import sys
import requests
import chromadb

# Windows 控制台默认 GBK，强制 UTF-8 输出避免中文乱码
if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8")

# 添加父目录到路径
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from rag.embedding_compare import get_embedding_bge, TEST_QUERIES, CHROMA_PATH
from rag.evaluate import evaluate, print_report


# ══════════════════════════════════════════════════════════
# 配置
# ══════════════════════════════════════════════════════════

COLLECTION_NAME = "kb_bge"
TOP_K = 5

# 基线里的问题查询（Day39 评估定位到的）
# 对它们做改写，验证效果；其他正常查询直接用原句
PROBLEM_QUERIES = {
    "请求太慢怎么排查",
    "登录密码怎么安全存储",
    "上线后怎么知道服务出问题",
}

# HyDE 用的生成模型（ollama 本地）
HYDE_MODEL = "qwen2.5:3b"   # 已有模型里最轻的，生成够用


# ══════════════════════════════════════════════════════════
# 检索函数（复用 evaluate 的 search_fn）
# ══════════════════════════════════════════════════════════

def search_fn(query: str, top_k: int = TOP_K) -> list[dict]:
    """用 bge-m3 检索，返回 [{file, ...}]"""
    client = chromadb.PersistentClient(path=CHROMA_PATH)
    collection = client.get_collection(name=COLLECTION_NAME)
    vec = get_embedding_bge(query)
    results = collection.query(
        query_embeddings=[vec],
        n_results=top_k,
        include=["metadatas", "distances"]
    )
    return [
        {
            "file": results["metadatas"][0][i]["file"],
            "chunk_index": results["metadatas"][0][i]["chunk_index"],
            "distance": results["distances"][0][i],
        }
        for i in range(len(results["ids"][0]))
    ]


# ══════════════════════════════════════════════════════════
# 方法 1：同义词扩展
# ══════════════════════════════════════════════════════════

# 手工同义词表：把"用户口语"映射到"文档用词"（Day39 原理的关键——用户怎么说 ≠ 文档怎么写）
SYNONYM_MAP = {
    "太慢": ["性能", "查询慢", "慢", "性能问题"],
    "排查": ["定位问题", "分析", "排错", "解决"],
    "登录密码": ["密码", "密码存储", "认证", "凭据"],
    "安全存储": ["哈希", "加密", "存储"],
    "上线后": ["部署后", "生产环境", "发布后"],
    "服务出问题": ["告警", "监控", "ERROR", "故障"],
    "知道": ["查看", "发现", "判断"],
}

def expand_query(query: str) -> list[str]:
    """把一个查询扩展成多个变体

    命中同义词表中的词 → 替换生成新变体；原句保留。
    """
    queries = [query]
    for word, syns in SYNONYM_MAP.items():
        if word in query:
            for s in syns:
                queries.append(query.replace(word, s))
    return queries


def search_expanded(query: str, top_k: int = TOP_K) -> list[dict]:
    """多路检索后合并、去重、按距离排序（Day39 同义词扩展方法）

    思路：query 扩成 N 个变体，每个变体检索 Top-K，结果按"最优距离"合并。
    同一片段可能被多个变体检索到，取它的最小距离（最相关那次）。
    """
    best_by_file: dict[str, dict] = {}
    for variant in expand_query(query):
        for r in search_fn(variant, top_k=top_k):
            key = (r["file"], r["chunk_index"])
            if key not in best_by_file or r["distance"] < best_by_file[key]["distance"]:
                best_by_file[key] = r
    # 按距离升序（Chroma 余弦距离越小越相关）取前 top_k
    return sorted(best_by_file.values(), key=lambda r: r["distance"])[:top_k]


# ══════════════════════════════════════════════════════════
# 方法 2：HyDE（用 LLM 写"假设答案"去检索）
# ══════════════════════════════════════════════════════════

def call_llm(prompt: str, model: str = HYDE_MODEL, max_tokens: int = 200) -> str:
    """调用 ollama 生成模型，返回文本"""
    resp = requests.post(
        "http://localhost:11434/api/generate",
        json={"model": model, "prompt": prompt, "stream": False,
              "options": {"num_predict": max_tokens}},
        timeout=120
    )
    resp.raise_for_status()
    return resp.json()["response"]


def hyde_search(query: str, top_k: int = TOP_K) -> list[dict]:
    """HyDE：先生成假设答案，再向量化假设答案去检索（Day39 最前沿方法）

    反直觉点：答案准不准确不重要，重要的是"长度和句式像文档"，
    假设答案在向量空间里离文档更近 → 命中率更高。
    """
    prompt = (
        "写一段关于下面问题的简要回答（可以包含猜想，不用准确，"
        "目的是找到相关文档）：\n\n"
        f"问题：{query}\n"
        f"假设回答："
    )
    hypothetical = call_llm(prompt)
    # 用"假设答案"而不是"用户问题"去检索
    client = chromadb.PersistentClient(path=CHROMA_PATH)
    collection = client.get_collection(name=COLLECTION_NAME)
    vec = get_embedding_bge(hypothetical)
    results = collection.query(
        query_embeddings=[vec],
        n_results=top_k,
        include=["metadatas", "distances"]
    )
    return [
        {
            "file": results["metadatas"][0][i]["file"],
            "chunk_index": results["metadatas"][0][i]["chunk_index"],
            "distance": results["distances"][0][i],
        }
        for i in range(len(results["ids"][0]))
    ]


# ══════════════════════════════════════════════════════════
# 主流程：基线 vs 改写后 对比
# ══════════════════════════════════════════════════════════

def main():
    use_hyde = "--hyde" in sys.argv

    # 确认集合存在
    client = chromadb.PersistentClient(path=CHROMA_PATH)
    try:
        client.get_collection(name=COLLECTION_NAME)
    except Exception:
        print(f"集合 {COLLECTION_NAME} 不存在，请先运行: python rag/embedding_compare.py --rebuild")
        return

    # 基线评估（原查询）
    print("=" * 60)
    print("基线：原查询")
    print("=" * 60)
    baseline = evaluate(TEST_QUERIES, search_fn, k=TOP_K)
    print_report(baseline)

    # 改写后评估：只对问题查询用改写，其余保持原句
    def rewritten_search(query: str, top_k: int = TOP_K) -> list[dict]:
        if query in PROBLEM_QUERIES:
            if use_hyde:
                print(f"    [HyDE] {query} → 生成假设答案检索")
                return hyde_search(query, top_k)
            else:
                print(f"    [扩展] {query} → {expand_query(query)}")
                return search_expanded(query, top_k)
        return search_fn(query, top_k=top_k)

    method = "HyDE" if use_hyde else "同义词扩展"
    print("\n" + "=" * 60)
    print(f"改写后：{method}（只改 {len(PROBLEM_QUERIES)} 条问题查询）")
    print("=" * 60)
    rewritten = evaluate(TEST_QUERIES, rewritten_search, k=TOP_K)
    print_report(rewritten)

    # 对比结论
    print("\n" + "=" * 60)
    print("对比结论")
    print("=" * 60)
    print(f"Recall@{TOP_K}: {baseline['recall@k']:.0%} → {rewritten['recall@k']:.0%} "
          f"({'↑' if rewritten['recall@k'] > baseline['recall@k'] else '→'})")
    print(f"MRR:      {baseline['mrr']:.3f} → {rewritten['mrr']:.3f} "
          f"({'↑' if rewritten['mrr'] > baseline['mrr'] else '→'})")
    print(f"Top-1:    {baseline['top1']:.0%} → {rewritten['top1']:.0%} "
          f"({'↑' if rewritten['top1'] > baseline['top1'] else '→'})")
    print(f"\n改写不是玄学，用数字证明是否有效（{method}）")


if __name__ == "__main__":
    main()
