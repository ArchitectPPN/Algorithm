"""
Day31：文本分片策略

功能：
1. 实现固定长度分片（按字符数切）
2. 参数实验：不同 chunk_size / chunk_overlap 的效果
3. 输出结论
"""

import os


# ══════════════════════════════════════════════════════════
# 分片函数
# ══════════════════════════════════════════════════════════

def split_text(text: str, chunk_size: int = 500, chunk_overlap: int = 50) -> list[str]:
    """按字符数分片，支持重叠"""
    if chunk_overlap >= chunk_size:
        raise ValueError(f"chunk_overlap({chunk_overlap}) 必须小于 chunk_size({chunk_size})")
    chunks = []
    start = 0
    while start < len(text):
        end = start + chunk_size
        chunks.append(text[start:end])
        start += chunk_size - chunk_overlap
    return chunks


# ══════════════════════════════════════════════════════════
# 基础验证：用简单文本测试分片逻辑
# ══════════════════════════════════════════════════════════

print("=" * 60)
print("基础验证：1200 字文本，chunk_size=500, overlap=50")
print("=" * 60)

text = "A" * 1200
chunks = split_text(text, chunk_size=500, chunk_overlap=50)
for i, chunk in enumerate(chunks):
    print(f"  片段 {i}: 长度 {len(chunk)}, 开头: {chunk[:20]}")
print(f"  共 {len(chunks)} 个片段，相邻片段重叠 50 字")


# ══════════════════════════════════════════════════════════
# 参数实验：用真实文章测试不同参数
# ══════════════════════════════════════════════════════════

# 用 Day29 的笔记作为测试文章
article_path = os.path.join(os.path.dirname(__file__), "..", "docs", "notes", "rag", "day29-embedding-basics.md")
if not os.path.exists(article_path):
    # 找不到就用一段模拟文本
    article_text = """
    Embedding 是把一段文字变成一串数字（向量）。语义相近的文字，向量距离就近。
    比如今天天气真好和今天天气不错，它们的向量非常接近。
    而今天天气真好和数据库索引优化，向量距离就很远。
    余弦相似度是衡量两个向量相似程度的指标，公式是 cos(theta) = A·B / (|A| × |B|)。
    cos(0度) = 1 表示完全相同，cos(90度) = 0 表示无关，cos(180度) = -1 表示完全相反。
    在 RAG 系统中，分片策略非常重要。太长的分片会导致语义稀释，太短的分片会丢失上下文。
    chunk_overlap 的作用是防止关键信息正好卡在分片边界上。
    一般建议 overlap 设为 chunk_size 的 10-20%。
    """.strip() * 10  # 放大到约 2000 字
else:
    with open(article_path, encoding="utf-8") as f:
        article_text = f.read()

print(f"\n测试文章长度: {len(article_text)} 字")

print("\n" + "=" * 60)
print("参数实验：不同 chunk_size / chunk_overlap 的效果")
print("=" * 60)

print(f"  {'size':>6} {'overlap':>8} {'片段数':>6} {'平均长度':>8} {'总字符':>8}")
print(f"  {'-'*6} {'-'*8} {'-'*6} {'-'*8} {'-'*8}")

for size in [200, 500, 1000]:
    for overlap in [0, 50, 100]:
        if overlap >= size:
            continue
        chunks = split_text(article_text, chunk_size=size, chunk_overlap=overlap)
        avg_len = sum(len(c) for c in chunks) / len(chunks) if chunks else 0
        total_chars = sum(len(c) for c in chunks)
        print(f"  {size:>6} {overlap:>8} {len(chunks):>6} {avg_len:>8.0f} {total_chars:>8}")


# ══════════════════════════════════════════════════════════
# 结论
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("结论")
print("=" * 60)
print("""
  1. chunk_size=500, overlap=50 是较好的平衡点：
     - 片段数适中，不会太多（检索慢）也不会太少（语义稀释）
     - 50 字重叠足以防止关键信息卡在边界

  2. chunk_size=200 太短：
     - 片段数多，检索效率低
     - 上下文容易丢失

  3. chunk_size=1000 太长：
     - 片段数少，但语义被稀释
     - 检索精度下降

  4. overlap 建议：chunk_size 的 10-20%（500字配50-100字重叠）
""")
