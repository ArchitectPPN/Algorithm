# Day 31：文本分片策略

> 目标：理解为什么需要分片，实验不同参数的影响。

---

## 学习路线（约 60 分钟）

```
概念（10min）→ 实现固定长度分片（15min）→ 参数实验（20min）→ 结论（15min）
```

---

## 第一步：理解分片概念（10min）

### 为什么不能把整本书当一条向量？

```
整本书 → 1 个 768 维向量 → 语义被稀释成"大概讲什么的"
分段后 → 每段 1 个 768 维向量 → 能精确找到"SQL 注入防护"那一页
```

### 太长 vs 太短

| 分片大小 | 效果 |
|---------|------|
| 太长（2000字） | 语义稀释，检索不精确 |
| 太短（50字） | 上下文丢失，"它"不知道指什么 |
| 适中（500字） | 兼顾语义完整性和检索精度 |

### chunk_overlap 是什么

```
文档：ABCDEFGHIJKLMNOPQRSTUVWXYZ
chunk_size=10, overlap=3

片段1: ABCDEFGHIJ    ← 0-9
片段2: HIJKLMNOPQ    ← 7-16（HIJ 重叠）
片段3: OPQRSTUVWX    ← 14-23（OPQ 重叠）
```

重叠的作用：**防止关键信息正好卡在分片边界上**。

---

## 第二步：实现固定长度分片（15min）

```python
def split_text(text: str, chunk_size: int = 500, chunk_overlap: int = 50) -> list[str]:
    """按字符数分片"""
    chunks = []
    start = 0
    while start < len(text):
        end = start + chunk_size
        chunks.append(text[start:end])
        start += chunk_size - chunk_overlap  # 下一个片段的起点 = 当前位置 + size - overlap
    return chunks

# 测试
text = "A" * 1200  # 1200 个 A
chunks = split_text(text, chunk_size=500, chunk_overlap=50)
for i, chunk in enumerate(chunks):
    print(f"片段 {i}: 长度 {len(chunk)}, 开头 20 字: {chunk[:20]}")
# 预期：3 个片段，相邻片段有 50 字重叠
```

### 练习

自己写出来，然后思考：
- `chunk_overlap=0` 时片段怎么分布？
- 如果 `chunk_overlap >= chunk_size` 会怎样？

---

## 第三步：参数实验（20min）

准备一篇 2000 字的中文文章（用你自己的笔记或找一篇技术文章），测试不同参数：

```python
# 读一篇文章
with open("article.md", encoding="utf-8") as f:
    text = f.read()

# 实验不同参数
for size in [200, 500, 1000]:
    for overlap in [0, 50, 100]:
        chunks = split_text(text, chunk_size=size, chunk_overlap=overlap)
        avg_len = sum(len(c) for c in chunks) / len(chunks) if chunks else 0
        print(f"size={size:4d} overlap={overlap:3d} → {len(chunks):2d} 片段, 平均 {avg_len:.0f} 字")
```

---

## 第四步：输出结论（15min）

回答以下问题：

1. `chunk_size=500, overlap=50` 时产生多少片段？
2. 哪个参数组合最适合你当前的文章？为什么？
3. 如果一个关键概念正好落在两个分片边界上怎么办？（overlap 的作用）

---

## 检验标准

1. **默写**：能写出 `split_text()` 函数
2. **一句话**："分片把长文档切成小段，每段单独做 embedding，chunk_overlap 防止关键信息卡在边界"
3. **追问**：overlap 设多大合适？（一般是 chunk_size 的 10-20%）

---

## 产出文件

`rag/text_splitter.py`