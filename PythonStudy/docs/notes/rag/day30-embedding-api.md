# Day 30：Embedding 模型调通 + 真实向量计算

> 目标：用 ollama 的 nomic-embed-text 获取真实向量，替换手写假向量，感受语义检索。

---

## 学习路线（约 50 分钟）

```
调通 API（10min）→ 手写余弦相似度接真实向量（15min）→ 对比实验（15min）→ 总结（10min）
```

---

## 第一步：调通 ollama Embedding API（10min）

```python
import requests

def get_embedding(text: str) -> list[float]:
    """调用 ollama 获取文本的 embedding 向量"""
    resp = requests.post(
        "http://localhost:11434/api/embeddings",
        json={"model": "nomic-embed-text", "prompt": text}
    )
    resp.raise_for_status()
    return resp.json()["embedding"]

# 测试
v = get_embedding("如何防止 SQL 注入")
print(f"维度: {len(v)}")       # 应该是 768
print(f"前 5 个值: {v[:5]}")
```

## 第二步：接上昨天的余弦相似度（15min）

把 Day 29 的手写 `cosine_similarity()` 和今天的 `get_embedding()` 拼起来：

```python
# 昨天写的
def cosine_similarity(a, b):
    dot = sum(x * y for x, y in zip(a, b))
    mag_a = math.sqrt(sum(x * x for x in a))
    mag_b = math.sqrt(sum(x * x for x in b))
    return dot / (mag_a * mag_b)

# 真实向量
v1 = get_embedding("今天天气真好")
v2 = get_embedding("今天天气不错")
v3 = get_embedding("数据库索引优化")

print(f"天气 vs 天气: {cosine_similarity(v1, v2):.3f}")   # 应该高（0.7+）
print(f"天气 vs 数据库: {cosine_similarity(v1, v3):.3f}") # 应该低（0.5 以下）
```

## 第三步：对比实验（15min）

用真实场景的句子测试：

```python
pairs = [
    ("如何防止 SQL 注入", "使用参数化查询防止注入攻击"),
    ("如何防止 SQL 注入", "变量命名使用驼峰格式"),
    ("密码存储最佳实践", "使用 bcrypt 哈希存储密码"),
    ("密码存储最佳实践", "代码缩进使用 4 个空格"),
    ("API 接口设计规范", "RESTful 风格的 API 设计"),
]

for a, b in pairs:
    score = cosine_similarity(get_embedding(a), get_embedding(b))
    bar = "█" * int(score * 20)
    print(f"{score:.3f} {bar} | {a}  ←→  {b[:30]}")
```

预期：
- 语义相关的组分数高（0.7+）
- 语义无关的组分数低（0.5 以下）

## 第四步：为什么不用关键词匹配？（10min）

想一想：
- "资料库" 和 "数据库" → 关键词匹配：0 个词相同 → 找不到
- "资料库" 和 "数据库" → Embedding：向量很近 → 找到了

这就是 Embedding 的核心价值——**捕捉语义，不是匹配字面**。

---

## 检验标准

1. **默写**：能写出 `get_embedding()` + `cosine_similarity()` 的完整调用链
2. **一句话**："用 ollama 本地模型把文字转成 768 维向量，用余弦相似度比较语义距离"
3. **追问**：如果两个完全不相关的句子相似度是 0.4，正常吗？（正常，余弦相似度很少低于 0，因为大多数向量在高维空间都在同一象限）

---

## 产出文件

`learning/rag/embedding_demo.py`