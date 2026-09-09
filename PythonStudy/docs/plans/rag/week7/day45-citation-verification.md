# Day 45：规范引用输出 + 引用校验——治幻觉

> 目标：让审查 Agent 输出的每条"规范问题"都带**编号引用**（如 `[1]`），
> 再写一段校验代码核对"编号 → 真实片段"的对应关系，把"模型编造不存在的规范条目"这件事堵死。
>
> 前置：Day 43 已在模板里埋了 `basis` 字段和片段编号，今天把它坐实。
> 这是本周的**面试亮点**："你怎么防 RAG 幻觉"的标准答案之一。

---

## 学习路线（约 90-120 分钟）

```
幻觉长什么样（15min）→ 强化引用约束（30min）→ 写校验器（40min）→ 跑测试看效果（20min）
```

---

## 第一步：先看幻觉长什么样（15min）

拿 Day 44 的 Agent 跑 `vuln_log.py`（密码明文进日志），观察输出 JSON 里 `basis` 字段：
- 理想：`basis: "[2]"`，且 `[2]` 确实是 log-best-practices.md 的片段
- **幻觉形态 1**：`basis: "根据 OWASP Top 10 A02"`——编了一个知识库里没有的规范来源
- **幻觉形态 2**：`basis: "[5]"`——但这次只检索了 3 条片段，`[5]` 根本不存在
- **幻觉形态 3**：`basis: "[1]"`，但 `[1]` 是 sql-best-practices.md，跟日志问题无关（张冠李戴）

> 认知：**RAG 不是防幻觉的银弹，它只是给了模型"证据"，模型仍可能不认证据或认错证据。**
> 防幻觉要靠"输出约束 + 事后校验"两道关：prompt 强制编号引用（约束），代码核对编号（校验）。

---

## 第二步：强化引用约束（30min）

改 `review_prompts.py` 的 system 和 format，把"必须引用"讲死：

```python
REVIEW_SYSTEM = (
    "你是资深 Python 代码安全与质量审查专家。\n"
    "【强制规则】每条'规范问题'的 basis 字段必须是【规范片段】里的编号（如 [1]），"
    "不得填写知识库之外的规范名（如 OWASP、PEP 编号等）。\n"
    "如果某问题在【规范片段】中找不到依据，必须归入'附加建议'且 basis 填 null，"
    "严禁把附加建议伪装成规范问题。\n"
    "【规范片段】为空时，只能输出附加建议，不得编造规范问题。"
)
```

在 format 里也重申：
```python
REVIEW_FORMAT = """...
  "issues": [
    {
      ...
      "basis": "必须是上方【规范片段】的编号如 [1]，找不到依据填 null 并把该条归为附加建议",
      ...
    }
  ]
..."""
```

> 关键：约束要写两遍（system 定规则 + format 字段说明重申），单写一处模型容易忽略。

---

## 第三步：写引用校验器（40min）

`rag/citation_check.py`——这是今天的主要产出，把"信不信模型"变成"代码能验"：

