# Day 42：裸写 RAG 链——从 /ask 抽取出可复用的 RAGChain

> 目标：把散落在 `rag_pipeline.py` 和 `api/rag_service.py` 里的"检索→拼 prompt→生成"链，
> 抽成一个**独立的进程内组件** `rag/rag_chain.py`。裸写完才知道链上哪一步是代码、哪一步是 LLM。
>
> ⚠️ 与 transition-plan 原 Day 43 的差异：RAG 链其实已在 Day 34（`ask()`）和 Day 40（`/ask`）写过，
> 今天的任务不是从零写，而是**抽取 + 理清封装边界**——因为明天开始 Agent 要把它当库调用，
> 不能再依赖 FastAPI 服务进程。

---

## 学习路线（约 90-120 分钟）

```
复盘现有链（20min）→ 设计接口（15min）→ 实现 RAGChain（45min）→ CLI 测试（20min）
```

---

## 第一步：复盘现有的两条链（20min）

打开两个文件对比着看：

1. `rag/rag_pipeline.py` → `ask()`：余弦粗筛 → **LLM 精筛** → generate（三层过滤架构）
2. `api/rag_service.py` → `ask()`：向量化 → 距离阈值过滤 → 拼 prompt → `/api/generate` 调 qwen2.5:3b

共同的四步骨架，标注每步的性质：

| 步骤 | 性质 | 出错的修法 |
|------|------|-----------|
| ① 查询向量化 | 确定性代码（embedding 模型固定即固定） | 换模型 / 改查询改写 |
| ② 检索 + 过滤 | 确定性代码 | 调 top_k / 阈值 / MMR |
| ③ 拼 prompt | 确定性代码（纯字符串模板） | 改上下文组织方式 |
| ④ LLM 生成 | **黑盒**（不可控，只能靠 prompt 约束） | 换模型 / 改指令 |

> 核心认知：**前三步的质量上限决定第四步的答案质量**（garbage in, garbage out）。
> ④ 不可控但可约束——这就是 Day 43 要学 Prompt 模板的原因。

两条链的差异点也要看清：pipeline 版多一层 LLM 精筛（贵但准），service 版纯靠距离阈值（快但糙）。
`RAGChain` 把精筛做成可选参数，两条链就统一了。

---

## 第二步：设计接口（15min）

调用方视角先定 API，再动手写：

```python
class RAGChain:
    def __init__(self, collection_name="kb_bge", model="qwen2.5:3b",
                 top_k=3, max_distance=0.6, rerank=False): ...

    def retrieve(self, query: str) -> list[dict]:
        """检索：返回 [{content, file, chunk_index, distance}]，Day 44 给 Agent 当工具用"""

    def build_prompt(self, query: str, chunks: list[dict]) -> str:
        """拼 prompt：编号片段 + 引用规则（Day 45 会改成带编号版本）"""

    def ask(self, query: str) -> RAGAnswer:
        """完整链：返回 dataclass(answer, sources, empty, elapsed_ms)"""
```

设计决策（面试可讲）：
- **为什么抽类而不是让 Agent 去 HTTP 调 `/ask`？** 学习场景单机部署，进程内调用少一跳网络、
  少一个"服务必须先起着"的部署耦合。真实分布式系统里 HTTP 解耦反而更好——两种都对，看场景。
- **为什么 `ask()` 返回 dataclass 而不是 str？** sources 是引用和评估的原料，必须结构化带出来。
- **参数为什么进构造函数？** 检索配置（集合/模型/阈值）是链的"身份"，一次固定；query 是每次变的。

---

## 第三步：实现 RAGChain（45min）

