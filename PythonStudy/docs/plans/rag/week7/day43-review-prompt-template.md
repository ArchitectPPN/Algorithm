# Day 43：Prompt 模板设计——代码审查场景

> 目标：学会用 system/user 角色设定 + 上下文组织 + 结构化输出，
> 设计一套"代码审查"Prompt 模板（`rag/review_prompts.py`），为 Day 44 的审查 Agent 备好弹药。
>
> 前置：Day 42 的 `RAGChain.retrieve()` 能拿到带来源的规范片段。

---

## 学习路线（约 90-120 分钟）

```
好 prompt 解剖（15min）→ 模板设计与实现（40min）→ 有无 RAG 对照测试（30min）→ 迭代（20min）
```

---

## 第一步：好审查 Prompt 的解剖（15min）

对比两个版本，先体会差异：

**❌ 弱版本：**
```
帮我审查这段代码：
{code}
```
问题：没有角色（模型不知道用什么标准）、没有输出格式（回复无法机器解析）、
没有边界（可能扯一堆正确的废话）。

**✅ 强版本四要素：**

| 要素 | 放哪 | 例子 |
|------|------|------|
| 角色 | system | "你是资深 Python 安全审查专家，只依据给定规范判断" |
| 上下文 | user 开头 | 【规范片段】+ 【待审代码】两段用标题分隔 |
| 输出格式 | user 结尾 | JSON：问题列表，每条含位置/风险/依据/建议 |
| 边界 | system 重申 | "规范外的建议放到'附加建议'，不得混入'规范问题'" |

> 关键认知：**角色决定"模型像谁"，格式决定"输出能不能被下游用"，边界决定"哪些话不许说"。**
> RAG 场景的特殊性：上下文片段是"证据"，prompt 必须显式要求"结论要挂在证据上"，
> 否则模型会退化成凭训练记忆自由发挥——这为 Day 45 的引用约束埋下伏笔。

---

## 第二步：模板设计与实现（40min）

`rag/review_prompts.py`：

```python
"""
Day43：代码审查 Prompt 模板

两套模板：
- build_review_prompt(code, chunks)：RAG 版，带规范片段上下文
- build_review_prompt_bare(code)：无 RAG 版（Day 44/47 对照实验用）

输出格式统一为 JSON（结构化 → 可被代码解析 → 可统计）。
"""

from __future__ import annotations

REVIEW_SYSTEM = (
    "你是资深 Python 代码安全与质量审查专家。\n"
    "你只依据【规范片段】中的内容给出'规范问题'，禁止编造不存在的规范。\n"
    "超出规范片段、但凭专业经验认为重要的问题，放进'附加建议'并注明不引用规范。"
)

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
      "suggestion": "具体修复建议（给出改后的代码片段）"
    }
  ]
}"""


def format_chunks(chunks: list[dict]) -> str:
    """规范片段 → 带编号的上下文块（编号 [1] [2]... 供 basis 字段引用）"""
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
```

⚠️ 三个设计点：
1. **片段编号 `[1] [2]`** 是引用约束的基础——没有编号，模型没法"挂证据"。
2. **`basis: null` 区分规范问题和附加建议**——防止模型把自由发挥伪装成规范结论。
3. 输出 JSON 走 `/api/chat`（messages 格式），不再是 Day 42 的 `/api/generate` 单 prompt——
   ollama 的 system role 约束力明显更强。

---

## 第三步：准备测试代码 + 对照测试（30min）

在 `data/test_code/` 放 3 段故意埋雷的代码（Day 47 会扩到 10 段）：

```python
# data/test_code/vuln_sql.py —— 埋雷：f-string 拼 SQL（对应 sql-best-practices.md）
import sqlite3

def get_user(username):
    conn = sqlite3.connect("app.db")
    query = f"SELECT * FROM users WHERE name = '{username}'"
    return conn.execute(query).fetchall()
```

```python
# data/test_code/vuln_log.py —— 埋雷：密码明文进日志（对应 log-best-practices.md）
import logging
logger = logging.getLogger(__name__)

def login(username, password):
    logger.info(f"user login: {username}, password: {password}")
    ...
```

```python
# data/test_code/vuln_auth.py —— 埋雷：鉴权只查有没有 token 不验签（对应 http-api-auth.md）
def delete_user(request):
    if request.headers.get("token"):
        User.objects.get(id=request.params["id"]).delete()   # 且无 IDOR 检查
        return "ok"
```

写个小脚本 `tmp_test_prompt.py`（临时文件，不用入库）跑对照：

| 变量 | A 组 | B 组 |
|------|------|------|
| prompt | `build_review_prompt(code, chunks)` | `build_review_prompt_bare(code)` |
| chunks 来源 | `RAGChain(top_k=3).retrieve("SQL 注入 参数化查询")` 等 | 无 |

各跑 3 段代码，记录：
- A/B 是否都发现埋的雷？（召回对比）
- A 的 `basis` 是否指向真实片段？B 有没有编造"根据 XXX 规范"？（幻觉对比）
- 输出 JSON 是否可解析？（格式约束是否生效，`json.loads` 直接试）

---

