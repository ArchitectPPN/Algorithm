# Day 34：端到端 RAG Pipeline

> 目标：把 Day 29-33 串联成完整的 RAG Pipeline。

---

## 学习路线（约 4 小时）

```
封装 Pipeline（40min）→ 端到端测试（30min）→ CLI 入口（20min）→ 代码整理（30min）→ 休息 → 可选优化（60min）
```

---

## 第一步：封装 RAG Pipeline 类（40min）

```python
"""myagent/rag_pipeline.py —— RAG 全流程封装"""
import os
import requests
import chromadb
from typing import Optional

class RAGPipeline:
    """RAG 完整流程：加载 → 分片 → 向量化 → 存储 → 检索"""

    def __init__(self, knowledge_dir: str = "data/knowledge",
                 chroma_path: str = "./chroma_data",
                 chunk_size: int = 500, chunk_overlap: int = 50):
        self.knowledge_dir = knowledge_dir
        self.chunk_size = chunk_size
        self.chunk_overlap = chunk_overlap

        # 初始化 Chroma
        self.client = chromadb.PersistentClient(path=chroma_path)
        self.collection = self.client.get_or_create_collection("knowledge_base")

    # ── 文档加载 ──
    def _load_docs(self) -> list[dict]:
        docs = []
        for filename in os.listdir(self.knowledge_dir):
            if filename.endswith(".md"):
                with open(os.path.join(self.knowledge_dir, filename), encoding="utf-8") as f:
                    docs.append({"file": filename, "content": f.read()})
        return docs

    # ── 分片 ──
    def _split(self, text: str) -> list[str]:
        chunks = []
        start = 0
        while start < len(text):
            chunks.append(text[start:start + self.chunk_size])
            start += self.chunk_size - self.chunk_overlap
        return chunks

    # ── Embedding ──
    def _embed(self, text: str) -> list[float]:
        resp = requests.post(
            "http://localhost:11434/api/embeddings",
            json={"model": "nomic-embed-text", "prompt": text}
        )
        return resp.json()["embedding"]

    # ── 构建索引（一次性） ──
    def build_index(self):
        """将知识库所有文档分片→向量化→存入 Chroma"""
        docs = self._load_docs()
        print(f"加载了 {len(docs)} 个文档")

        total_chunks = 0
        for doc in docs:
            chunks = self._split(doc["content"])
            for i, chunk in enumerate(chunks):
                embedding = self._embed(chunk)
                self.collection.add(
                    ids=[f"{doc['file']}_{i}"],
                    embeddings=[embedding],
                    documents=[chunk],
                    metadatas=[{"file": doc["file"], "chunk_index": i}]
                )
                total_chunks += 1
            print(f"  {doc['file']}: {len(chunks)} 个片段")

        print(f"索引构建完成，共 {total_chunks} 个片段")

    # ── 检索 ──
    def search(self, query: str, top_k: int = 3) -> list[dict]:
        """语义检索"""
        query_vec = self._embed(query)
        results = self.collection.query(
            query_embeddings=[query_vec],
            n_results=top_k,
            include=["documents", "metadatas", "distances"]
        )
        output = []
        for i in range(len(results["ids"][0])):
            output.append({
                "content": results["documents"][0][i][:200],
                "file": results["metadatas"][0][i]["file"],
                "distance": results["distances"][0][i],
            })
        return output

    # ── 格式化输出 ──
    def search_pretty(self, query: str, top_k: int = 3):
        results = self.search(query, top_k)
        print(f"\n查询: {query}\n{'='*50}")
        for i, r in enumerate(results):
            print(f"\n[{i+1}] {r['file']}  (距离: {r['distance']:.3f})")
            print(f"    {r['content']}...")
        return results
```

---

## 第二步：端到端测试（30min）

```python
# 测试脚本
from rag_pipeline import RAGPipeline

# 1. 构建索引（只需运行一次）
rag = RAGPipeline(knowledge_dir="data/knowledge")
# rag.build_index()  # 首次运行需要，之后注释掉

# 2. 检索测试
test_queries = [
    "Python 变量怎么命名",
    "API 接口设计有什么规范",
    "数据库查询应该注意什么",
    "Git 提交信息格式",
    "代码出错怎么处理",
]

for q in test_queries:
    rag.search_pretty(q, top_k=2)
```

---

## 第三步：CLI 入口（20min）

```python
"""search.py —— 命令行检索入口"""
import sys
from rag_pipeline import RAGPipeline

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("用法: python search.py <查询内容>")
        sys.exit(1)

    query = sys.argv[1]
    rag = RAGPipeline()
    rag.search_pretty(query)

# 使用: python search.py "如何防止 SQL 注入"
```

---

## 第四步：代码整理（30min）

- 检查每个函数有 docstring
- 检查异常处理（文件不存在、API 失败）
- 确认 `data/knowledge/` 有至少 3 篇文档
- 代码提交到 GitHub

