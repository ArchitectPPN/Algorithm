# Day 37：MMR 多样性检索

> 目标：理解"Top-K 返回重复内容"的问题，学会用 MMR（最大边际相关性）解决。

---

## 学习路线（约 60-90 分钟）

```
问题引入（10min）→ MMR 原理（20min）→ 手写 MMR（30min）→ 对比效果（20min）
```

---

## 第一步：问题引入（10min）

**普通 Top-K 检索的问题：** 返回的前几条可能高度相似，内容重复。

例如知识库里"SQL 注入"相关的片段有 5 段，查询"如何防注入"时：
- Top-1: "永远使用参数化查询，禁止拼接 SQL"（`sql-best-practices.md`）
- Top-2: "禁止拼接 SQL 字符串，必须用参数化查询"（`sql-best-practices.md` 另一段）
- Top-3: "SQL 注入防护：使用参数化查询"（同上）

前三条几乎在说同一件事 → 信息冗余，浪费 LLM 上下文窗口。

**问题本质：** 普通检索只优化"和查询的相关性"，不优化"结果之间的多样性"。

---

## 第二步：MMR 原理（20min）

**MMR（Maximal Marginal Relevance，最大边际相关性）** 同时考虑两个目标：

```
MMR = λ × 相关性 - (1 - λ) × 冗余度
```

- **相关性**：候选片段和查询的相似度（要最大化）
- **冗余度**：候选片段和已选片段的相似度（要最小化）
- **λ（lambda）**：0~1 的平衡参数，控制"多相关"和"多多样"的权重

**选择过程（贪心）：**
1. 先选和查询最相关的（Top-1）
2. 之后每轮：从剩余候选中，选"和查询相关、且和已选集合最不重复"的一个
3. 重复直到选满 K 个

```
第一轮：所有候选只比相关性 → 选和查询最像的 A
第二轮：候选 B 相关 0.9，但和 A 相似 0.95 → 得分低
        候选 C 相关 0.8，但和 A 相似 0.3  → 得分高 → 选 C
```

**λ 的作用：**
- λ=1：完全看相关性 → 退化成普通 Top-K
- λ=0：完全看多样性 → 结果杂乱，可能不相关
- 常见取值 0.7~0.8：兼顾两者

---

## 第三步：手写 MMR（30min）

```python
def mmr_select(query_vec: list[float], candidates: list[list[float]],
               k: int = 3, lambda_: float = 0.7) -> list[int]:
    """贪心选择 K 个既相关又不重复的候选

    Args:
        query_vec: 查询向量
        candidates: 候选向量列表
        k: 要选几个
        lambda_: 平衡参数（越大越看重相关性）
    Returns:
        选中候选的下标列表
    """
    import numpy as np

    q = np.array(query_vec)
    cand = np.array(candidates)  # shape: (N, dim)

    # 预计算：每个候选和查询的相关性（余弦相似度）
    q_norm = q / (np.linalg.norm(q) + 1e-8)
    cand_norm = cand / (np.linalg.norm(cand, axis=1, keepdims=True) + 1e-8)
    sim_query = cand_norm @ q_norm  # 相关性得分

    # 预计算：候选两两之间的相似度
    sim_matrix = cand_norm @ cand_norm.T  # 冗余度来源

    selected = []        # 已选下标
    remaining = list(range(len(candidates)))

    for _ in range(min(k, len(candidates))):
        best_idx = None
        best_score = -1

        for i in remaining:
            # MMR = λ × 相关性 - (1-λ) × 最大冗余度
            relevance = sim_query[i]
            # 冗余度 = 与已选集合中相似度最大的那个
            if selected:
                max_dup = max(sim_matrix[i][j] for j in selected)
            else:
                max_dup = 0
            score = lambda_ * relevance - (1 - lambda_) * max_dup

            if score > best_score:
                best_score = score
                best_idx = i

        selected.append(best_idx)
        remaining.remove(best_idx)

    return selected
```

---

## 第四步：对比效果（20min）

```python
# 普通 Top-K vs MMR
def plain_search(query: str, top_k: int = 5):
    """普通检索：只按相关性，返回候选的 id / 文档 / 元数据"""
    query_vec = get_embedding(query)
    results = collection.query(
        query_embeddings=[query_vec],
        n_results=top_k,
        include=["documents", "metadatas", "distances"]
    )
    return query_vec, results  # 同时返回查询向量，供 MMR 使用

# 1. 先取 Top-20 候选
query_vec, results = plain_search("如何防止 SQL 注入", top_k=20)

# 2. 用 ids 取出候选的向量（query 结果不带向量，必须重新 get）
candidate_ids = results["ids"][0]
candidates = collection.get(
    ids=candidate_ids,
    include=["embeddings"]
)
candidate_vecs = candidates["embeddings"]

# 3. MMR 选 Top-3
selected = mmr_select(query_vec, candidate_vecs, k=3, lambda_=0.7)

print("普通 Top-3:")
for i in range(3):
    print(f"  {results['metadatas'][0][i]['file']} | {results['documents'][0][i][:50]}...")

print("\nMMR Top-3:")
for idx in selected:
    print(f"  {results['metadatas'][0][idx]['file']} | {results['documents'][0][idx][:50]}...")
```

**预期结果：** 普通 Top-3 可能都来自同一文件，MMR Top-3 会覆盖多个不同主题的片段。

---

## 实验任务

1. 用同一查询跑普通 Top-K 和 MMR，对比结果文件分布
2. 调整 λ（0.3 / 0.7 / 1.0），观察结果变化
3. 写个小函数统计"Top-K 结果来自几个不同文件"（普通检索 vs MMR）

---

## 检验标准

1. **默写**：MMR 公式 `MMR = λ×相关性 - (1-λ)×冗余度`
2. **一句话**："MMR 在选片段时，既看和查询的相关性，又惩罚和已选片段的重复，让结果覆盖更多主题"
3. **追问**：λ=1 时会发生什么？（退化成普通 Top-K）；λ=0 呢？（只追求多样，可能不相关）

---

## 产出文件

`rag/mmr_demo.py` — MMR 多样性检索 demo
