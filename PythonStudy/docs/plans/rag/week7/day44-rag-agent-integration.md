# Day 44：RAG 集成进 Agent——search_knowledge 工具 + ReAct loop

> 目标：把 Day 42 的 `RAGChain.retrieve()` 包成一个 Agent 工具 `search_knowledge`，
> 接进 ReAct loop，让 Agent **自主决定要不要检索、检索什么**。
> 再做"固定 RAG 管道 vs Agent 检索 vs 无 RAG"三方案对照实验，讲清两种集成模式的取舍。
>
> 前置：Day 42 RAGChain、Day 43 审查 Prompt 模板。
> 复用骨架：`myagent/git_agent_practice.py` 的 ReAct loop（已跑通）。

---

## 学习路线（约 120-150 分钟）

```
理解集成模式（20min）→ 包工具（30min）→ 接 ReAct loop（50min）→ 三方案对照（30min）
```

---

## 第一步：两种集成模式的区别（20min）

这是本周最容易混、也最常被面试问的点，先把概念钉死：

| 模式 | 流程 | 检索时机 | 谁决定查什么 | 适用场景 |
|------|------|---------|------------|---------|
| **固定 RAG 管道** | 查询→检索→拼 prompt→生成 | 每次必查 | 代码写死（query=用户输入） | 问答机器人、检索即答案 |
| **Agent 检索（工具）** | 用户输入→Agent 思考→[调工具?]→生成 | Agent 判断要不要查 | **LLM 决定**（可改写、可多查、可不查） | 代码审查、多步推理、可多工具 |

> 关键差异：**固定管道"总是查、查原话"；Agent"该查才查、查该查的"**。
>
> 代码审查场景为什么适合 Agent 模式？审查一段 SQL 代码，Agent 可能：
> ① 先看代码识别出是"SQL 拼接"风险 → 决定查"SQL 注入"规范（改写了查询词）
> ② 再看用了明文密码 → 再查"日志规范"
> ③ 看到一段干净的工具函数 → 不查，直接放过
> 固定管道做不到 ① 的"先识别风险再针对性检索"和 ③ 的"不查"。
>
> 代价：Agent 模式多几轮 LLM 调用、token 消耗高、可控性差（可能该查的不查）。
> 所以"简单问答"用固定管道、"复杂判断"才上 Agent——这是 Day 47 对比报告的核心论点。

---

## 第二步：包 search_knowledge 工具（30min）

Agent 工具 = 函数 + JSON Schema 描述。`myagent/rag_agent_practice.py`：

```python
"""
Day44：RAG 增强代码审查 Agent（裸写 ReAct loop）

复用：
- rag/rag_chain.py 的 RAGChain（检索能力）
- rag/review_prompts.py 的审查模板（生成能力）
- myagent/git_agent_practice.py 的 ReAct loop 骨架
"""
from __future__ import annotations
import os, sys, json, requests

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from rag.rag_chain import RAGChain
from rag.review_prompts import build_review_prompt

OLLAMA_URL = "http://localhost:11434"
LLM_MODEL = "qwen3:8b"   # Day42 对比后选 8b：审查需要更强推理（Day44 实验验证）

# ── 工具：检索知识库 ──
_chain = RAGChain(top_k=3, max_distance=0.6)

def search_knowledge(query: str) -> str:
    """检索代码规范知识库，返回相关规范片段（带来源）。"""
    chunks = _chain.retrieve(query)
    if not chunks:
        return "未检索到相关规范。"
    lines = []
    for i, c in enumerate(chunks, start=1):
        lines.append(f"[{i}] 来源: {c['file']}\n{c['content'][:400]}")
    return "\n\n".join(lines)

# 工具表（给 LLM 的 function calling 描述）
TOOLS = [{
    "type": "function",
    "function": {
        "name": "search_knowledge",
        "description": "检索代码规范知识库（SQL/日志/鉴权/API 等），获取审查依据。当需要判断代码是否符合某类规范时调用。",
        "parameters": {
            "type": "object",
            "properties": {
                "query": {"type": "string", "description": "检索关键词，如 'SQL 注入 参数化查询'"}
            },
            "required": ["query"],
        },
    },
}]
TOOL_FUNCS = {"search_knowledge": search_knowledge}
```