---

## 第五步（可选优化，60min）

- 增量添加文档（不重建整个索引）
- 检索结果缓存（相同查询不重复调 Embedding）
- 加分片去重（完全相同的内容不重复存储）

---

## 检验标准

1. **默写**：能写出 `RAGPipeline` 的核心结构（load → split → embed → store → search）
2. **一句话**："加载→分片→向量化→存库→查询转向量→余弦粗筛→LLM 精筛→相关片段为空则拒绝回答→否则基于片段生成回答"
3. **追问**：如果知识库更新了怎么办？（调 `build_index()` 重建，或实现增量添加）

---

## 全流程逻辑（严谨版）—— 默写题的标准答案

> 来源：用户复述 → 校正后的严谨版。写代码前，先把这版逻辑在脑子里讲顺。
> 对应实际文件：`rag/rag_pipeline.py`

### 三层过滤一图流

```
用户问题
  │
  ├─ 第1层 粗筛：search()     问题→向量 → Chroma 余弦检索 top_k=5 → 相似度 < 0.5 丢弃
  │     ↓ 候选片段（可能相关，宁松勿紧）
  ├─ 第2层 精筛：rerank()     deepseek-r1:7b 逐条判断，只留 {"related": true}
  │     ↓ 相关片段
  ├─ [安全闸] 精筛为空 → 拒绝回答，不调用生成
  │
  └─ 第3层 生成：generate()   相关片段作上下文 + 提示词 → LLM 回答
```

### 分步严谨版（对应函数）

| 步 | 做什么 | 对应代码 | 关键点 |
|----|--------|----------|--------|
| 1 | 加载文档 | `load_all_docs()` | 读 `data/knowledge/` 下所有 .md |
| 2 | 分片 | `chunk_all_docs()` | `chunk_size=500, overlap=50`（Day31） |
| 3 | 向量化 | `_embed()` | 文本 → 768 维向量（带缓存） |
| 4 | 存储 | `collection.add()` | **4 样**：ids / embeddings / documents / metadatas |
| 5 | 查询转向量 | `_embed(query)` | 问题和片段在同一向量空间才能比相似度 |
| 6 | 粗筛 | `search()` | 余弦 top_k + `min_similarity=0.5` 阈值 |
| 7 | 精筛 | `rerank()` | R1 输出 JSON 结论，只认布尔 `true` |
| 8 | 安全闸 | `ask()` | 精筛为空 → 拒绝生成（防幻觉） |
| 9 | 生成 | `generate()` | 相关片段 + 提示词 → 回答，不足则说没有 |

### 三处最容易讲错 / 漏的

1. **精筛模型不是"小模型"，是推理模型**：精筛用 `deepseek-r1:7b`（推理模型）。它先思考再回答，所以 `num_predict` 必须给足 **400+**，否则思考占满 token 配额、正文还没输出就返回空字符串（坑 3）。选它的理由是**判断稳**（坑 4：lfm2 那类真小模型不遵守指令，早被淘汰）。

2. **安全闸不能省**：
   ```python
   if not relevant:
       return {"answer": "知识库中没有与问题相关的内容。", "empty": True}
   ```
   没有相关片段就**不生成**。片段不相关时，LLM 会强行编造圆场（坑 6 的红烧肉幻觉），这道闸是防幻觉的根本——**检索是上限**。

3. **术语要能对上函数名**：粗筛 = 余弦 `search()`，精筛 = LLM `rerank()`，生成 = `generate()`。三层名字记混，代码就找不到函数。

### 一句话严谨版

> "加载 → 分片 → 向量化 → 存库（向量+原文+元数据）→ 查询转向量 → 余弦粗筛（阈值 0.5）→ LLM 精筛（判相关）→ 相关片段为空则拒绝回答 → 否则喂给 LLM 基于片段生成回答"

---

## FAQ：分片 → 向量化 → 存储，存的是什么？

**问**：`build_index()` 里"分片 → 向量化 → 存储"这条链路，存储的是向量数据吗？

**答**：是向量，但**不止向量**。Chroma 一条记录存了 4 样东西：

| 字段 | 内容 | 例子 | 作用 |
|------|------|------|------|
| `ids` | 唯一标识 | `chunk_0` | 定位/去重/删除 |
| `embeddings` | **向量**（768维 float） | `[0.12, -0.34, ...]` | 相似度检索的核心 |
| `documents` | **原文** | `"# 数据库规范 ## SQL注入防护..."` | 检索到后返回给用户/LLM 的内容 |
| `metadatas` | **元数据** | `{"file": "sql-best-practices.md", "chunk_index": 0}` | 标注来源、可过滤 |

对应代码（`rag_pipeline.py` 的 `build_index`）：