## 第四步：迭代模板（20min）

根据观察修模板，常见迭代点：
- 模型不输出纯 JSON（带 ```json 围栏或解释文字）→ 加"只输出 JSON"仍不行就写个
  `extract_json()` 容错（找第一个 `{` 到最后一个 `}`）
- 问题太泛（"建议提高安全性"）→ system 里加"每条问题必须指出具体行/函数并给改后代码"
- 附加建议混进规范问题 → 强化 basis 规则

---

## 实验任务

- [ ] 3 段代码 × A/B 两组共 6 次审查全部跑通，JSON 可解析
- [ ] 记录对照表：A 组发现 3 雷中的几个？B 组呢？B 组有没有编造规范名？
- [ ] 至少迭代一轮模板并记录改动原因

## 检验标准

- [ ] 能默写审查模板四要素（角色/上下文/格式/边界）各放在哪
- [ ] 能解释为什么片段要编号、basis 为什么允许 null
- [ ] 能说出 system role 为什么比把要求塞进单条 prompt 更有效

## 实验结果（2026-08-31 实测）

### ⚠️ 实验设计踩坑：测试代码不能带"答案提示"

初版 vuln_*.py 的 docstring 写了"预期应检出 SQL 注入"——这会把答案泄漏给 LLM，
污染 A/B 对照（无 RAG 组也能从注释看出该报什么）。**已修正**：代码文件只留纯代码，
埋雷说明统一放 `data/test_code/README.md`。

> 教训：对照实验的变量必须只有"有无 RAG"，测试材料里任何提示性内容都是污染源。

### A/B 对照数据（3b 跑全量 + 8b 验证 vuln_log）

| 代码 | 组 | 检出情况 | basis 引用 |
|------|-----|---------|-----------|
| vuln_sql | A 3b | ✅ SQL 注入 | None |
| vuln_sql | B 3b | ✅ SQL 注入 | [1]→sql-best-practices（真实） |
| vuln_auth | A 3b | ❌ **误判**：把 IDOR 说成 SQL 注入 | None |
| vuln_auth | B 3b | ✅ token 未验签（漏 IDOR） | [1]→http-api-auth（真实） |
| vuln_log | A 3b | ⚠️ 半对：说"默认日志级别泄露"（没抓到密码明文） | [1] **幻觉**（A 组无规范可引） |
| vuln_log | B 3b | ❌ **漏检密码明文**：只报"缺时间戳/模块名" | [1]→log-best（真实但挂错条目） |
| vuln_log | A 8b | ✅ 密码泄露 + check_credentials 硬编码 | "安全规范[1]" **幻觉** |
| vuln_log | B 8b | ✅ 密码泄露 | [1]→log-best（真实） |

### 关键发现

**1. RAG 的价值不是"检得多"，是"说得有依据"**
A 组（无 RAG）basis 要么 None 要么幻觉（编造不存在的规范编号）；B 组 basis 全部指向真实规范文件。
无 RAG 的审查意见无法验证真伪；有 RAG 至少可以核对"引用的规范是否真的支持这个结论"。

**2. RAG 不是银弹：证据在眼前，弱模型也用不上**
vuln_log B 组（3b）：检索命中的 chunk0 白纸黑字写着"禁止把敏感信息打到日志（密码、Token、身份证号）"，
但 3b 只报了"缺时间戳/模块名"——**证据齐了，模型关联不起来**（被 chunk 开头的日志级别内容带偏）。
换 8b 同条件立刻检出。→ RAG 系统质量 = 检索质量 × 模型能力，两个短板哪个短都拖垮整体。

**3. 无 RAG 的模型会"乱贴标签"**
vuln_auth A 组把 IDOR 越权误判成 SQL 注入（看到 `params["id"]` 就条件反射）；
B 组靠 http-api-auth 规范纠正为"token 未验证"。规范上下文给了模型更准的分类框架。

### 3b 输出 JSON 的三个坑及修法（已修两个）

| 坑 | 现象 | 修法 |
|----|------|------|
| 未转义引号 | suggestion 贴代码时 `f"SELECT..."` 的双引号截断 JSON | ✅ ollama 请求加 `"format": "json"`（语法层约束） |
| 输出截断 | suggestion 贴完整函数，写超 num_predict 断在中途 | ✅ 模板约束"关键修改点，最多 3 行代码" |
| basis 格式混乱 | 同一模型输出 `"1"` / `"[1]"` / `"安全规范[1]"` 三种 | ⏳ Day45 的 extract_basis 正则要容错纯数字 + 校验幻觉 |

### 模型选型结论（结合 Day42 对比）

- 审查场景选 **8b**：3b 会漏检（vuln_log 教训），漏检比慢更致命
- 8b 的 A 组也产生幻觉引用 → 幻觉不是 3b 专属，**引用校验（Day45）必须做**，不能靠"换个好模型"解决

## 产出文件

- `rag/review_prompts.py`
- `data/test_code/vuln_sql.py` / `vuln_log.py` / `vuln_auth.py` / `README.md`
- 临时实验脚本 `tmp_test_prompt.py`（不入库，含 A/B 对照逻辑可复用）