⚠️ 工具设计要点：
- `description` 是 LLM 决策的唯一依据，要写清"什么时候该调"——模糊描述会导致该调不调。
- `query` 的描述给例子（"SQL 注入 参数化查询"），引导 LLM 用"风险类型 + 关键词"检索而不是原话。
- 工具返回**带编号的文本**（不是 JSON）——ReAct loop 里工具结果要塞回 LLM 上下文，文本更直接。

---

## 第三步：接 ReAct loop（50min）

复用 `git_agent_practice.py` 的循环骨架，核心改动三处：

```python
def call_llm(messages: list, tools: list = None) -> dict:
    """调 ollama /api/chat，支持 function calling"""
    payload = {"model": LLM_MODEL, "messages": messages, "stream": False,
               "tools": tools, "options": {"num_predict": 800}}
    resp = requests.post(f"{OLLAMA_URL}/api/chat", json=payload, timeout=120)
    resp.raise_for_status()
    return resp.json()

def run_review_agent(code: str, max_loops: int = 5) -> dict:
    """RAG 增强审查 Agent：思考→(检索)→审查→输出"""
    messages = [
        {"role": "system", "content": (
            "你是 Python 代码审查专家。审查代码时，如需依据规范判断，"
            "调用 search_knowledge 检索相关规范。最终输出 JSON 审查报告。")},
        {"role": "user", "content": f"请审查以下代码：\n{code}"},
    ]
    for step in range(max_loops):
        out = call_llm(messages, TOOLS)
        msg = out.get("message", {})
        # 有工具调用 → 执行工具 → 结果塞回 → 继续
        if msg.get("tool_calls"):
            messages.append(msg)
            for tc in msg["tool_calls"]:
                fn, args = tc["function"]["name"], json.loads(tc["function"]["arguments"])
                result = TOOL_FUNCS[fn](**args)
                messages.append({"role": "tool", "tool_name": fn, "content": result})
            continue
        # 无工具调用 → 最终回答
        return {"answer": msg.get("content", ""), "steps": step + 1}
    return {"answer": "(达到最大循环)", "steps": max_loops}
```

⚠️ ollama function calling 的坑（提前记，省调试时间）：
- tool_calls 的 `arguments` 是**字符串**要 `json.loads`，不是 dict。
- tool 消息的 role 是 `"tool"`，ollama 还要带 `tool_name` 字段（OpenAI 是 `tool_call_id`，别混）。
- num_predict 给 800：审查报告 + 多轮思考比单轮问答费 token。

---

## 第四步：三方案对照实验（30min）

对同一段 `vuln_sql.py`，跑三个方案：

| 方案 | 实现 | 检索触发 |
|------|------|---------|
| A 无 RAG | `build_review_prompt_bare(code)` 直接调 LLM | 不检索 |
| B 固定管道 | `RAGChain.retrieve("SQL 注入")`（写死查询词）→ `build_review_prompt` | 必检索，词固定 |
| C Agent | `run_review_agent(code)`（今天写的） | LLM 自主决定 |

记录三件事：
1. **埋雷检出率**：A/B/C 各发现 vuln_sql.py 的几处问题？
2. **token 消耗**：ollama 返回的 `prompt_eval_count + eval_count`，C 比 B 贵多少？
3. **检索质量**：C 的 Agent 实际用了什么 query 去检索？比 B 写死的"SQL 注入"更准还是更差？

> 预期：C 检出率 ≥ B > A；C token ≈ B 的 2-3 倍（多几轮思考）。
> 如果 C 没比 B 好——说明 Agent 的检索词选得差，回去改工具 description 或换 8b 模型。

---

## 实验任务

- [ ] `rag_agent_practice.py` 跑通，Agent 至少调一次 search_knowledge
- [ ] 三方案对照表完成（检出率/token/检索词质量）
- [ ] 记录 Agent 实际用的检索词，对比"写死词"的差距

## 检验标准

