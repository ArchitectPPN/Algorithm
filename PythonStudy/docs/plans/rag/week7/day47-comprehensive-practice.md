# Day 47：综合实战——RAG 增强代码审查 Agent 完善（10 段测试 + 对比报告）

> 目标：把本周做的 RAGChain + 审查 Agent + 引用校验串成完整流程，
> 准备 10 段测试代码（2 干净 + 8 埋雷），跑"有 RAG vs 无 RAG"对照实验，
> 产出一份**能写进简历的对比报告**（带数字）。这是 Week7 的里程碑产出。
>
> 前置：Day 42-46 全部完成，/review 端点可调。

---

## 学习路线（约 5-6 小时）

```
扩测试集（60min）→ 跑对照实验（90min）→ 分析数据（60min）→ 完善Agent（90min）→ 写报告（60min）
```

---

## 第一步：扩充测试集到 10 段（60min）

`data/test_code/` 下 10 个文件，覆盖知识库里的多类规范：

| 文件 | 埋雷类型 | 对应规范 | 严重度 |
|------|---------|---------|--------|
| `vuln_sql.py` | f-string 拼 SQL | sql-best-practices | high |
| `vuln_log.py` | 密码明文进日志 | log-best-practices | high |
| `vuln_auth.py` | token 不验签 + IDOR | http-api-auth | high |
| `vuln_bare_except.py` | `except:` 吞所有异常 | error-handling | medium |
| `vuln_n plus_hardcoded.py` | 硬编码密钥 | frontend-security | high |
| `vuln_no_cache.py` | 每次查库不缓存 | cache-design | low |
| `vuln_long_function.py` | 200 行单函数 | python-coding-style | low |
| `vuln_no_pagination.py` | API 返回全表 | api-design | medium |
| `clean_util.py` | 干净的工具函数 | —（不该报问题） | — |
| `clean_handler.py` | 干净的请求处理 | —（不该报问题） | — |

> 设计意图：8 个问题覆盖 7 个规范文件（验证检索广度）；2 个干净代码测**误报率**
> （无 RAG 时模型可能凭"感觉"挑刺，有 RAG 应该更克制）。

每段代码控制在 10-30 行，埋雷点明确（写注释标注预期应发现的行号，方便评估）。

---

## 第二步：跑对照实验（90min）

对 10 段代码各跑两遍：

| 组 | 端点 | use_rag |
|----|------|---------|
| A 无 RAG | `POST /review {"code": ..., "use_rag": false}` | false |
| B 有 RAG | `POST /review {"code": ..., "use_rag": true}` | true |

记录每次结果（写个脚本 `rag/run_eval_batch.py` 批量跑 + 存 JSON）：

```python
import json, requests, pathlib

cases = sorted(pathlib.Path("data/test_code").glob("*.py"))
results = []
for f in cases:
    code = f.read_text(encoding="utf-8")
    for use_rag in [False, True]:
        r = requests.post("http://localhost:8000/review",
                          json={"code": code, "use_rag": use_rag}).json()
        r["file"] = f.name; r["use_rag"] = use_rag
        results.append(r)
        print(f"{f.name} use_rag={use_rag}: {len(json.loads(r['review']).get('issues',[]))} issues")
pathlib.Path("rag/eval_results_day47.json").write_text(
    json.dumps(results, ensure_ascii=False, indent=2), encoding="utf-8")
```

人工评估每个结果（这是 RAG 评估"人工基线"，Week8 会换成自动评估）：

| 指标 | 算法 |
|------|------|
| 检出率（8 问题代码） | 正确指出埋雷的代码数 / 8 |
| 误报率（2 干净代码） | 干净代码被报"问题"的条数 / 2 |
| 引用准确率 | basis 指向正确规范的条数 / basis 非 null 的总条数 |
| 幻觉率 | citation_issues 报 error 的条数 / 总 issues |

---

## 第三步：分析数据（60min）

把 A/B 两组数字填进对比表（这是简历/面试的核心素材）：