```python
"""
Day42：独立 RAG 链组件（不依赖 FastAPI，可被脚本/Agent 进程内调用）

复用 Week6 结论：bge-m3 + kb_bge 集合（cosine）、阈值 0.6（Day40 实测距离分布定的）。
"""

from __future__ import annotations
import os, sys, time
from dataclasses import dataclass, field

import chromadb
import requests

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from rag.embedding_compare import get_embedding_bge, CHROMA_PATH

OLLAMA_URL = "http://localhost:11434"


@dataclass
class RAGAnswer:
    query: str
    answer: str
    sources: list[dict] = field(default_factory=list)  # [{file, chunk_index, distance}]
    empty: bool = False
    elapsed_ms: float = 0.0


class RAGChain:
    def __init__(self, collection_name: str = "kb_bge",
                 model: str = "qwen2.5:3b",
                 top_k: int = 3,
                 max_distance: float = 0.6,
                 llm_timeout: int = 120):
        client = chromadb.PersistentClient(path=CHROMA_PATH)
        self.collection = client.get_collection(name=collection_name)
        self.model = model
        self.top_k = top_k
        self.max_distance = max_distance
        self.llm_timeout = llm_timeout

    # ── ① + ②：向量化 + 检索 + 过滤 ──
    def retrieve(self, query: str, top_k: int | None = None) -> list[dict]:
        res = self.collection.query(
            query_embeddings=[get_embedding_bge(query)],
            n_results=top_k or self.top_k,
        )
        chunks = []
        for content, meta, dist in zip(res["documents"][0], res["metadatas"][0], res["distances"][0]):
            if dist <= self.max_distance:   # Day40 实测：>0.6 基本不相关
                chunks.append({
                    "content": content,
                    "file": meta.get("file", "?"),
                    "chunk_index": meta.get("chunk_index", -1),
                    "distance": round(dist, 3),
                })
        return chunks

    # ── ③：拼 prompt（与 /ask 一致的结构，Day 45 升级为编号引用版） ──
    def build_prompt(self, query: str, chunks: list[dict]) -> str:
        context_text = "\n\n".join(
            f"[来源: {c['file']}]\n{c['content'][:400]}" for c in chunks
        )
        return (
            "你是基于知识库回答问题的助手。\n"
            "只能根据下面提供的知识库片段回答，不要编造。"
            "如果片段不足以回答，就说'知识库中没有相关信息'。\n\n"
            f"【知识库片段】\n{context_text}\n\n"
            f"【问题】{query}\n"
            f"【回答】"
        )

    # ── ④：调 LLM 生成 ──
    def _call_llm(self, prompt: str) -> str:
        resp = requests.post(f"{OLLAMA_URL}/api/generate",
                             json={"model": self.model, "prompt": prompt,
                                   "stream": False,
                                   "options": {"num_predict": 500}},
                             timeout=self.llm_timeout)
        resp.raise_for_status()
        return resp.json()["response"]

    # ── 完整链 ──
    def ask(self, query: str) -> RAGAnswer:
        start = time.perf_counter()
        chunks = self.retrieve(query)
        if not chunks:
            return RAGAnswer(query=query, answer="知识库中没有相关信息。",
                             empty=True, elapsed_ms=(time.perf_counter() - start) * 1000)
        answer = self._call_llm(self.build_prompt(query, chunks))
        return RAGAnswer(
            query=query, answer=answer,
            sources=[{"file": c["file"], "chunk_index": c["chunk_index"],
                      "distance": c["distance"]} for c in chunks],
            elapsed_ms=(time.perf_counter() - start) * 1000,
        )


if __name__ == "__main__":
    import argparse
    p = argparse.ArgumentParser(description="RAGChain CLI")
    p.add_argument("--query", required=True)
    p.add_argument("--model", default="qwen2.5:3b")
    args = p.parse_args()

    chain = RAGChain(model=args.model)
    r = chain.ask(args.query)
    print(f"\n问题: {r.query}\n回答: {r.answer}")
    print(f"\n来源（{len(r.sources)} 条，{r.elapsed_ms:.0f}ms）:")
    for s in r.sources:
        print(f"  - {s['file']} #chunk{s['chunk_index']} (距离 {s['distance']})")
```

⚠️ 注意事项：
- `num_predict: 500` 是 Day40 的经验值——快模型给太大会拖慢且啰嗦。
- 集合用 `get_collection` 不是 `get_or_create_collection`：**宁报错不静默建空库**（Day40 踩过的原则）。
- 代码和 `rag_service.ask()` 高度重复是故意的——先抽取（今天），再让 `/review` 复用（Day 46），
  别今天就回头重构 `/ask`，避免一次改太多。

---

## 第四步：CLI 测试（20min）

```bash
python rag/rag_chain.py --query "如何防止 SQL 注入"
python rag/rag_chain.py --query "Git 分支应该怎么管理"
python rag/rag_chain.py --query "今天下午吃什么"          # 拒答测试：应返回"没有相关信息"
python rag/rag_chain.py --query "如何防止 SQL 注入" --model qwen3:8b   # 换模型对比回答质量
```

检查三件事：① 来源里是否出现 `sql-best-practices.md` / `git-workflow.md`；
② 无关问题是否触发 `empty=True`（阈值 0.6 挡住了）；③ qwen3:8b 和 3b 的回答差距——
**这个差距记下来，Day 44 决定 Agent 用哪个模型**。

---

## 实验任务

- [ ] RAGChain 三条查询全部跑通（含拒答）
- [ ] 对比 3b vs 8b 对同一问题的回答（哪步差异？引用是否更规范？）
- [ ] 把 `max_distance` 改成 0.3 和 0.9 各跑一次，观察 empty 和噪音的变化
- [ ] 思考：`retrieve()` 单独暴露出来，谁会用它？（答案：Day 44 的 Agent 工具）

## 检验标准

- [ ] 能默写 RAGChain 四步骨架，并说出每步"代码可控"还是"LLM 黑盒"
- [ ] 能解释为什么 Agent 集成要抽类而不是 HTTP 调自己的服务
- [ ] 能解释 RAGAnswer 里 sources 字段的两个下游用途（引用展示、效果评估）

## 产出文件

- `rag/rag_chain.py`
- 本文件补充"实验结果"小节
