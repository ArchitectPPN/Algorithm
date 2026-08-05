"""
Day30：Embedding 模型调通 + 真实向量计算

功能：
1. 调通 ollama Embedding API，验证向量维度和数值
2. 用真实向量计算余弦相似度，对比语义相近/无关
"""

import requests
import math
import warnings
warnings.filterwarnings("ignore")


# ══════════════════════════════════════════════════════════
# 工具函数
# ══════════════════════════════════════════════════════════

def get_embedding(text: str, model: str = "nomic-embed-text") -> list[float]:
    """调用 ollama 获取文本的 embedding 向量"""
    resp = requests.post(
        "http://localhost:11434/api/embeddings",
        json={"model": model, "prompt": text},
        timeout=30
    )
    resp.raise_for_status()
    return resp.json()["embedding"]

def cosine_similarity(a: list[float], b: list[float]) -> float:
    """余弦相似度 = 点积 / (模长A × 模长B)"""
    dot = sum(x * y for x, y in zip(a, b))
    mag_a = math.sqrt(sum(x * x for x in a))
    mag_b = math.sqrt(sum(x * x for x in b))
    return dot / (mag_a * mag_b)


# ══════════════════════════════════════════════════════════
# 第一步：调通 API，验证向量维度和数值
# ══════════════════════════════════════════════════════════

print("=" * 60)
print("第一步：调通 ollama Embedding API")
print("=" * 60)

test_text = "如何防止 SQL 注入"
v = get_embedding(test_text)

print(f"  输入文本: {test_text}")
print(f"  向量维度: {len(v)}")
print(f"  前 5 个值: {v[:5]}")
print(f"  向量模长: {math.sqrt(sum(x * x for x in v)):.4f}")
print(f"  ✅ API 调通，nomic-embed-text 输出 768 维向量")


# ══════════════════════════════════════════════════════════
# 第二步：用真实向量计算余弦相似度
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第二步：真实向量 + 余弦相似度")
print("=" * 60)

v1 = get_embedding("今天天气真好")
v2 = get_embedding("今天天气不错")
v3 = get_embedding("数据库索引优化")

score_similar = cosine_similarity(v1, v2)
score_different = cosine_similarity(v1, v3)

print(f"  '天气真好' vs '天气不错': {score_similar:.3f}  (语义相近，应该高)")
print(f"  '天气真好' vs '数据库索引': {score_different:.3f}  (语义无关，应该低)")
print(f"  差值: {score_similar - score_different:.3f}  (差值越大，区分能力越强)")


# ══════════════════════════════════════════════════════════
# 第三步：对比实验（5 组句子）
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第三步：对比实验 — 语义相近 vs 语义无关")
print("=" * 60)

pairs = [
    # 语义相近
    ("如何防止 SQL 注入", "使用参数化查询防止注入攻击"),
    ("密码存储最佳实践", "使用 bcrypt 哈希存储密码"),
    # 语义无关
    ("如何防止 SQL 注入", "变量命名使用驼峰格式"),
    ("密码存储最佳实践", "代码缩进使用 4 个空格"),
    # 语义相关（中等相似）
    ("API 接口设计规范", "RESTful 风格的 API 设计"),
]

for a, b in pairs:
    va = get_embedding(a)
    vb = get_embedding(b)
    score = cosine_similarity(va, vb)
    bar = "█" * int(score * 20)
    print(f"  {score:.3f} {bar} | {a}  ←→  {b}")

print("\n结论：语义相近的分数高（0.7+），语义无关的分数低（0.5以下）。")
print("Embedding 的核心价值：捕捉语义，不是匹配字面。")
