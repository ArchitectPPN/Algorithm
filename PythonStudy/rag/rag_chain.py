"""
Day42：独立 RAG 链组件（不依赖 FastAPI，可被脚本/Agent 进程内调用）

把散在 rag_pipeline.py（ask）和 api/rag_service.py（/ask 端点）里的
"检索 → 拼 prompt → 生成"链，抽成一个可复用的进程内组件。

复用 Week6 结论：bge-m3 + kb_bge 集合（cosine）、阈值 0.6（Day40 实测距离分布定的）。
裸写四步骨架（前三步可控、第四步 LLM 黑盒）：
  ① 查询向量化   ② 检索 + 距离过滤   ③ 拼 prompt   ④ 调 LLM 生成

CLI 用法：
  python rag/rag_chain.py --query "如何防止 SQL 注入"
  python rag/rag_chain.py --query "如何防止 SQL 注入" --model qwen3:8b
"""

from __future__ import annotations

import argparse
import os
import sys
import time
from dataclasses import dataclass, field

import chromadb
import requests

# 添加父目录到路径，方便导入 rag 包
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from rag.embedding_compare import get_embedding_bge, CHROMA_PATH

OLLAMA_URL = "http://localhost:11434"


@dataclass
class RAGAnswer:
    """RAG 链的返回（sources 是引用展示和效果评估的原料，必须结构化带出来）"""

    query: str
    answer: str
    sources: list[dict] = field(default_factory=list)  # [{file, chunk_index, distance}]
    empty: bool = False
    elapsed_ms: float = 0.0


class RAGChain:
    """RAG 完整链：检索 → 拼 prompt → 生成（进程内组件，不依赖 FastAPI）

    与 api/rag_service.py 的 /ask 端点对比：
    - /ask 走 HTTP，多一跳网络、要求服务先起着；本类进程内调用，少部署耦合。
    - 学习场景单机部署用本类更直接；真实分布式系统用 HTTP 解耦反而更好——看场景。
    """

    def __init__(
        self,
        collection_name: str = "kb_bge",
        model: str = "qwen2.5:3b",
        top_k: int = 3,
        max_distance: float = 0.6,
        llm_timeout: int = 120,
    ):
        # 检索配置是链的"身份"，一次固定；query 每次变（进 ask 参数）
        # 用 get_collection 不是 get_or_create_collection：宁报错不静默建空库（Day40 原则）
        client = chromadb.PersistentClient(path=CHROMA_PATH)
        self.collection = client.get_collection(name=collection_name)
        self.model = model
        self.top_k = top_k
        self.max_distance = max_distance
        self.llm_timeout = llm_timeout

    # ── ① + ②：向量化 + 检索 + 距离过滤 ──
    def retrieve(self, query: str, top_k: int | None = None) -> list[dict]:
        """检索：返回 [{content, file, chunk_index, distance}]，Day44 给 Agent 当工具用。

        单独暴露 retrieve() 是为了让 Agent 能"只检索不生成"——Agent 要先看片段再决定怎么用。
        """
        res = self.collection.query(
            query_embeddings=[get_embedding_bge(query)],
            n_results=top_k or self.top_k,
        )
        chunks = []
        for content, meta, dist in zip(
            res["documents"][0], res["metadatas"][0], res["distances"][0]
        ):
            if dist <= self.max_distance:  # Day40 实测：>0.6 基本不相关
                chunks.append(
                    {
                        "content": content,
                        "file": meta.get("file", "?"),
                        "chunk_index": meta.get("chunk_index", -1),
                        "distance": round(dist, 3),
                    }
                )
        return chunks

    # ── ③：拼 prompt（与 /ask 一致的结构，Day45 升级为编号引用版） ──
    def build_prompt(self, query: str, chunks: list[dict]) -> str:
        """拼 prompt：纯字符串模板（确定性代码），上下文组织方式改这里"""
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

    # ── ④：调 LLM 生成（黑盒，只能靠 prompt 约束） ──
    def _call_llm(self, prompt: str) -> str:
        """调用 ollama 生成模型，返回回答文本"""
        resp = requests.post(
            f"{OLLAMA_URL}/api/generate",
            json={
                "model": self.model,
                "prompt": prompt,
                "stream": False,
                "options": {"num_predict": 500},  # Day40 经验值：快模型给太大反而拖慢且啰嗦
            },
            timeout=self.llm_timeout,
        )
        resp.raise_for_status()
        return resp.json()["response"]

    # ── 完整链：检索 → 拼 prompt → 生成 ──
    def ask(self, query: str) -> RAGAnswer:
        """端到端问答：返回 RAGAnswer（answer + sources + empty + elapsed_ms）"""
        start = time.perf_counter()
        chunks = self.retrieve(query)

        # 没有相关片段 → 直接返回空回答，不调 LLM（省一次调用）
        if not chunks:
            return RAGAnswer(
                query=query,
                answer="知识库中没有相关信息。",
                empty=True,
                elapsed_ms=(time.perf_counter() - start) * 1000,
            )

        answer = self._call_llm(self.build_prompt(query, chunks))
        return RAGAnswer(
            query=query,
            answer=answer,
            sources=[
                {
                    "file": c["file"],
                    "chunk_index": c["chunk_index"],
                    "distance": c["distance"],
                }
                for c in chunks
            ],
            elapsed_ms=(time.perf_counter() - start) * 1000,
        )


if __name__ == "__main__":
    p = argparse.ArgumentParser(description="RAGChain CLI —— 裸写 RAG 链测试")
    p.add_argument("--query", required=True, help="查询内容")
    p.add_argument("--model", default="qwen2.5:3b", help="生成模型（默认 qwen2.5:3b）")
    p.add_argument("--top-k", type=int, default=3, help="返回条数")
    p.add_argument(
        "--max-distance",
        type=float,
        default=0.6,
        help="距离阈值（超过丢弃，默认 0.6）",
    )
    args = p.parse_args()

    chain = RAGChain(model=args.model, top_k=args.top_k, max_distance=args.max_distance)
    r = chain.ask(args.query)
    print(f"\n问题: {r.query}")
    print(f"回答: {r.answer}")
    print(f"\n来源（{len(r.sources)} 条，{r.elapsed_ms:.0f}ms，empty={r.empty}）:")
    for s in r.sources:
        print(f"  - {s['file']} #chunk{s['chunk_index']} (距离 {s['distance']})")