| 指标 | A 无 RAG | B 有 RAG | 提升 |
|------|---------|---------|------|
| 检出率 | ?% | ?% | +?% |
| 误报率 | ?条 | ?条 | -?条 |
| 引用准确率 | N/A（无引用） | ?% | — |
| 幻觉率 | ?% | ?% | — |
| 平均 token | ? | ? | +? |
| 平均耗时 | ?ms | ?ms | +?ms |

> 预期模式（如果数据和预期不符，要分析为什么）：
> - B 检出率 ≥ A（有规范依据更敢下结论）
> - B 误报率 < A（有规范约束，不乱挑刺）
> - B 贵在 token 和耗时（多检索 + 多轮）
>
> 常见"意外"及解释：
> - B 检出率反而低：检索没召回正确规范（查 embedding 或查询词问题）
> - B 误报率没降：prompt 约束没生效，回去看 Day 45 的 system 强化
> - A 检出率也很高：说明这些雷太明显（通用知识就够），RAG 价值没体现——
>   可加几段"需要特定规范才看得出"的雷（如"API 必须分页"这种项目自定义规则）

---

## 第四步：完善 Agent（90min）

根据实验发现的弱点针对性改：

| 发现的问题 | 改法 |
|-----------|------|
| 某类规范检索不到 | `_infer_review_query()` 关键词映射补全 |
| 引用张冠李戴多 | 强化 Day 45 软校验为硬报错，或加"来源文件名必须含 category 关键词"规则 |
| 干净代码误报 | system 加"找不到规范依据就不要报规范问题，宁可放过" |
| JSON 解析失败 | 加 `extract_json()` 容错（找首个 `{` 到末个 `}`） |
| 慢 | 检索结果缓存（Day34 已有 `_embed_cache` 思路，扩展到检索结果） |

改完重跑 10 段，看数字是否改善——**留一份"改进前 vs 改进后"的数据**，Week8 评估专题会用到。

---

## 第五步：写对比报告（60min）

`docs/plans/rag/week7/compare-report.md`（或 docs/notes 下）：

```markdown
# RAG 增强代码审查：有 RAG vs 无 RAG 对比报告

## 场景
用代码规范知识库（11 篇文档）增强 LLM 审查 Python 代码，对比有无 RAG 的审查质量。

## 方法
- 10 段测试代码（8 埋雷 + 2 干净）
- A 组：无 RAG（纯 LLM 审查）
- B 组：有 RAG（检索规范 → 带引用审查 → 校验）
- 评估指标：检出率 / 误报率 / 引用准确率 / 幻觉率 / token / 耗时

## 结果
（填第三步的对比表）

## 结论
- RAG 在 X 方面提升明显（检出率 +?%，误报率 -?条）
- 代价是 token/耗时 +?%
- 适用边界：当审查依据是"项目特定规范"（通用知识不知道的）时 RAG 价值最大

## 技术栈
裸写 RAGChain（bge-m3 + Chroma + ollama qwen3:8b），不依赖 LangChain。
```

> 简历写法示例（有数字才有力）：
> "构建规范驱动的 AI 代码审查系统，RAG 增强后埋雷检出率从 X% 提升到 Y%，
>  误报率下降 Z 条，通过编号引用 + 代码校验将规范幻觉率控制在 W% 以下。"

---

## 实验任务

- [ ] 10 段测试代码就位（8 问题 + 2 干净）
- [ ] 批量跑完 A/B 两组，结果存 JSON
- [ ] 人工评估完成，对比表填满
- [ ] 至少一轮"发现弱点 → 改 → 重跑"迭代
- [ ] 对比报告写完，含可写进简历的数字

## 检验标准

- [ ] 能用一句话讲清这个项目"做了什么 + RAG 的价值 + 量化结果"
- [ ] 对比报告里的数字经得起追问（知道每个数字怎么算的）
- [ ] 能说出 RAG 在这个场景的"适用边界"（什么情况 RAG 价值大、什么情况不值）

## 产出文件

- `data/test_code/*.py`（10 段）
- `rag/run_eval_batch.py`
- `rag/eval_results_day47.json`
- `docs/plans/rag/week7/compare-report.md`
