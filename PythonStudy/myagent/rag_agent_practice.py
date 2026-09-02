"""
Day44：RAG 增强代码审查 Agent（裸写 ReAct loop + search_knowledge 工具）

复用：
- rag/rag_chain.py 的 RAGChain（检索能力，Day42）
- rag/review_prompts.py 的 REVIEW_FORMAT / extract_json（输出格式，Day43）
- myagent/git_agent_practice.py 的 ReAct loop 骨架（call → tool_calls? → 执行 → 塞回 → 循环）

与固定管道（Day43 ab_compare.py B 组）的区别：
- 固定管道：代码写死检索词，每次必查
- Agent：LLM 看代码识别风险，自己决定查不查、查什么、查几轮

ollama function calling 实测格式（2026-08-31 探测）：
- 响应 message.tool_calls[].function.arguments 直接是 dict（不是字符串，与 OpenAI 不同）
- tool 结果回传：role=tool + tool_call_id（带 tool_name 兼容旧版）
- qwen3:8b 需 "think": false 关闭思考模式（否则 content 混入 <think>）

片段编号跨轮连续（第一轮 [1][2][3]、第二轮 [4][5]...），
保证最终 basis 引用全局唯一——Day45 引用校验的前提。

CLI 用法（在 PythonStudy 目录下执行）：
  python myagent/rag_agent_practice.py --file data/test_code/vuln_sql.py
  python myagent/rag_agent_practice.py --file data/test_code/vuln_sql.py --model qwen3:8b
"""

from __future__ import annotations

import argparse
import json
import os
import sys
import time

import requests

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from rag.rag_chain import RAGChain
from rag.review_prompts import REVIEW_FORMAT, extract_json

OLLAMA_URL = "http://localhost:11434"

# ── 工具：检索知识库（包 Day42 的 RAGChain.retrieve） ──
_chain = RAGChain(top_k=3, max_distance=0.6)


def search_knowledge(query: str) -> str:
    """检索代码规范知识库，返回带编号的相关规范片段。

    编号由调用方（run_review_agent）跨轮连续分配，所以这里只返回原始片段。
    """
    chunks = _chain.retrieve(query)
    if not chunks:
        return "未检索到相关规范。"
    return json.dumps(chunks, ensure_ascii=False)  # 编号在主循环统一分配后格式化


# 工具表（给 LLM 的 function calling 描述——description 是 LLM 决策的唯一依据）
TOOLS = [
    {
        "type": "function",
        "function": {
            "name": "search_knowledge",
            "description": (
                "检索代码规范知识库（SQL 最佳实践/日志规范/API 鉴权/错误处理等），"
                "获取代码审查的规范依据。当需要判断代码是否符合某类规范、"
                "或想确认某类问题（如 SQL 注入、敏感信息泄露）的规范要求时调用。"
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "query": {
                        "type": "string",
                        "description": "检索关键词，如 'SQL 注入 参数化查询'、'日志 敏感信息'",
                    }
                },
                "required": ["query"],
            },
        },
    }
]

TOOL_FUNCS = {"search_knowledge": search_knowledge}

# ── system prompt：角色 + 工具使用规则 + 输出格式（Day43 的四要素全带上） ──
SYSTEM_PROMPT = (
    "你是资深 Python 代码安全与质量审查专家。\n"
    "你可以调用 search_knowledge 检索代码规范知识库。\n\n"
    "工作方式：\n"
    "1. 先仔细阅读代码，识别潜在风险点（安全/性能/规范）\n"
    "2. 对每个风险点，调用 search_knowledge 检索对应规范（每类风险一次，检索词用'风险类型+关键词'）\n"
    "3. 结合检索到的规范片段给出审查报告——有规范依据的意见比凭经验的判断更有说服力\n"
    "4. 只当代码完全干净、确实找不出任何风险时，才不调用工具直接给出结论\n\n"
    "引用规则（强制）：basis 只能引用 search_knowledge 返回的【检索结果】里真实存在的编号"
    "（如 [1]）。没有检索到对应规范的问题，basis 必须填 null——"
    "宁可空着也不得编造编号，编造编号的审查报告会被系统拒绝。\n\n"
    f"最终输出格式（只输出 JSON）：\n{REVIEW_FORMAT}"
)


def call_chat(messages: list, model: str, tools: list | None = None) -> dict:
    """调 ollama /api/chat（think:false 关 qwen3 思考模式，实测不关会混 <think>）"""
    resp = requests.post(
        f"{OLLAMA_URL}/api/chat",
        json={
            "model": model,
            "messages": messages,
            "tools": tools,
            "stream": False,
            "think": False,
            "options": {"num_predict": 1500, "temperature": 0},  # 审查要确定性：t=0（ollama 默认 0.8 有随机性）
        },
        timeout=300,
    )
    resp.raise_for_status()
    return resp.json()


