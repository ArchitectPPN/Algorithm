# Day 39：检索效果评估 + 查询改写

> 目标：用量化指标评估检索质量，然后用查询改写优化，重跑评估验证效果。
> 这条链 = 发现问题 → 定位原因 → 解决问题 → 验证结果，是 RAG 优化的完整闭环。

---

## 学习路线（约 90-120 分钟）

```
为什么需要评估（10min）→ 核心指标（20min）→ 评估脚本（30min）→ 分析改进（15min）→ 查询改写实验（30min）
```

---

## 第一步：为什么需要评估（10min）

**没有评估 = 不知道检索好不好。** 常见场景：

- 换了分片参数，检索变好了还是变差了？→ 需要指标对比
- 换了 embedding 模型，值不值得？→ 需要数据支撑
- 加了阈值过滤，误伤了多少相关结果？→ 需要量化

**核心思想：** 准备一批"已知答案"的查询（golden set），跑检索，看命中率。

---

## 第二步：核心指标（20min）

| 指标 | 含义 | 怎么算 |
|------|------|--------|
| **准确率（Accuracy）** | Top-K 里有没有正确答案 | 命中的查询数 / 总查询数 |
| **命中率@K（Recall@K）** | 正确答案是否出现在前 K 条 | 同准确率@K，K 通常取 1/3/5 |
| **MRR（Mean Reciprocal Rank）** | 正确答案排多靠前 | 平均 1/排名，越靠前越高 |
| **NDCG@K** | 正确答案的排序质量（加权） | 需要分级标注（相关/部分/不相关） |
| **Top-1 命中率** | 最相关的是否排第一 | 排第一的命中数 / 总数 |

**初学者重点：Recall@K 和 MRR。** 前者看"找没找到"，后者看"排得靠不靠前"。

### 指标计算示例

```
查询 Q1：正确答案实际排在 Top-2 → Recall@5=命中, MRR 贡献 1/2=0.5
查询 Q2：正确答案实际排在 Top-1 → Recall@5=命中, MRR 贡献 1/1=1.0
查询 Q3：正确答案没出现在 Top-5 → Recall@5=未命中, MRR 贡献 0

Recall@5 = 2/3 = 66.7%
MRR = (0.5 + 1.0 + 0) / 3 = 0.5
```

---

## 第三步：评估脚本（30min）

```python
import math

def evaluate(queries: list[dict], search_fn, k: int = 5) -> dict:
    """评估检索效果

    Args:
        queries: [{"query": "...", "expected_file": "..."}, ...]
        search_fn: 检索函数，输入 query 返回 [(file, distance), ...]
        k: 只看前 K 条
    Returns:
        {"recall@k": ..., "mrr": ..., "top1": ..., "per_query": [...]}
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
    }

def print_report(result: dict):
    """打印评估报告"""
    print(f"Recall@{len(result['per_query'][0]['top_files'])}: {result['recall@k']:.0%}")
    print(f"MRR:                 {result['mrr']:.3f}")
    print(f"Top-1 命中率:         {result['top1']:.0%}")
    print(f"\n逐条明细:")
    for q in result["per_query"]:
        mark = "✅" if q["found"] else "❌"
        rank_str = f"第{q['rank']}名" if q["rank"] else "未命中"
        print(f"  {mark} {q['query']} → 期望{q['expected']}, 实际{rank_str}")
```

---

## 第四步：分析改进（20min）

跑完评估后，针对失败案例分析原因：

| 失败现象 | 可能原因 | 对策 |
|----------|---------|------|
| 相关片段没进 Top-K | 分片太大/太小，语义被稀释 | 调 chunk_size |
| 正确文件排第 2 第 3 | 有干扰片段更"像"查询 | 调阈值、看是不是元数据干扰 |
| 查询和文档关键词差异大 | 用户措辞和文档用词对不上 | **查询改写**（本日第五步） |
| 全部查询都失败 | 知识库没收录该内容 | 补文档，不是调参能解决的 |

**关键：** 检索评估是"找原因"不是"看分数"。分数低不可怕，不知道为啥低才可怕。

---

