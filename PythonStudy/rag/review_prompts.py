"""
Day43：代码审查 Prompt 模板

两套模板（Day44/47 对照实验用）：
- build_review_prompt(code, chunks)：RAG 版，带规范片段上下文
- build_review_prompt_bare(code)：无 RAG 版（对照组）

设计四要素：
- 角色（system）：决定模型用什么视角/标准审查
- 上下文（user 开头）：【规范片段】+【待审查代码】，标题分隔
- 输出格式（user 结尾）：JSON 结构化 → 可被代码解析 → 可统计
- 边界（system 重申）：规范外的建议归"附加建议"，不得伪装成"规范问题"

Day45 会在 REVIEW_SYSTEM 里加"强制编号引用"约束（basis 只能填 [n]）。

用法（配合 ollama /api/chat，messages 格式）：
    from rag.rag_chain import RAGChain
    from rag.review_prompts import build_review_prompt

    chain = RAGChain()
    chunks = chain.retrieve("SQL 注入 参数化查询")
    messages = build_review_prompt(code, chunks)
    # → POST http://localhost:11434/api/chat {"messages": messages, ...}
"""

from __future__ import annotations

# ── system：角色 + 边界 ──
REVIEW_SYSTEM = (
    "你是资深 Python 代码安全与质量审查专家。\n"
    "你只依据【规范片段】中的内容给出'规范问题'，禁止编造不存在的规范。\n"
    "超出规范片段、但凭专业经验认为重要的问题，放进'附加建议'并注明不引用规范。"
)

# ── 输出格式：JSON 结构化（可解析 → 可统计 → Day45 可校验引用） ──
REVIEW_FORMAT = """请只输出如下 JSON（不要输出其他内容）：
{
  "summary": "一句话总体评价",
  "issues": [
    {
      "location": "行号或函数名",
      "severity": "high|medium|low",
      "category": "安全|性能|可维护性|规范",
      "problem": "问题描述",
      "basis": "依据的规范片段编号，如 [1]；附加建议填 null",
      "suggestion": "具体修复建议，给出关键修改点（最多 3 行代码，不要贴完整函数）"
    }
  ]
}"""


def format_chunks(chunks: list[dict]) -> str:
    """规范片段 → 带编号的上下文块（编号 [1] [2]... 供 basis 字段引用）

    编号是 Day45 引用约束的基础——没有编号，模型没法"挂证据"。
    """
    lines = []
    for i, c in enumerate(chunks, start=1):
        lines.append(f"[{i}] 来源: {c['file']}\n{c['content'][:400]}")
    return "\n\n".join(lines) if lines else "（未检索到相关规范）"


def build_review_prompt(code: str, chunks: list[dict]) -> list[dict]:
    """RAG 审查模板：返回 messages 列表（system + user），供 /api/chat 使用"""
    user = (
        f"【规范片段】\n{format_chunks(chunks)}\n\n"
        f"【待审查代码】\n{code}\n\n"
        f"{REVIEW_FORMAT}"
    )
    return [
        {"role": "system", "content": REVIEW_SYSTEM},
        {"role": "user", "content": user},
    ]


def build_review_prompt_bare(code: str) -> list[dict]:
    """无 RAG 对照模板：只给角色不给规范片段"""
    user = f"【待审查代码】\n{code}\n\n{REVIEW_FORMAT}"
    return [
        {"role": "system", "content": "你是资深 Python 代码安全与质量审查专家。"},
        {"role": "user", "content": user},
    ]


def extract_json(text: str) -> dict:
    """从容错文本中提取 JSON（模型可能带 ```json 围栏或解释文字）

    策略：找第一个 { 到最后一个 }，中间的当 JSON 解析。
    解析失败抛 ValueError，由调用方决定重试还是标记失败。
    """
    import json

    start, end = text.find("{"), text.rfind("}")
    if start == -1 or end == -1 or end <= start:
        raise ValueError(f"输出中找不到 JSON 结构: {text[:100]}...")
    return json.loads(text[start : end + 1])