def run_review_agent(code: str, model: str = "qwen3:8b", max_loops: int = 5) -> dict:
    """RAG 增强审查 Agent：思考 →（自主检索）→ 审查 → JSON 报告

    Returns:
        {"answer"(str), "answer_json"(dict|None), "steps"(int),
         "retrievals"(list[{"query","chunks":[{file,distance}]}]),
         "chunks"(list[dict] 全部片段，编号=下标+1，Day45 校验用),
         "elapsed_ms"}
    """
    start = time.perf_counter()
    messages = [
        {"role": "system", "content": SYSTEM_PROMPT},
        {"role": "user", "content": (
            "请审查以下代码。工作顺序：先用 search_knowledge 检索代码涉及的"
            "每类风险对应的规范（一类风险查一次），拿到片段后再输出带 basis 编号的审查报告。\n\n"
            f"{code}"
        )},
    ]
    all_chunks: list[dict] = []      # 跨轮汇总，编号 = 下标+1，全局唯一
    retrievals: list[dict] = []      # 记录 Agent 每次检索的词和命中（三方案对照的核心数据）

    for step in range(1, max_loops + 1):
        resp = call_chat(messages, model, TOOLS)
        msg = resp.get("message", {})
        tool_calls = msg.get("tool_calls")

        if not tool_calls:
            # 无工具调用 → 最终回答
            answer = msg.get("content", "")
            try:
                answer_json = extract_json(answer)
            except (ValueError, json.JSONDecodeError):
                answer_json = None
            return {
                "answer": answer,
                "answer_json": answer_json,
                "steps": step,
                "retrievals": retrievals,
                "chunks": all_chunks,
                "elapsed_ms": (time.perf_counter() - start) * 1000,
            }

        # 有工具调用 → 执行 → 结果塞回 → 继续循环
        messages.append(msg)  # assistant 消息（带 tool_calls 意图）入历史
        for tc in tool_calls:
            fn = tc["function"]["name"]
            raw_args = tc["function"]["arguments"]
            # ollama 实测 arguments 已是 dict；兼容字符串形式（OpenAI 格式）
            args = json.loads(raw_args) if isinstance(raw_args, str) else (raw_args or {})

            if fn not in TOOL_FUNCS:
                # 容错：错误信息回传给模型，让它自己纠正（复用 git_agent 的模式）
                messages.append({
                    "role": "tool",
                    "tool_call_id": tc.get("id", ""),
                    "tool_name": fn,
                    "content": f"没有名为 {fn} 的工具，可用工具: {list(TOOL_FUNCS)}",
                })
                continue

            # 执行检索 + 跨轮连续编号
            query = args.get("query", "")
            new_chunks = _chain.retrieve(query)
            base_idx = len(all_chunks)  # 本轮编号起点
            all_chunks.extend(new_chunks)
            retrievals.append({
                "query": query,
                "chunks": [{"file": c["file"], "distance": c["distance"]} for c in new_chunks],
            })
            numbered = "\n\n".join(
                f"[{base_idx + i}] 来源: {c['file']}\n{c['content'][:400]}"
                for i, c in enumerate(new_chunks, start=1)
            ) or "未检索到相关规范。"
            messages.append({
                "role": "tool",
                "tool_call_id": tc.get("id", ""),
                "tool_name": fn,
                "content": numbered,
            })

    return {
        "answer": f"(达到最大循环 {max_loops}，未给出最终回答)",
        "answer_json": None,
        "steps": max_loops,
        "retrievals": retrievals,
        "chunks": all_chunks,
        "elapsed_ms": (time.perf_counter() - start) * 1000,
    }


if __name__ == "__main__":
    p = argparse.ArgumentParser(description="Day44 RAG 增强代码审查 Agent")
    p.add_argument("--file", required=True, help="待审查代码文件路径")
    p.add_argument("--model", default="qwen3:8b")
    p.add_argument("--max-loops", type=int, default=5)
    args = p.parse_args()

    code = open(args.file, encoding="utf-8").read()
    result = run_review_agent(code, args.model, args.max_loops)

    print(f"\n{'=' * 60}")
    print(f"审查文件: {args.file}（{result['steps']} 轮，{result['elapsed_ms'] / 1000:.1f}s）")
    print(f"{'=' * 60}")

    print("\n【Agent 自主检索记录】")
    if not result["retrievals"]:
        print("  （未调用检索——Agent 判断无需规范依据）")
    for r in result["retrievals"]:
        files = ", ".join(c["file"] for c in r["chunks"]) or "无命中"
        print(f"  查询词: {r['query']!r} → {files}")

    print("\n【审查报告】")
    print(result["answer"])

    if result["answer_json"]:
        issues = result["answer_json"].get("issues", [])
        print(f"\n共 {len(issues)} 条问题，basis 引用:")
        for i, iss in enumerate(issues, 1):
            print(f"  {i}. {iss.get('basis')} | {str(iss.get('problem'))[:60]}")
