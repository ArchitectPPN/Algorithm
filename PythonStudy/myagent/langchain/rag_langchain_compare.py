"""
Day46：LangChain RAG 链 vs 裸写链对比

同一个知识库、同一个问题，分别走两种实现：
- 裸写：rag/rag_chain.py（手动 embedding → Chroma → prompt → ollama）
- LangChain：OllamaEmbeddings + Chroma + LCEL 管道

用法：
  python myagent/langchain/rag_langchain_compare.py --query "如何防止 SQL 注入"
"""
from __future__ import annotations

import argparse
import os
import sys
import time

from langchain_community.vectorstores import Chroma
from langchain_core.output_parsers import StrOutputParser
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.runnables import RunnablePassthrough
from langchain_ollama import ChatOllama, OllamaEmbeddings

sys.path.insert(0, os.path.join(os.path.dirname(__file__), "..", ".."))
from rag.embedding_compare import CHROMA_PATH
from rag.rag_chain import RAGChain


def format_docs(docs):
    """把 LangChain Document 列表格式化成上下文"""
    return "\n\n".join(
        f"[来源: {doc.metadata.get('file', '?')}]\n{doc.page_content[:400]}"
        for doc in docs
    )


def build_langchain_chain(model: str = "qwen2.5:3b", top_k: int = 3):
    """组装 LangChain RAG 链：retriever | prompt | llm | parser"""
    vectorstore = Chroma(
        persist_directory=CHROMA_PATH,
        collection_name="kb_bge",
        embedding_function=OllamaEmbeddings(model="bge-m3"),
    )
    retriever = vectorstore.as_retriever(search_kwargs={"k": top_k})
    prompt = ChatPromptTemplate.from_template(
        "你是基于知识库回答问题的助手。只能根据以下片段回答，不要编造。\n\n"
        "【知识库片段】\n{context}\n\n"
        "【问题】{question}\n"
        "【回答】"
    )
    llm = ChatOllama(model=model, temperature=0)
    return (
        {"context": retriever | format_docs, "question": RunnablePassthrough()}
        | prompt
        | llm
        | StrOutputParser()
    )


def run_compare(query: str, model: str = "qwen2.5:3b", top_k: int = 3) -> None:
    """跑同题对比，输出答案、来源和耗时"""
    chain = RAGChain(model=model, top_k=top_k)
    start = time.perf_counter()
    bare_result = chain.ask(query)
    bare_ms = (time.perf_counter() - start) * 1000

    langchain_chain = build_langchain_chain(model=model, top_k=top_k)
    start = time.perf_counter()
    langchain_answer = langchain_chain.invoke(query)
    langchain_ms = (time.perf_counter() - start) * 1000

    print("=" * 72)
    print(f"问题：{query}")
    print("-" * 72)
    print(f"[A 裸写链 {bare_ms:.0f}ms]\n{bare_result.answer}")
    print(f"来源：{[item['file'] for item in bare_result.sources]}")
    print("-" * 72)
    print(f"[B LangChain {langchain_ms:.0f}ms]\n{langchain_answer}")
    print("=" * 72)
    print(
        "结论：LangChain 把 embedding/检索/prompt/LLM/串联封装成 LCEL 管道；"
        "代价是抽象层更厚，排查问题需要进入框架源码。"
    )


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="LangChain RAG 与裸写链对比")
    parser.add_argument("--query", default="如何防止 SQL 注入")
    parser.add_argument("--model", default="qwen2.5:3b")
    parser.add_argument("--top-k", type=int, default=3)
    args = parser.parse_args()
    run_compare(args.query, args.model, args.top_k)
