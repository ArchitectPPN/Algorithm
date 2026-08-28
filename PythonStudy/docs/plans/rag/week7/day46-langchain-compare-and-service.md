# Day 46：LangChain RAG 对比 + /review 端点服务化 + git 提交

> 目标：用 LangChain 的 RAG 链跑一遍同样的审查，对比裸写封装了哪几步、省了多少代码、
> 代价是什么；再把审查 Agent 包成 `POST /review` 端点接入 FastAPI 服务，提交到 GitHub。
>
> 前置：Day 42-45 裸写链已完整；`myagent/langchain/` 已有 4 个 LangChain 示例（quickstart/create_agent/git_agent/three_ways）。
> ⚠️ 压缩日：LangChain 了解级已在 Git Agent 阶段完成，今天只补 **RAG 场景**的对比，不从头学 LangChain。

---

## 学习路线（约 120-150 分钟）

```
LangChain RAG 对比（60min）→ /review 端点（40min）→ 测试 + 提交（30min）
```

---

## 第一步：LangChain RAG 对比（60min）

### 1.1 裸写链回顾（5min）

你的 `RAGChain.ask()` 四步：`get_embedding_bge` → `collection.query` → 拼 prompt → `requests.post(/api/generate)`。
每一步都是手写，逻辑透明。

### 1.2 LangChain RAG 链实现（35min）

`myagent/langchain/rag_langchain_compare.py`：

```python
"""
Day46：LangChain RAG 链 vs 裸写对比

用 LangChain 跑同一份知识库、同一个问题，看它封装了什么。
"""
import os, sys, warnings
warnings.filterwarnings("ignore")

from langchain_community.vectorstores import Chroma
from langchain_ollama import OllamaEmbeddings, ChatOllama
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import StrOutputParser
from langchain_core.runnables import RunnablePassthrough

sys.path.insert(0, os.path.join(os.path.dirname(__file__), "..", ".."))
from rag.embedding_compare import CHROMA_PATH

# ① Embedding（LangChain 包了 ollama embeddings）
embeddings = OllamaEmbeddings(model="bge-m3")

# ② VectorStore（LangChain 包了 Chroma 连接 + 检索）
vectorstore = Chroma(persist_directory=CHROMA_PATH,
                     collection_name="kb_bge",
                     embedding_function=embeddings)
retriever = vectorstore.as_retriever(search_kwargs={"k": 3})

# ③ Prompt 模板（LangChain 用模板变量替代 f-string）
prompt = ChatPromptTemplate.from_template(
    "你是基于知识库回答问题的助手。只能根据以下片段回答，不要编造。\n\n"
    "【知识库片段】\n{context}\n\n【问题】{question}\n【回答】"
)

# ④ LLM（LangChain 包了 ollama chat 调用）
llm = ChatOllama(model="qwen2.5:3b")

# ⑤ 链组装（LCEL 管道符，这是 LangChain 的核心封装）
def format_docs(docs):
    return "\n\n".join(f"[来源: {d.metadata.get('file','?')}]\n{d.page_content[:400]}" for d in docs)

rag_chain = (
    {"context": retriever | format_docs, "question": RunnablePassthrough()}
    | prompt
    | llm
    | StrOutputParser()
)

# 跑一次
if __name__ == "__main__":
    answer = rag_chain.invoke("如何防止 SQL 注入")
    print(answer)
```

### 1.3 逐项对比（20min）

填这张表（这是今天最重要的产出，面试可直接讲）：

| 步骤 | 裸写代码 | LangChain 封装 | 省了多少 | 代价 |
|------|---------|---------------|---------|------|
| Embedding | `requests.post` + 手动取 `["embedding"]` | `OllamaEmbeddings(model=...)` | 5 行→1 行 | 多一层抽象，调试要翻源码 |
| 检索 | `collection.query(...)` + 手动解包 `documents[0]` | `retriever.invoke(q)` | 3 行→1 行 | 返回 Document 对象要适应 |
| 拼 prompt | f-string | `ChatPromptTemplate.from_template` | 持平 | 模板语法学习成本 |
| 调 LLM | `requests.post(/api/generate)` + 取 `["response"]` | `ChatOllama` + `\| llm` | 3 行→1 行 | 黑盒，报错信息不直观 |
| 串联 4 步 | 手动顺序调用 | LCEL `\|` 管道 | **核心价值**：4 步变 1 条表达式 | 读懂 LCEL 要时间 |

