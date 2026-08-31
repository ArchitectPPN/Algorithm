"""Day43：A/B 对照实验——有 RAG vs 无 RAG 的审查结果对比

对 data/test_code/ 下每段埋雷代码，各跑无 RAG（bare 模板）/有 RAG（检索规范）两组，
对比检出情况和 basis 引用真实性。Day47 批量评估会基于此脚本扩展。

用法（在 PythonStudy 目录下执行）：
  python rag/ab_compare.py [--model qwen2.5:3b] [--filter vuln_sql]
"""
import argparse
import json
import pathlib
import sys
import time

import requests

sys.path.insert(0, ".")
from rag.rag_chain import RAGChain
from rag.review_prompts import build_review_prompt, build_review_prompt_bare, extract_json

OLLAMA = "http://localhost:11434"

# 每段代码对应的检索词（B 组用；模拟 Day44 之前人工选词的"固定管道"模式）
SEARCH_TERMS = {
    "vuln_sql.py": "SQL 注入 参数化查询",
    "vuln_log.py": "日志 敏感信息 泄露",
    "vuln_auth.py": "API 鉴权 token 验证",
}


def call_chat(messages, model):
    resp = requests.post(
        f"{OLLAMA}/api/chat",
        json={"model": model, "messages": messages, "stream": False,
              "format": "json",  # ollama 语法层约束：输出必须是合法 JSON（治 3b 未转义引号）
              "options": {"num_predict": 1500}},
        timeout=180,
    )
    resp.raise_for_status()
    return resp.json()


def review(code, use_rag, model, chain):
    if use_rag:
        term = SEARCH_TERMS[pathlib.Path(code_path).name]
        chunks = chain.retrieve(term)
        messages = build_review_prompt(code, chunks)
    else:
        chunks = []
        messages = build_review_prompt_bare(code)
    out = call_chat(messages, model)
    answer = out.get("message", {}).get("content", "")
    usage = out.get("prompt_eval_count", 0) + out.get("eval_count", 0)
    try:
        parsed = extract_json(answer)
        ok = True
    except Exception as e:
        parsed = {"parse_error": str(e), "raw": answer[:300]}
        ok = False
    return {"ok": ok, "parsed": parsed, "chunks": [
        {"file": c["file"], "distance": c["distance"]} for c in chunks], "tokens": usage}


if __name__ == "__main__":
    p = argparse.ArgumentParser()
    p.add_argument("--model", default="qwen2.5:3b")
    p.add_argument("--filter", default=None, help="只跑文件名包含此关键字的用例")
    args = p.parse_args()

    chain = RAGChain(top_k=3)
    results = {}
    for code_path in sorted(pathlib.Path("data/test_code").glob("vuln_*.py")):
        if args.filter and args.filter not in code_path.name:
            continue
        code = code_path.read_text(encoding="utf-8")
        for use_rag in [False, True]:   # A 组先跑
            tag = f"{code_path.name} | {'B有RAG' if use_rag else 'A无RAG'}"
            t0 = time.perf_counter()
            r = review(code, use_rag, args.model, chain)
            r["elapsed_s"] = round(time.perf_counter() - t0, 1)
            results[tag] = r
            issues = r["parsed"].get("issues", []) if r["ok"] else []
            print(f"\n=== {tag}（{r['elapsed_s']}s, {r['tokens']} tok, 解析{'OK' if r['ok'] else 'FAIL'}）===")
            print(f"  检索片段: {[c['file'] for c in r['chunks']]}")
            for i, iss in enumerate(issues, 1):
                print(f"  问题{i}: [{iss.get('severity')}] {iss.get('category')} @ {iss.get('location')}"
                      f" | basis={iss.get('basis')}")
                print(f"        {str(iss.get('problem'))[:80]}")

    out_path = pathlib.Path(__file__).parent / "ab_results_day43.json"
    out_path.write_text(
        json.dumps(results, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"\n完整结果已存 {out_path}")