- [ ] 能默写固定管道 vs Agent 模式的三处差异（检索时机/谁决定/适用场景）
- [ ] 能解释代码审查为什么适合 Agent 模式（先识别风险再针对性检索）
- [ ] 能说出 Agent 模式比固定管道贵在哪、什么时候不值

## 实验结果（2026-09-01 实测）

### ⚠️ 计划与实测的偏差（实现时已对齐）

- 教程预想"ollama tool_calls 的 arguments 是字符串要 json.loads"——**实测（2026-08-31 探测）新版 ollama 直接返回 dict**，代码已做 str/dict 双兼容
- 教程预想"C 检出率 ≥ B > A"——**实测部分不成立**：Agent 的"该查才查"判断比预期保守（详见发现 1），检出没输但 basis 出了幻觉（详见发现 2）
- qwen3:8b 必须加 `"think": false`——否则 content 混入 `<think>` 思考标签，污染 JSON 输出

### Agent 行为实录（qwen3:8b，temperature=0，每段稳定复现）

| 代码 | 检索？ | Agent 自选检索词 | 命中 | basis |
|------|--------|----------------|------|-------|
| vuln_sql | ✅ 2 轮 | `'SQL 注入 参数化查询'` | sql-best-practices ✓ | 真实引用 |
| vuln_auth | ✅ 2 轮 | `'安全 请求头验证'` | http-api-auth ✓ | 真实引用 |
| vuln_log | ❌ 1 轮直答 | —（判断"无需规范依据"） | — | **编造 `[1]`**（幻觉） |

### 关键发现

**1. Agent"该查才查"的真实标准：凭通用知识答不了才查**
vuln_log 的"密码进日志"是常识级风险，模型认为不需要规范依据 → 不调工具直接回答；
SQL 注入/鉴权这类"需要具体规范条文"的才主动查。
→ Agent 模式的检索覆盖**依赖模型自评**，常识级风险容易漏检索——
审查场景想要"每条意见都有依据"，纯自主检索不够（Day47 考虑混合模式：代码侧保底检索 + Agent 自主补充）。

**2. 不检索 ≠ basis=null：模型会编造编号（幻觉引用实锤）**
vuln_log 全程没检索（retrievals 空），但报告 basis 填了 `[1]`——上下文里根本没有编号片段。
**system prompt 三轮迭代（收紧检索标准 → 禁止编造编号 → user 消息引导顺序）都治不住**：
模型注意力被"输出 JSON 报告"占满，basis 字段的示例"如 [1]"反而诱导它照着填。
→ 结论：**幻觉引用不能靠 prompt 治，必须代码校验**——Day45 校验器的必要性今天被直接证明
（校验逻辑已有素材：retrievals 为空 + basis 非 null = 100% 幻觉，可直接判 error）。

**3. temperature=0 是审查 Agent 的硬前提**
ollama 默认 t=0.8。实测同一代码（vuln_auth）：t=0.8 时有时查有时不查（随机），
t=0 后稳定检索。**不控温，Agent 行为不可复现，对照实验无效**（Day47 批量评估的前提）。

**4. Agent 自选检索词质量：可用，但不总比人工词准**
vuln_auth 自选 `'安全 请求头验证'`（Day43 人工词是 `'API 鉴权 token 验证'`）——
两者都命中 http-api-auth.md。自选词的价值不在更准，在**无人预先标注风险类别也能查**（真实场景用户只丢代码）。

### 成本对比（同 8b，单次耗时波动大仅供参考）

| 方案 | LLM 调用 | 检索 | 耗时 |
|------|---------|------|------|
| A 无 RAG | 1 | 0 | ~120s |
| B 固定管道 | 1（长 prompt） | 1（人工词） | ~126s |
| C Agent（查） | 2 轮 | 1-2（自选词） | 72-97s |
| C Agent（不查） | 1 | 0 | ~17s |

→ C 的 token 成本 ≈ B 的 1.5-2 倍（多一轮"看代码→决定检索"的调用），
但换来：无人预标注的自主检索 + 多轮多类别检索能力 + 不该查时省一次。

## 产出文件

- `myagent/rag_agent_practice.py`（Agent 本体：工具 + ReAct loop + 跨轮编号 + CLI）
- 本文件"实验结果"小节
