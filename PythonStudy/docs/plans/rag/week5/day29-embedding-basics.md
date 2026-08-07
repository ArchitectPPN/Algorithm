# Day 29-30：Embedding 原理 + 模型选型 + 手写余弦相似度

> 目标：理解 Embedding 是什么，选定模型，用手写余弦相似度比较两段文字的语义距离。

---

## 学习路线（约 80 分钟）

```
概念（10min）→ 模型选型（15min）→ 手写余弦相似度（20min）→ ollama 验证（15min）→ 对比实验（15min）→ 总结（5min）
```

---

## 第一步：理解 Embedding 概念（10min）

### Embedding 是什么？

把一段文字变成一串数字（向量）。**语义相近的文字，向量距离就近。**

```
"今天天气真好" → [0.12, -0.34, 0.87, 0.05, ...]  (768个数字)
"今天天气不错" → [0.11, -0.33, 0.85, 0.04, ...]  ← 向量很接近
"数据库索引优化" → [0.89, 0.42, -0.15, 0.67, ...] ← 向量很远
```

### 类比

- 地图上"北京"和"上海"的经纬度距离比"北京"和"纽约"更近
- Embedding 就是给每段文字分配一个"语义坐标"
- 坐标近 = 意思近

### 为什么不用关键词匹配？

```
查询："资料库怎么备份"
文档A："数据库备份方法"    → 关键词匹配：0 个词相同 → 找不到 ❌
文档A：向量 [0.13, -0.33, ...]  → 余弦相似度：0.92 → 找到了 ✅
```

关键词"资料库"和"数据库"不一样，但语义相近。Embedding 能捕捉这种语义关系。

---

## 第二步：Embedding 模型选型（15min）

### 主流模型对比

| 模型 | 部署方式 | 维度 | 中文效果 | 价格 | 是否需要 GPU |
|------|---------|------|---------|------|-------------|
| nomic-embed-text | Ollama 本地 | 768 | 中等 | 免费 | 否 |
| BAAI/bge-small-zh | 本地 HuggingFace | 512 | 好（中文专用） | 免费 | 否 |
| 智谱 embedding-3 | 云端 API | 2048 | 好 | 0.5元/百万token | 否 |
| 通义 text-embedding-v3 | 云端 API | 1024 | 好 | 0.7元/百万token | 否 |

### 选型结论

**当前阶段用 nomic-embed-text**：本地部署、零成本、无需 API Key、768 维够用。

**后续生产环境考虑**：bge-small-zh（中文专用，效果更好）或云端 API（维度更高，但有成本和延迟）。

---

## 第三步：手写余弦相似度（20min）

### 公式

```
cos(θ) = (A·B) / (|A| × |B|)

A·B = a1×b1 + a2×b2 + ... + an×bn    (向量点积)
|A|  = sqrt(a1² + a2² + ... + an²)    (向量模长)
```

### 代码（用纯 Python，不依赖 numpy）

```python
import math

def dot_product(a: list[float], b: list[float]) -> float:
    """向量点积：对应位置相乘再求和"""
    return sum(x * y for x, y in zip(a, b))

def magnitude(a: list[float]) -> float:
    """向量模长：各分量平方和再开方"""
    return math.sqrt(sum(x * x for x in a))

def cosine_similarity(a: list[float], b: list[float]) -> float:
    """余弦相似度：点积 / (模长A × 模长B)"""
    return dot_product(a, b) / (magnitude(a) * magnitude(b))

# 测试：用假向量
vec_a = [1, 2, 3]
vec_b = [1, 2, 3]    # 完全相同
vec_c = [-1, -2, -3]  # 完全相反

print(cosine_similarity(vec_a, vec_b))  # 1.0（完全相同）
print(cosine_similarity(vec_a, vec_c))  # -1.0（完全相反）
```

### 练习

自己写出来，不要抄。然后思考：
- 为什么 cos(0°) = 1 表示完全相同？
- 如果结果是 0 是什么意思？

---

## 第四步：用 ollama 获取真实向量（15min）

有了真的 Embedding 模型，替换假向量：

```python
import requests

def get_embedding(text: str) -> list[float]:
    """调用 ollama 获取文本的 embedding 向量"""
    resp = requests.post(
        "http://localhost:11434/api/embeddings",
        json={"model": "nomic-embed-text", "prompt": text}
    )
    return resp.json()["embedding"]

# 获取真实向量
v1 = get_embedding("今天天气真好")
v2 = get_embedding("今天天气不错")
v3 = get_embedding("数据库索引优化")

# 用刚才手写的余弦相似度比较
print(f"'天气真好' vs '天气不错': {cosine_similarity(v1, v2):.3f}")  # 应该高
print(f"'天气真好' vs '数据库索引': {cosine_similarity(v1, v3):.3f}")  # 应该低
```

---

## 第五步：对比实验（15min）

准备 5 组句子，跑一遍相似度，感受语义相近和语义无关的差距：

```python
pairs = [
    ("如何防止 SQL 注入", "使用参数化查询防止注入"),
    ("如何防止 SQL 注入", "变量命名使用驼峰格式"),
    ("密码存储最佳实践", "使用 bcrypt 哈希密码"),
    ("密码存储最佳实践", "代码缩进使用 4 个空格"),
    ("API 接口设计规范", "RESTful API 使用名词复数"),
]

for a, b in pairs:
    va = get_embedding(a)
    vb = get_embedding(b)
    score = cosine_similarity(va, vb)
    print(f"{score:.3f} | {a}  ←→  {b}")
```

预期结果：语义相关的组分数高（0.7+），语义无关的组分数低（0.5 以下）。

---

## 检验标准

做完后用三步法自检：

1. **默写**：能写出 `cosine_similarity()` 函数 + `get_embedding()` 调用
2. **一句话**："用 ollama 本地模型把文字转成 768 维向量，语义相近的向量距离近，用余弦相似度衡量"
3. **追问**：
   - 为什么不用关键词匹配？（同义词匹配不到）
   - 余弦相似度为什么用 cos 而不是直接算欧氏距离？（余弦消除长度影响，只看方向）
   - nomic-embed-text 和 bge-small-zh 怎么选？（学习阶段用前者免费够用，生产用后者中文效果更好）

---

## 产出文件

`rag/cosine_similarity.py` — 包含手写余弦相似度 + ollama embedding 调用 + 基础验证 + 对比实验

> 注：Day29 和 Day30 合并为一天完成，不再单独产出 `embedding_demo.py`。

---

## 额外要安装的包

```bash
pip install requests   # 已有的话跳过
# ollama nomic-embed-text 模型昨天已装好
```