```python
"""
Day45：审查结果引用校验器

校验逻辑：
- basis 必须是 [n] 格式，n 在本次检索的片段范围内
- basis 指向的片段来源，要和 problem 的 category 相关（防张冠李戴，软校验只告警）
- 附加建议（basis=null）不计入规范问题统计
"""
from __future__ import annotations
import re, json
from dataclasses import dataclass

@dataclass
class CitationIssue:
    severity: str       # error / warning
    issue_idx: int      # 第几条问题
    message: str

def extract_basis(basis) -> int | None:
    """从 basis 字段提取编号，返回 int 或 None"""
    if basis is None:
        return None
    m = re.search(r"\[(\d+)\]", str(basis))
    return int(m.group(1)) if m else None

def verify_citations(review_json: str | dict, chunks: list[dict]) -> list[CitationIssue]:
    """
    review_json: LLM 输出的审查 JSON（str 或已解析的 dict）
    chunks: 本次实际检索到的规范片段（Day44 retrieve() 的返回）
    """
    if isinstance(review_json, str):
        review_json = json.loads(review_json)   # 容错：解析失败上层处理
    issues_found = []
    n_chunks = len(chunks)
    for i, issue in enumerate(review_json.get("issues", [])):
        basis = issue.get("basis")
        idx = extract_basis(basis)
        # 规则 1：声称是规范问题但 basis 为空 → 报错
        if idx is None and basis is not None:
            issues_found.append(CitationIssue("error", i,
                f"basis '{basis}' 不是 [n] 格式，无法定位规范"))
            continue
        # 规则 2：编号超出范围 → 报错（幻觉形态 2）
        if idx is not None and (idx < 1 or idx > n_chunks):
            issues_found.append(CitationIssue("error", i,
                f"basis [{idx}] 超出本次检索范围（1-{n_chunks}），疑似编造"))
            continue
        # 规则 3：张冠李戴软校验 → 告警
        if idx is not None:
            src_file = chunks[idx-1]["file"]
            cat = issue.get("category", "")
            # 简单关键词映射（可扩展）
            file_cat = {"sql-best-practices": "安全", "log-best-practices": "安全",
                        "http-api-auth": "安全", "python-coding-style": "规范"}
            if any(k in src_file for k in file_cat) and file_cat.get(
                    next(k for k in file_cat if k in src_file)) != cat:
                # 只在 category 明确是"安全"但来源是"规范"类时告警（避免误报）
                pass  # 软校验，先只记录不报错，Day47 再决定阈值
    return issues_found
```

接入 Agent（改 `rag_agent_practice.py`）：

```python
from rag.citation_check import verify_citations

def run_review_agent(code: str, max_loops: int = 5) -> dict:
    ...
    # 记录本次检索到的所有片段（Agent 可能多次调工具，要汇总）
    all_chunks = []
    # 在工具执行处收集：all_chunks.extend(_chain.retrieve(query))
    ...
    result = {... "answer": ..., "chunks": all_chunks}
    # 审查完后跑校验
    try:
        issues = verify_citations(result["answer"], all_chunks)
        result["citation_issues"] = [i.__dict__ for i in issues]
    except Exception as e:
        result["citation_issues"] = [{"severity": "error", "message": f"校验失败: {e}"}]
    return result
```

---

## 第四步：跑测试看效果（20min）

对 3 段测试代码各跑一次，统计：
- 校验器报了几个 error？都是哪种幻觉形态？
- 强化 system 后，幻觉率比 Day 44 降了多少？
- 张冠李戴（形态 3）校验效果如何——软校验有没有误报？

> 预期：强化 system 能消掉大部分形态 1（编外部规范名）；形态 2（超范围编号）靠校验器兜底；
> 形态 3（张冠李戴）最难，软校验先做"有比没有好"，Day 47 综合实战再调。

---

## 实验任务

- [ ] 校验器跑通，能识别"超范围编号"和"非 [n] 格式"两类 error
- [ ] 对比强化 system 前后的幻觉率（用 Day 44 同样的 3 段代码）
- [ ] 记录：校验器有没有误报？什么情况下会误报？

## 检验标准

- [ ] 能说出 RAG 防幻觉的"两道关"（prompt 约束 + 代码校验）各管什么
- [ ] 能解释为什么张冠李戴比超范围编号更难校验
- [ ] 能讲清 basis=null 的设计意图（区分规范问题和附加建议）

## 实验结果（2026-09-09 实测）

### 与教程预期的偏差

教程原计划"第二步：强化引用约束（改 system prompt）"——**跳过了**：
Day44 已实测三轮 prompt 迭代治不住幻觉（不检索照样编 [1]），
今天直接上代码校验（教程第三步），这本身就是 Day44 结论的应用。

### 三段实测：三种判定状态全部自然出现

| 代码 | 检索？ | basis 实际输出 | 校验结果 | 说明 |
|------|--------|---------------|---------|------|
| vuln_sql | ✅（自选词命中 sql-best-practices） | `[1]` 标准格式 | **PASS** | 真实引用放行 |
| vuln_auth | ✅（命中 http-api-auth） | `"1"` 纯数字 | **PASS_WITH_WARNINGS** | R4：能定位到真实片段，只是格式不标准——不是幻觉 |
| vuln_log | ❌（判断"常识级风险"不检索） | `[1]` 凭空编造 | **REJECTED** | R1 精准拦截 Day44 实锤的幻觉现场 |