## 第五步：查询改写实验（30min）

评估发现某些查询"措辞和文档对不上"时，不要急着换模型——先试试**改查询**，零成本、见效快。

### 问题本质

向量检索对措辞敏感："登录老失败咋整"和文档里的"认证流程异常处理"，语义相同但向量距离远。用户怎么问 ≠ 文档怎么写。

### 三种解法

| 方法 | 成本 | 原理 |
| --- | --- | --- |
| **同义词扩展** | 0（不调 LLM） | 手工同义词表，把查询扩成多个变体分别检索 |
| **LLM 改写查询** | 1 次 LLM | 让 LLM 把口语改写为规范检索式 |
| **HyDE** | 1 次 LLM | 让 LLM 先写"假设答案"，再用答案去检索 |

### 同义词扩展（最简单）

```python
SYNONYM_MAP = {
    "注入": ["注入攻击", "SQL 注入", "攻击"],
    "登录": ["认证", "鉴权", "session", "身份验证"],
    "缓存": ["redis", "缓存失效", "cache"],
}

def expand_query(query: str) -> list[str]:
    """把一个查询扩展成多个变体"""
    queries = [query]
    for word, syns in SYNONYM_MAP.items():
        if word in query:
            for s in syns:
                queries.append(query.replace(word, s))
    return queries

# 多路检索后合并、去重、按距离排序
all_results = []
for q in expand_query("如何防止注入"):
    all_results += search(q, top_k=3)
```

### HyDE（最前沿）

核心反直觉：**不直接检索原始查询，而是让 LLM 先写一段假设答案，再用答案向量去检索**。

```python
def hyde_search(user_query: str, top_k: int = 3):
    """HyDE：先生成假设答案，再向量化假设答案去检索"""
    prompt = f"""写一段关于下面问题的简要回答（可以包含猜想，不用准确，
目的是找到相关文档）：

问题：{user_query}
假设回答："""
    hypothetical = call_llm(prompt)

    # 关键：用"假设答案"而不是"用户问题"去向量化检索
    vec = get_embedding(hypothetical)
    results = collection.query(query_embeddings=[vec], n_results=top_k, ...)
    return results
```

**为什么有效：** 用户问题往往很短、很抽象，而**文档是完整句子**。假设答案的长度和句式更接近文档 → 向量更近。比如问"怎么防止注入"，假设答案可能写着"永远使用参数化查询，禁止拼接 SQL 字符串"——几乎就是文档原文，命中率暴增。

### 用评估验证改写效果

改写不是玄学，**重跑 Day 39 的评估脚本**，对比指标：

```text
改写前: Recall@5=70%, MRR=0.41
改写后: Recall@5=90%, MRR=0.72   ← 用数字证明改写有效
```

这就是"评估 → 优化 → 重评估"的闭环：评估发现问题，改写解决问题，重评估验证效果。

---

## 实验任务

1. 准备 10 个"已知答案"的测试查询（覆盖 5 个知识库文件）
2. 跑评估，得到 Recall@5 / MRR / Top-1
3. 对比 Day 38 的 embedding 模型评估结果
4. 调参（chunk_size、阈值）重跑，看指标变化
5. **找出评估里失败的查询，用同义词扩展或 HyDE 改写，重跑评估对比指标**
6. 写结论：当前检索效果如何？查询改写提升了多少？

---

## 检验标准

1. **一句话**："用已知答案的测试集跑检索，用 Recall@K 看找没找到、MRR 看排得靠不靠前；效果差时先改查询（扩展/HyDE）再改模型"
2. **追问**：Recall@5 是 100% 但 MRR 只有 0.3，说明什么？（都找到了但排得靠后，需要优化相关性排序）
3. **追问**：测试集怎么准备？（从知识库挑代表性内容，改写提问方式模拟真实用户）
4. **追问**：为什么 HyDE 用"假设答案"检索而不是用"问题"检索？（答案和文档同为完整句子，向量空间更接近）

---

## 产出文件

- `rag/evaluate.py` — 检索效果评估脚本
- `rag/query_rewrite.py` — 查询改写实验（同义词扩展 + HyDE）