```python
for i, chunk in enumerate(chunks):
    embedding = self._embed(chunk["content"])   # 向量化：文本 → 768维向量
    ids.append(f"chunk_{i}")
    embeddings.append(embedding)                 # 存的向量
    documents.append(chunk["content"])           # 存的原文
    metadatas.append({"file": ..., "chunk_index": ...})  # 存的元数据

self.collection.add(
    ids=ids,              # 唯一ID
    embeddings=embeddings,  # 向量数据
    documents=documents,    # 原文文本
    metadatas=metadatas,    # 来源信息
)
```

### 为什么要同时存原文，不只存向量？

向量只是**用来算相似度**的，但最终用户要的是**原文片段**：

```
用户查询"如何防止SQL注入"
  → 查询向量化 → 和库里的向量算余弦相似度 → 找到最像的 chunk_0
  → 返回 chunk_0 的 documents（原文）给 LLM 生成回答
```

如果只存向量不存原文，检索到了也**不知道这条向量对应什么文字**，没法生成回答。所以 Chroma 是"向量 + 原文 + 元数据"一起存——**向量负责"找"，原文负责"用"**。

### 链路对应关系

```
build_index() 的链路：
  分片    chunk_all_docs()        → [{content, file, chunk_index}, ...]
  向量化  self._embed(content)    → [0.12, -0.34, ...] 768维向量
  存储    collection.add()        → ids + embeddings + documents + metadatas 存进 Chroma
```

> 💡 验证方式：用 `collection.peek(limit=5)` 能直接看到存进去的 4 样东西。

---

## FAQ：短查询（10字）怎么命中长片段（500字）？向量差距不会很大吗？

**问**：分片是 500-1000 字的长文本，我的问题只有 10-30 字，向量难道不会差距非常大吗？怎么保证查到的就是我想要的？

**答**：这是 RAG 最核心的疑问之一。先看真实数据（实测验证，非想象）：

| | 短查询「如何防止SQL注入」(9字) | 长片段 (500字) |
|---|---|---|
| 向量维度 | 768 维 | **768 维（一样！）** |
| 向量模长 | 23.01 | 19.88（很接近！） |
| 相关查询 vs 长片段 | **相似度 0.782** | ← 短查询成功命中长片段 |
| 无关查询「怎么做红烧肉」 vs 长片段 | 相似度 0.610 | ← 明显更低 |

### 关键：向量维度固定，跟字数无关

你的直觉是"500字 → 大向量，10字 → 小向量"，但 embedding 不是这么算的。它是把整段文本的**语义**压缩进一个固定大小的向量（768 维），语义相近的向量就靠近，**跟字数多少无关**。实测两个向量模长也接近（23 vs 20），长度差异根本没造成悬殊。

### 相似度靠的是「语义重合」，不是长度

9 字的查询"如何防止SQL注入"和 500 字片段讲的"SQL注入防护、参数化查询"**语义重合**，所以向量靠得近（0.78）。无关查询"怎么做红烧肉"和同一片段语义不重合，只有 0.61。**差距来自语义，不是长度。**

### 你的直觉其实点到了真问题

短查询 vs 长片段确实是 RAG 的经典难点，但不在"向量大小"，而在**语义稀释**：

1. 500 字片段里有各种信息（索引、事务、SQL注入…），如果查询只命中片段一小部分，相似度会被拉低
2. 这正是**不设 chunk_size=10000 的原因**（Day31：太长 → 语义稀释）。500 字是在"语义完整"和"检索精度"间取平衡
3. **精筛兜底**：三层过滤里的 `rerank`（LLM 精筛）是最后一道闸——余弦粗筛找"可能相关"，LLM 判断"到底相不相关"。即使余弦给的分不高，精筛能兜住，不会把无关的当相关的返回

**一句话**：向量编码的是**语义**不是长度，短查询和长片段维度一样、可比；命中靠语义重合，兜底靠 LLM 精筛。

### 认知确认（换个角度理解黑盒）

- **不是"拆分"，是"压缩"**：不是"9字拆9格、500字拆500格"，而是"9字一句话 → 压成1个768维坐标，500字一段 → 也压成1个768维坐标"。两个坐标在**同一个空间**，语义近就靠得近，**不存在谁格子多谁占优势**。
- **黑盒 + 局限**：embedding 模型内部是神经网络，看不到也管不着。但它不只是黑，还**不完美**：
  - 中文效果一般（nomic-embed-text 是英文模型，中文语义捕捉不完美）
  - 语义被稀释（500字片段含索引/事务/SQL注入，压缩后是"混合语义"）
  - 区分度有限（实测相关的 0.64 和无关的 0.68 会重叠）
- **为什么不能只靠余弦**：向量说"可能相关"，但到底相不相关，得让 LLM 看内容后拍板。这就是三层过滤的精筛环节存在的意义。

---

## 产出文件

- `rag/rag_pipeline.py` — 完整 Pipeline 类 + CLI 入口
- `rag/chroma_demo.py` — Chroma 向量化 + 检索演示