**拦截率：1 个真幻觉被抓、2 个真引用放行、0 误报（3 段样本）。**

### 关键验证点

**1. R1（没检索 + 非 null basis = 幻觉）按预期精准命中**
vuln_log 的行为和 Day44 完全一致（不检索、编 [1]），校验器直接 REJECTED。
Day44 说"retrievals 为空 + basis 非 null = 100% 幻觉"的判据，实测成立。

**2. R4（格式 warning）的价值：区分"格式怪"和"编造"**
vuln_auth 输出 `"1"` 而不是 `"[1]"`（Day43 见过的格式变体）——校验器提取出编号 1、
定位到真实来源 http-api-auth.md，只报 warning 不拒绝。**能定位到真实片段的引用不是幻觉**，
这个分层避免了一刀切误杀。

**3. 张冠李戴观察素材（留给人工/Day47）**
vuln_auth 三条问题（token 验证/参数校验/HTTPS）全部挂同一条 http-api-auth 片段——
是片段真覆盖多主题，还是轻微张冠李戴？校验器在 detail 里带出了 `real_source` 供人工核对。
语义级张冠李戴自动判定误报率高，维持"带出证据、不自动判"的设计。

### 设计立场（写代码前想清楚的）

- **校验器只"标记 + 降级可信度"，不自动重跑**：学习场景先观察幻觉率；
  Day47 综合实战再考虑"error 即拒绝、强制重审"的生产做法（成本翻倍）
- **格式宽容（纯数字 "1" 能定位就放行 warning）**：抓幻觉是目标，格式洁癖不是——
  一刀切 [n] 格式会把"真实引用但格式怪"的误杀成幻觉

### 边界攻击测试与加固（2026-09-09，学习者攻击性测试驱动）

**起因**：学习者追问"basis 输出格式不固定，假如模型输出 `你[1]好`，代码能处理吗？"

**v1 行为（修复前）**：
- `你[1]好` → **能处理**：宽容提取出编号 1，定位到真实片段，R4 warning 放行（符合设计）
- 但顺着这个思路构造 `"根据[2]和[5]"` → **暴露真 bug**：v1 的 `re.search` 只提取**第一个**编号，
  [2] 在范围内校验即结束——**[5] 是编造的（只检索了 3 条）却完全漏检**，
  带幻觉的报告拿到 PASS_WITH_WARNINGS 放行

**加固（同日）**：
- `extract_basis`（单编号）→ `extract_bases`（`re.findall` 全编号提取）
- R2 改为**逐个校验所有编号，任一超范围即 error**：`"根据[2]和[5]"` → R2 抓住 [5] → REJECTED
- 自测用例从 4 种扩到 6 种（新增多引用幻觉、噪声嵌编号）
- 顺带踩坑：f-string 嵌套索引 `f"[{nums[0]]}]"` 括号配对语法错——嵌套取下标时用 `.format()` 或中间变量

**保留的设计权衡（想清楚才保留的）**：
`你[1]好` 这类"噪声文本嵌编号"仍宽容放行（R4 warning）——因为 `"安全规范[1]"`
这类"真实语义引用但带杂质"和它走的是同一条路径，收紧会把前者误杀。
宽容提取的代价就是接受"编号可能不是真在指片段"的误放行，靠 R4 warning 留痕供人工复核。
**根治方向**是输出端硬约束（结构化生成限定 basis 只能是 `^[n]$` 或 null），
ollama 目前不支持，记为生产方向。

> 方法论记录：这是"能对代码中不正确的位置提出自己的意见"标准的第一次实战收益——
> 学习者的一个格式边界追问，直接挖出一个幻觉绕过路径。校验器自己也需要被攻击测试，
> 和 LLM 输出需要被校验是同一个道理：**任何防线都要假设它会被绕着走**。

## 产出文件

- `rag/citation_check.py`（四条判定规则 + verdict 三态 + 多编号校验加固 + 6 用例自测）
- `myagent/rag_agent_practice.py`（接入校验：结果带 citation_issues / citation_verdict，CLI 展示）
- 本文件"实验结果"小节（含边界攻击测试记录）
