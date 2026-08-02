# Day 32：文档加载 + 分片脚本 + 准备知识库

> 目标：写一个完整的加载→分片→统计脚本，并准备 RAG 知识库文档。

---

## 学习路线（约 60 分钟）

```
文档加载（15min）→ 分片封装（15min）→ 准备知识库（20min）→ 跑通流程（10min）
```

---

## 第一步：实现 Markdown 文件加载（15min）

```python
import os

def load_markdown(file_path: str) -> str:
    """读取 .md 文件，返回纯文本"""
    if not os.path.exists(file_path):
        raise FileNotFoundError(f"文件不存在: {file_path}")
    with open(file_path, encoding="utf-8") as f:
        return f.read()

def load_all_docs(directory: str) -> list[dict]:
    """加载目录下所有 .md 文件，返回 [{file, content}, ...]"""
    docs = []
    for filename in os.listdir(directory):
        if filename.endswith(".md"):
            path = os.path.join(directory, filename)
            docs.append({
                "file": filename,
                "content": load_markdown(path)
            })
    return docs
```

---

## 第二步：封装分片函数（15min）

把 Day 31 的分片逻辑封装成带统计的版本：

```python
def chunk_document(doc: dict, chunk_size: int = 500, chunk_overlap: int = 50) -> list[dict]:
    """对单个文档分片，返回 [{file, chunk_index, content, token_estimate}, ...]"""
    chunks = []
    text = doc["content"]
    start = 0
    idx = 0
    while start < len(text):
        end = start + chunk_size
        chunk_text = text[start:end]
        chunks.append({
            "file": doc["file"],
            "chunk_index": idx,
            "content": chunk_text,
            "token_estimate": len(chunk_text) // 1.5,  # 中文约 1 字符 = 1.5 tokens
        })
        start += chunk_size - chunk_overlap
        idx += 1
    return chunks

def chunk_all_docs(docs: list[dict], chunk_size=500, chunk_overlap=50) -> list[dict]:
    """对所有文档分片"""
    all_chunks = []
    for doc in docs:
        all_chunks.extend(chunk_document(doc, chunk_size, chunk_overlap))
    return all_chunks

def print_stats(chunks: list[dict]):
    """打印分片统计"""
    print(f"总片段数: {len(chunks)}")
    print(f"平均长度: {sum(len(c['content']) for c in chunks) / len(chunks):.0f} 字")
    print(f"预估 Token: {sum(c['token_estimate'] for c in chunks):.0f}")
    print(f"涉及文件: {len(set(c['file'] for c in chunks))} 个")
```

---

## 第三步：准备 RAG 知识库文档（20min）

用你熟悉的领域写 3-5 篇 .md 文档，放到 `data/knowledge/` 下。每篇 500-1000 字。

**选题建议（选 3-5 个）：**

1. `python-coding-style.md` — Python 编码规范（命名、缩进、注释）
2. `api-design.md` — API 接口设计原则（RESTful、状态码、版本管理）
3. `sql-best-practices.md` — 数据库操作规范（参数化查询、索引、事务）
4. `error-handling.md` — 错误处理最佳实践（异常捕获、日志、降级）
5. `git-workflow.md` — Git 协作规范（分支策略、commit message、PR 流程）

---

## 第四步：跑通完整流程（10min）

```python
# 加载 → 分片 → 统计
docs = load_all_docs("data/knowledge")
chunks = chunk_all_docs(docs, chunk_size=500, chunk_overlap=50)
print_stats(chunks)

# 预览前 3 个片段
for chunk in chunks[:3]:
    print(f"\n[{chunk['file']}] 片段 {chunk['chunk_index']}:")
    print(chunk['content'][:100] + "...")
```

---

## 检验标准

1. **默写**：能写出 `load_markdown()` + `chunk_document()` 的核心逻辑
2. **一句话**："文档加载→分片→统计，每段预估 Token 数，为向量化做准备"
3. **追问**：如果文档是 PDF 怎么办？（用 PyPDF2 或 pdfplumber 先转文本）

---

## 产出文件

- `learning/rag/document_loader.py` — 加载 + 分片 + 统计脚本
- `data/knowledge/` — 3-5 篇知识库 .md 文档