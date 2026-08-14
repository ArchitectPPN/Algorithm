"""
Day39：检索效果评估

用量化指标评估检索质量，为"优化 → 重评估"闭环提供数据。

核心指标（Day39 原理）：
  - Recall@K  ：正确答案是否出现在前 K 条（看"找没找到"）
  - MRR       ：正确答案排第几（看"排得靠不靠前"），排第1=1.0，排第2=0.5
  - Top-1 命中：最相关的是否排第一

用法：
  python evaluate.py                 # 用 bge-m3（Day38 选型结论）评估
  python evaluate.py --rebuild       # 重建集合再评估

依赖：Day38 的 rag/embedding_compare.py（复用 bge 向量函数和 15 条测试查询）
"""

from __future__ import annotations

import os
import sys
import chromadb

# Windows 控制台默认 GBK，强制 UTF-8 输出避免中文乱码
if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8")

# 添加父目录到路径，方便导入同目录下的 embedding_compare
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from rag.embedding_compare import get_embedding_bge, TEST_QUERIES, CHROMA_PATH


# ══════════════════════════════════════════════════════════
# 配置
# ══════════════════════════════════════════════════════════

COLLECTION_NAME = "kb_bge"   # Day38 选型结论：bge-m3
TOP_K = 5                    # 默认评估 K


# ══════════════════════════════════════════════════════════
# 检索函数（evaluate 的 search_fn 契约：返回 [{file, ...}, ...]）
# ══════════════════════════════════════════════════════════

def search_fn(query: str, top_k: int = TOP_K) -> list[dict]:
    """用 bge-m3 检索，返回 [{file, chunk_index, distance}, ...]

    这是评估脚本的"插槽"：想对比不同模型/不同集合，改这里即可。
    """
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
# 评估核心：算 Recall@K / MRR / Top-1
# ══════════════════════════════════════════════════════════

def evaluate(queries: list[dict], search_fn, k: int = TOP_K) -> dict:
    """评估检索效果

    Args:
        queries: [{"query": "...", "expected_file": "..."}, ...]
        search_fn: 检索函数，输入 query 返回 [{file, ...}, ...]
        k: 只看前 K 条
    Returns:
        {"recall@k", "mrr", "top1", "per_query", "k"}
    """
    total = len(queries)
    hit_k = 0          # 正确答案出现在前 K
    hit_top1 = 0       # 正确答案排第一
    mrr_sum = 0.0

    per_query = []
    for item in queries:
        results = search_fn(item["query"], top_k=k)
        files = [r["file"] for r in results]
        expected = item["expected_file"]

        # Recall@K：出现在前 K 就算命中
        if expected in files:
            hit_k += 1
            rank = files.index(expected) + 1  # 1-based
            mrr_sum += 1.0 / rank
            if rank == 1:
                hit_top1 += 1
            found = True
        else:
            rank = None
            found = False

        per_query.append({
            "query": item["query"],
            "expected": expected,
            "top_files": files,
            "found": found,
            "rank": rank,
        })

    return {
        "recall@k": hit_k / total,
        "mrr": mrr_sum / total,
        "top1": hit_top1 / total,
        "per_query": per_query,
        "k": k,
    }


# ══════════════════════════════════════════════════════════
# 评估报告
# ══════════════════════════════════════════════════════════

def print_report(result: dict):
    """打印评估报告（指标 + 逐条明细 + 失败分析）"""
    print(f"\nRecall@{result['k']}: {result['recall@k']:.0%}")
    print(f"MRR:                 {result['mrr']:.3f}")
    print(f"Top-1 命中率:         {result['top1']:.0%}")

    print(f"\n逐条明细:")
    for q in result["per_query"]:
        mark = "✅" if q["found"] else "❌"
        rank_str = f"第{q['rank']}名" if q["rank"] else "未命中"
        print(f"  {mark} {q['query']} → 期望{q['expected']}, 实际{rank_str}")

    # 失败分析：未命中 / 排位靠后 分开看
    missed = [q for q in result["per_query"] if not q["found"]]
    ranked_late = [q for q in result["per_query"] if q["found"] and q["rank"] > 1]
    if missed:
        print(f"\n❌ 未命中 {len(missed)} 条（可能原因：查询和文档用词对不上 → 用查询改写）")
        for q in missed:
            print(f"    {q['query']} → Top-{result['k']}: {q['top_files']}")
    if ranked_late:
        print(f"\n⚠️ 命中但排位靠后 {len(ranked_late)} 条（MRR 拖后腿，优化相关性排序）")
        for q in ranked_late:
            print(f"    {q['query']} → 期望排第{q['rank']}, Top-{result['k']}: {q['top_files']}")


# ══════════════════════════════════════════════════════════
# 主流程
# ══════════════════════════════════════════════════════════

def main():
    rebuild = "--rebuild" in sys.argv

    # 建集合（复用 embedding_compare 的建库逻辑，已有数据则跳过）
    # ⚠️ 这里不重建：kb_bge 在 Day38 已建好且模型/知识库没变
    if rebuild:
        from rag.embedding_compare import build_collection
        build_collection("bge", rebuild=True)

    # 确认集合存在
    client = chromadb.PersistentClient(path=CHROMA_PATH)
    try:
        collection = client.get_collection(name=COLLECTION_NAME)
    except Exception:
        print(f"集合 {COLLECTION_NAME} 不存在，请先运行: python rag/embedding_compare.py --rebuild")
        return
    print(f"使用集合: {COLLECTION_NAME}（{collection.count()} 条），模型: bge-m3")
    print(f"评估查询数: {len(TEST_QUERIES)} 条，K={TOP_K}")

    result = evaluate(TEST_QUERIES, search_fn, k=TOP_K)
    print_report(result)


if __name__ == "__main__":
    main()
