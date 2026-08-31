# 代码审查测试集（Day43-47）

> ⚠️ 埋雷说明只放在这个 README 里，**不放代码文件内**——docstring 写"预期检出 XX"
> 会把答案泄漏给 LLM，污染有/无 RAG 的对照实验（Day43 踩过的实验设计坑）。

## 埋雷清单（评估对照用）

| 文件 | 埋雷 | 预期引用规范 | 严重度 |
|------|------|------------|--------|
| `vuln_sql.py` | f-string 拼 SQL → 注入 | sql-best-practices.md（参数化查询） | high |
| `vuln_log.py` | 密码明文进日志 | log-best-practices.md（禁止记录敏感信息） | high |
| `vuln_auth.py` | ① token 只查存在不验签 ② IDOR 越权删除 | http-api-auth.md | high |

干净代码（Day47 扩充，测误报率）：`clean_util.py`、`clean_handler.py`（待 Day47 添加）。

## 对照实验设计（Day43 / Day47）

| 组 | 模板 | 检索 |
|----|------|------|
| A 无 RAG | `build_review_prompt_bare(code)` | 无 |
| B 有 RAG | `build_review_prompt(code, chunks)` | 每段代码对应检索词（见 run 脚本） |

评估指标：埋雷检出数 / basis 是否指向真实规范 / JSON 可解析性。
