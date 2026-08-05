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

### overlap 的局限

overlap 是**盲切**——不管内容边界在哪，只是机械地重叠 N 个字符：

- overlap 太短：关键信息还是可能被切断
- overlap 太长：重复内容增多，浪费 token，语义又开始稀释
- 不管 overlap 多长，**都有可能把一个完整的句子从中间劈开**

所以 overlap 只是一个"廉价补救"，不是根本解决方案。

### 更好的方案：按语义边界切

**方案1：按段落/标题切（最实用）**

Markdown 文档天然有结构——用 `##` 标题作为分片边界：

```
## SQL 注入防护          ← 天然边界
内容内容内容...
## 密码存储最佳实践      ← 天然边界
内容内容内容...
```

这样切出来的片段语义完整。只有当章节超过 chunk_size 时，才在章节内部按固定长度二次切分 + overlap。

**方案2：递归分片（LangChain 默认策略）**

按优先级依次尝试分割符：

1. 先按 `\n\n`（段落）切
2. 段落太长 → 按 `\n`（换行）切
3. 还是太长 → 按 `。`（句号）切
4. 还是太长 → 按固定字符数切 + overlap

每一层都尽量在语义边界上切，实在切不了才退回盲切。

**方案3：语义分片（最精确，成本最高）**

用 Embedding 计算相邻句子的相似度，相似度骤降的地方就是语义边界：

```
句子1: "SQL 注入是最常见的安全漏洞"     ──→ 相似度 0.85
句子2: "使用参数化查询可以防止注入"     ──→ 相似度 0.82
句子3: "API 接口应该使用 HTTPS"         ──→ 相似度 0.35 ← 骤降，这里是边界
句子4: "状态码 200 表示成功"            ──→ 相似度 0.88
```

最精确，但每个句子都要调一次 Embedding API，成本高速度慢。生产环境少用。

**实际工程的常见做法**：

| 方案 | 适用场景 | 成本 |
|------|---------|------|
| 固定长度 + overlap | 快速原型、非结构化文本 | 低 |
| 按标题/段落切 | Markdown/HTML 等结构化文档 | 低 |
| 递归分片 | 通用方案（LangChain 默认） | 低 |
| 语义分片 | 精度要求极高的场景 | 高 |

生产中最常用：**先按标题/段落切（保证语义完整），段落内太长再用固定长度 + overlap 补底**。

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

1. `chunk_size=500, overlap=50` 时产生多少片段？→ **12 个片段**
2. 哪个参数组合最适合你当前的文章？为什么？→ **这篇文档有清晰的 `##` 标题结构，固定长度分片都不合适——不管参数怎么调都会切断句子。按标题切才是正解，固定长度分片只适合没有结构的纯文本**
3. 如果一个关键概念正好落在两个分片边界上怎么办？→ **overlap 能缓解但不能根治，它只是"廉价补丁"。根本解决方案是按语义边界切（标题/段落/递归分片），固定长度 + overlap 只是无结构文本的兜底方案**

### Day31 实验结论

- 固定长度分片是**盲切**，不管参数怎么调都会存在句子被截断的情况
- overlap 只是补丁，无法完全避免边界问题
- 有结构的文档（Markdown/HTML）应优先按标题/段落切
- 固定长度分片的价值在于：无结构文本的兜底方案 + 更高级分片策略的底层工具

---

## 检验标准

1. **默写**：能写出 `split_text()` 函数
2. **一句话**："分片把长文档切成小段，每段单独做 embedding，chunk_overlap 防止关键信息卡在边界"
3. **追问**：overlap 设多大合适？（一般是 chunk_size 的 10-20%）

---

## 产出文件

`rag/data_chunk_example.py`