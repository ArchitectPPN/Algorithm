import requests
import math
import warnings
warnings.filterwarnings("ignore")

def dot_product(a: list[float], b: list[float]) -> float:
    """向量点积：对应位置相乘再求和"""
    return sum(x * y for x, y in zip(a, b))

def magnitude(a: list[float]) -> float:
    """向量模长：各分量平方和再开方"""
    return math.sqrt(sum(x * x for x in a))

def cosine_similarity(a: list[float], b: list[float]) -> float:
    """余弦相似度 = 点积 / (模长A × 模长B)"""
    return dot_product(a, b) / (magnitude(a) * magnitude(b))

def get_embedding(text: str) -> list[float]:
    """调用 ollama 获取文本的 embedding 向量（768维）"""
    resp = requests.post(
        "http://localhost:11434/api/embeddings",
        json={"model": "nomic-embed-text", "prompt": text}
    )
    return resp.json()["embedding"]


# ══════════════════════════════════════════════════════════
# 实验1：基础验证（3组句子，验证 Embedding 语义捕捉能力）
# ══════════════════════════════════════════════════════════

print("=" * 60)
print("实验1：基础验证 — 语义相近 vs 语义无关")
print("=" * 60)

basic_pairs = [
    ("今天天气真好", "今天天气不错"),       # 语义相近 → 应该高
    ("今天天气真好", "数据库索引优化"),     # 语义无关 → 应该低
    ("今天天气不错", "数据库索引优化"),     # 语义无关 → 应该低
]

for a, b in basic_pairs:
    va = get_embedding(a)
    vb = get_embedding(b)
    score = cosine_similarity(va, vb)
    bar = "█" * int(score * 20)
    print(f"  {score:.3f} {bar} | {a}  ←→  {b}")

print("\n预期：第一组分数明显高于后两组，说明 Embedding 能捕捉语义关系。")


# ══════════════════════════════════════════════════════════
# 实验2：编程领域对比（5组句子，更贴近实际应用）
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("实验2：编程领域 — 语义相近 vs 语义无关")
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
