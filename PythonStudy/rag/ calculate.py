import requests
import math
import warnings
warnings.filterwarnings("ignore")

def get_embedding(text: str) -> list[float]:
    """调用 ollama 获取文本的 embedding 向量（768维）"""
    resp = requests.post(
        "http://localhost:11434/api/embeddings",
        json={"model": "nomic-embed-text", "prompt": text}
    )
    return resp.json()["embedding"]

def cosine_similarity(a: list[float], b: list[float]) -> float:
    """余弦相似度 = 点积 / (模长A × 模长B)"""
    dot = sum(x * y for x, y in zip(a, b))
    magA = math.sqrt(sum(x * x for x in a))
    magB = math.sqrt(sum(x * x for x in b))
    return dot / (magA * magB)


# ══════════════════════════════════════════════════════════
# 对比实验：5 组句子
# ══════════════════════════════════════════════════════════

pairs = [
    # 语义相近
    ("如何防止 SQL 注入", "使用参数化查询防止注入攻击"),
    ("密码存储最佳实践", "使用 bcrypt 哈希存储密码"),
    # 语义无关
    ("如何防止 SQL 注入", "变量命名使用驼峰格式"),
    ("密码存储最佳实践", "代码缩进使用 4 个空格"),
    ("API 接口设计规范", "RESTful 风格的 API 设计"),
]

print("余弦相似度对比实验\n")

for a, b in pairs:
    va = get_embedding(a)
    vb = get_embedding(b)
    score = cosine_similarity(va, vb)
    bar = "█" * int(score * 20)
    print(f"  {score:.3f} {bar} | {a}  ←→  {b}")

print("\n结论：语义相近的分数高，语义无关的分数低。")