> 结论模板（写进笔记）：
> **LangChain 帮你封装了"检索→拼 prompt→调模型"的管道编排（LCEL），代码量少一半。**
> **代价是每一层都加抽象，调试时报错要翻框架源码；且 LCEL 的 `\|` 语义和 Python 原生不一致，有学习成本。**
> **生产慎用的原因**：breaking change 多（0.1→0.2 大改）、抽象层厚导致性能问题和调试困难。
> 本项目选裸写，是为了面试时能讲清每一步——框架帮你藏起来的，恰恰是面试官想问的。

---

## 第二步：/review 端点服务化（40min）

扩展 `api/rag_service.py`，把 Day 44 的审查 Agent 包成 HTTP 端点：

```python
from rag.rag_chain import RAGChain
from rag.review_prompts import build_review_prompt
from rag.citation_check import verify_citations

class ReviewRequest(BaseModel):
    code: str = Field(..., min_length=1, max_length=10000, description="待审查代码")
    use_rag: bool = Field(True, description="是否启用 RAG 检索规范")
    model: str = Field("qwen3:8b")

@app.post("/review", tags=["审查"])
def review(req: ReviewRequest) -> dict:
    """RAG 增强代码审查：检索规范 → 审查 → 引用校验"""
    start = time.perf_counter()
    chain = RAGChain(top_k=3)
    chunks = chain.retrieve(_infer_review_query(req.code)) if req.use_rag else []
    messages = build_review_prompt(req.code, chunks) if req.use_rag else build_review_prompt_bare(req.code)
    answer = _call_chat(messages, req.model)   # 注意换 /api/chat 支持 system role
    citation_issues = verify_citations(answer, chunks) if req.use_rag else []
    return {
        "review": answer, "use_rag": req.use_rag,
        "sources": [{"file": c["file"], "chunk_index": c["chunk_index"]} for c in chunks],
        "citation_issues": [i.__dict__ for i in citation_issues],
        "elapsed_ms": (time.perf_counter() - start) * 1000,
    }
```

⚠️ 两个实现点：
1. `_infer_review_query()`：简单做法是取代码里的关键词（如 `execute`、`password`、`token`）映射到规范类别；
   进阶做法是先让 LLM 用一轮调用提取"这段代码涉及哪些规范主题"——后者更准但贵，先用简单版。
2. `_call_chat()`：要在 rag_service 里新加一个走 `/api/chat`（messages 格式）的调用函数，
   因为审查模板用了 system role，`/api/generate`（单 prompt）给不了 system 约束。

---

## 第三步：测试 + git 提交（30min）

```bash
# 启动
uvicorn api.rag_service:app --reload --port 8000

# 测试 /review
curl -X POST http://localhost:8000/review \
  -H "Content-Type: application/json" \
  -d '{"code": "import sqlite3\ndef get_user(n): conn=sqlite3.connect(\"a.db\"); return conn.execute(f\"SELECT * FROM u WHERE name=\\\"{n}\\\"\").fetchall()", "use_rag": true}'

# Swagger: http://localhost:8000/docs 看 /review
```

检查：① 返回 JSON 可解析；② sources 有 sql-best-practices.md；③ citation_issues 为空（或只有 warning）。

git 提交（按项目提交风格）：
```bash
git add rag/rag_chain.py rag/review_prompts.py rag/citation_check.py \
        myagent/rag_agent_practice.py myagent/langchain/rag_langchain_compare.py \
        data/test_code/ api/rag_service.py docs/plans/rag/week7/
git commit -m "feat: Day42-46 RAG+Agent 整合（RAGChain + 审查Agent + 引用校验 + /review端点 + LangChain对比）"
```

---

## 实验任务

- [ ] LangChain RAG 链跑通，和裸写结果对比（同一问题答案质量差异？）
- [ ] 对比表填完（5 步的"省了多少/代价"）
- [ ] /review 端点跑通，Swagger 可见
- [ ] 代码提交到 GitHub

## 检验标准

- [ ] 能默写 LangChain RAG 链的 LCEL 五步管道
- [ ] 能说出 LangChain 封装的核心价值（LCEL 管道编排）和两个代价（抽象厚/调试难）
- [ ] 能讲清为什么本项目选裸写（面试要讲每一步）

## 产出文件

- `myagent/langchain/rag_langchain_compare.py`
- `api/rag_service.py`（新增 /review）
- 对比表（写进本文件或单独笔记）
