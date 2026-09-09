"""
Day45：审查结果引用校验器——治幻觉的代码防线

背景（Day44 实锤）：prompt 治不住幻觉——Agent 没检索过，报告里 basis 照样编 [1]；
三轮 prompt 迭代全部无效。结论：模型没有"自觉"可言，必须用代码核对每个引用的真伪。

四条判定规则（全部来自实测过的幻觉形态）：
  R1 没检索过任何片段 + basis 非 null   → error（100% 幻觉，Day44 vuln_log 实锤）
  R2 basis 编号超出片段范围（如 [5]）   → error（编造编号）
  R3 basis 非空但解析不出编号           → error（格式混乱，Day43 见过 "安全规范[1]"）
  R4 basis 非 [n] 标准格式（如 "1"）    → warning（能定位但格式不标准，Day43 B 组见过）

张冠李戴（编号存在但来源对不上问题）不做硬校验——需要语义判断，误报率高，
verify 结果里带出"basis → 真实来源文件"映射供人工核对，Day47 再决定是否收紧。

用法：
    from rag.citation_check import verify_citations, verdict
    issues = verify_citations(answer_json, all_chunks)   # all_chunks 与 Agent 的跨轮编号对齐
    print(verdict(issues))                                # "PASS" / "REJECTED" / "PASS_WITH_WARNINGS"

设计立场：校验器只"标记 + 降级可信度"，不自动重跑报告（学习场景先观察幻觉率，
Day47 综合实战再考虑"error 即拒绝、强制重审"的生产做法）。
"""

from __future__ import annotations

import re
from dataclasses import dataclass

import sys
import os

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from rag.review_prompts import extract_json


@dataclass
class CitationIssue:
    severity: str      # "error" | "warning"
    issue_idx: int     # 第几条 issue（1 起）
    message: str       # 给人看的说明
    detail: dict       # 机器可读：basis 原值 / 提取编号 / 真实来源（若有）


def extract_basis(basis) -> tuple[int | None, bool]:
    """从 basis 字段提取编号。

    Returns:
        (编号或 None, 是否标准 [n] 格式)

    兼容三种实测格式（Day43/44）：
      "[1]"        → (1, True)   标准
      "1"          → (1, False)  纯数字（Day43 B 组出现过），能定位，格式 warning
      "安全规范[1]" → (1, False)  带杂质的方括号（Day43 A 组幻觉形态），格式 warning
      None/null    → (None, True) 合法（附加建议）
      "OWASP..."   → (None, False) 解析不出编号 → error（R3）
    """
    if basis is None:
        return None, True
    text = str(basis)
    m = re.search(r"\[(\d+)\]", text)          # 优先方括号里的数字
    if m:
        n = int(m.group(1))
        return n, (text.strip() == f"[{n}]")   # 整个值就是 [n] 才算标准格式
    m = re.fullmatch(r"\s*(\d+)\s*", text)      # 退而求其次：纯数字
    if m:
        return int(m.group(1)), False
    return None, False                          # 解析不出（如 "OWASP Top 10"）


def verify_citations(review, chunks: list[dict]) -> list[CitationIssue]:
    """核对审查报告里每条 issue 的 basis 引用真伪。

    Args:
        review: 审查报告（dict，或 JSON 字符串——内部会 extract_json 容错解析）
        chunks: 本次实际检索到的全部片段（Agent 跨轮汇总，编号 = 下标+1）
                空 list = 全程没检索过（R1 的判据）

    Returns:
        CitationIssue 列表；空列表 = 全部引用通过
    """
    if isinstance(review, str):
        try:
            review = extract_json(review)
        except Exception as e:
            return [CitationIssue("error", 0, f"报告 JSON 解析失败，无法校验引用: {e}",
                                  {"raw": str(review)[:200]})]

    n_chunks = len(chunks)
    found: list[CitationIssue] = []

    for i, issue in enumerate(review.get("issues", []), start=1):
        basis = issue.get("basis")
        n, is_std = extract_basis(basis)

        # 合法情况 1：附加建议（basis=null）
        if n is None and is_std:
            continue

        # R3：非空但解析不出编号（编了规范名而不是编号）
        if n is None and not is_std:
            found.append(CitationIssue(
                "error", i,
                f"basis={basis!r} 解析不出片段编号（疑似编造规范名，R3）",
                {"basis": basis}))
            continue

        # R1：没检索过任何片段却给出编号 —— 100% 幻觉（Day44 实锤）
        if n_chunks == 0:
            found.append(CitationIssue(
                "error", i,
                f"全程未检索任何片段，basis={basis!r} 是凭空编造的引用（R1）",
                {"basis": basis, "extracted": n, "real_source": None}))
            continue

        # R2：编号超范围（编造编号）
        if n < 1 or n > n_chunks:
            found.append(CitationIssue(
                "error", i,
                f"basis={basis!r} 的编号 {n} 超出本次检索范围（1-{n_chunks}），疑似编造（R2）",
                {"basis": basis, "extracted": n, "range": f"1-{n_chunks}"}
            ))
            continue

        # 编号真实存在 → 检查格式（R4）+ 带出真实来源供人工核对张冠李戴
        real = chunks[n - 1]
        if not is_std:
            found.append(CitationIssue(
                "warning", i,
                f"basis={basis!r} 能定位到片段 {n}（来源 {real['file']}）但格式不标准（R4）",
                {"basis": basis, "extracted": n, "real_source": real["file"]}))

    return found


def verdict(issues: list[CitationIssue]) -> str:
    """汇总判定：有任何 error → REJECTED；只有 warning → PASS_WITH_WARNINGS；否则 PASS"""
    if any(i.severity == "error" for i in issues):
        return "REJECTED"
    if issues:
        return "PASS_WITH_WARNINGS"
    return "PASS"


if __name__ == "__main__":
    # 自测：四种形态各喂一个（不调 LLM，纯规则验证）
    review = {"issues": [
        {"basis": "[1]", "problem": "标准引用"},                # PASS（有 3 条片段时）
        {"basis": "[5]", "problem": "超范围编号"},               # R2 error
        {"basis": "OWASP Top 10", "problem": "编规范名"},        # R3 error
        {"basis": None, "problem": "附加建议"},                  # 合法跳过
    ]}
    chunks = [{"file": "sql-best-practices.md"}, {"file": "log-best-practices.md"},
              {"file": "http-api-auth.md"}]
    for iss in verify_citations(review, chunks):
        print(f"[{iss.severity}] issue#{iss.issue_idx}: {iss.message}")
    print("verdict:", verdict(verify_citations(review, chunks)))

    # R1 场景：没检索过（Day44 vuln_log 幻觉现场）
    review2 = {"issues": [{"basis": "[1]", "problem": "没检索却引用"}]}
    for iss in verify_citations(review2, []):
        print(f"[{iss.severity}] issue#{iss.issue_idx}: {iss.message}")
    print("verdict:", verdict(verify_citations(review2, [])))
