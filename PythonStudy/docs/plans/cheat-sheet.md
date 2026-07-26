# 踩坑记录

> 转型路上真实踩过的坑，记下来避免重犯，也作面试时"工程经验"的素材。

---

## 2026-07-20 · 坑 1：API Key 明文泄露（最严重，安全类）

**经过**：调 embedding demo 时，把 DeepSeek API Key（`sk-21a21...`）明文贴进了对话，又写进了 `deepseek_embedding_demo.py` 第 18 行硬编码。

**问题**：
- key 完整出现在对话记录 + 脚本文件里，等于公开。
- DeepSeek key 能消费账户余额，别人拿到就能用我的钱。
- 一旦 `git commit`，key 永久进 git 历史，删文件也删不掉历史（要 `git filter-branch` 清理，很麻烦）。

**正确做法**：
1. key 泄露后**立即去后台吊销重生成**（platform.deepseek.com → API Keys 删掉旧的）。
2. key 永远从环境变量读，`API_KEY = os.environ.get("DEEPSEEK_API_KEY", "")`，绝不硬编码。
3. key 存在 home 目录独立文件（`~/.deepseek_env`）或项目里 `.env` 文件，**不进 git**（`.gitignore` 挡住）。
4. 永远不把 key 贴进对话/截图/日志。

**面试启示**：AI 岗位面试会问"你怎么管理 API Key / 密钥"，答"环境变量 + .env + .gitignore + 不进代码"是标准答法。答"写代码里/贴终端"直接减分。这是区分"会调 API"和"能上生产"的分水岭。

---

## 2026-07-20 · 坑 2：系统 Python 3.9 装包，版本太老

**经过**：跑 `deepseek_embedding_demo.py` 报 `ModuleNotFoundError: No module named 'requests'`。排查发现机器上只有系统自带 `/usr/bin/python3`（Python 3.9），没建 venv，requests 也没装。`pip install requests` 后又冒出 `NotOpenSSLWarning`（urllib3 v2 + macOS LibreSSL 2.8.3 不兼容警告）。

**问题**：
- 系统 Python 3.9 偏老，新语法不支持（如 `list[str]` 类型注解 3.9 要写 `List[str]`）。
- 全局装包会污染系统 Python，容易"今天能跑明天不能跑"。
- 装 chromadb / langchain 这类库时，老环境 + 老依赖会冒一堆警告甚至报错。
- urllib3/OpenSSL 警告虽不影响功能，但说明环境老化。

**正确做法**：
- 装 Python 3.12（用 pyenv 或官网安装包）。
- 每个项目建独立 venv：`python3.12 -m venv .venv && source .venv/bin/activate`。
- 在 venv 里 `pip install`，隔离依赖。
- 计划第1周 Day4 该做 venv 但跳过了，补在第3周裸写 ReAct loop 之前。

**面试启示**：Python 工程化（venv/依赖隔离）是后端转 AI 的基本盘，这块 PHP 转 Python 最容易漏。

---

## 2026-07-20 · 坑 3：搞混 chat API 和 embedding API

**经过**：一直只用 chat API（输入文字→输出文字），听到"embedding API"时问"这是什么"。一度想用 DeepSeek chat 的思路去理解 embedding。

**问题**：把两种完全不同的 API 当成一类东西。

**澄清**：
- chat API：输入文本 → 输出文本（生成回答）。做周报分析、对话用它。
- embedding API：输入文本 → 输出向量（一串数字，如 1024 维）。不算回答，只把文字变数字。
- embedding 的价值：语义相近的文本向量距离近，RAG 靠这个"按意思检索"。
- RAG 离线把文档 chunk 算成向量存库，在线把问题算成向量去库里找最近的——这两步都调 embedding API。

**面试启示**：chat 和 embedding 的区别是 RAG 基础题，必考。能讲清"embedding 输出的是向量不是文字、用途是算相似度"就过关。

---

## 2026-07-20 · 坑 4：计划进度标记混乱

**经过**：原计划里 Day8 标了 `[x]`（DeepSeek 注册），但 Day9 及之后都空着，实际却已做完周报分析工具（覆盖 Day8-13 能力）。进度标记和真实进度对不上。

**问题**：手动维护 markdown 勾选框，容易标错或漏更新，导致"以为自己落后其实领先"或反之。

**正确做法**：用单一文档统一管理勾选（已在 `AI-Agent-Transition-Plan.md` 用 `[ ]/[x]/[~]` 统一），定期对照真实产出校准。每周末花 5 分钟把勾选和实际做完的事对齐。

**面试启示**：不重要，但影响学习节奏判断，别让标记拖累心态。

---

（后续踩坑持续追加……）

---

## 2026-07-20 · 坑 5：DeepSeek 根本没有 embedding 接口（选型坑）

**经过**：调 embedding demo 一直 404。排查发现：DeepSeek 当前只开放两个 chat 模型（`deepseek-v4-flash` / `deepseek-v4-pro`），**不提供 embedding API**（`/embeddings` 端点 404）。之前定的"chat+embedding 全用 DeepSeek"选型走不通。

**问题**：把 chat 厂商当成必然有 embedding 能力的厂商。很多 chat 厂商（DeepSeek、Kimi 等）专注对话，不一定开放 embedding。选型前没确认厂商能力清单。

**正确做法**：
1. 选型前先调 `GET /models` 看厂商开放了哪些模型，别假设有 embedding。
2. embedding 单独选型，和 chat 解耦：chat 用 DeepSeek、embedding 用智谱/通义/OpenAI。
3. **或走完全本地方案**：本地跑开源 embedding 模型（如 `BAAI/bge-small-zh`）+ 本地 Chroma，零 API、零 key、不受云可用性影响。转行学习阶段推荐本地方案，简历还能写"部署开源 embedding 模型"。

**概念澄清**（关键）：
- **embedding 模型** = 把文字变向量（翻译官），不会存。
- **向量数据库（Chroma）** = 存向量 + 查最近的（仓库管理员），不会算向量。
- 两者缺一不可。Chroma 本地跑没问题，但它算向量仍要靠 embedding 模型（API 或本地模型）。"本地搞向量数据库"=Chroma 本地✅，但 embedding 来源仍要定。

**面试启示**：能讲清 embedding 模型 vs 向量数据库的区别、以及"本地向量库也要配 embedding 来源"，是 RAG 基础题。能讲"为什么选本地 bge 而非调 API"是加分项。

---

## 2026-07-20 · 坑 6：embedding 和第3周无关，别在它上面耗时间

**经过**：为调通 embedding demo 反复折腾（key 泄露、装 requests、404 排查），花了不少时间。但第3周裸写 ReAct loop **完全不需要 embedding**（只用 chat API）。

**问题**：被一个第5周才用、且当前受阻的东西卡住，忽略了真正该推进的第3周。

**正确做法**：按计划顺序推进，当前阶段用不上的能力先跳过，等真正需要时再解决。embedding 等 RAG 阶段（第5/6周）再定方案。决策时区分"现在必须的"vs"以后才要的"。

**面试启示**：不重要，但影响学习效率，别让边角问题拖累主线。

---

## 2026-07-21 · 坑 7：`os.environ.setdefault()` 导致 .env 不生效

**经过**：`.env` 里填了新 key，但脚本一直报 401。排查发现脚本里用了 `os.environ.setdefault("DEEPSEEK_API_KEY", value)`——这个方法只在环境变量**不存在**时才设值。如果终端 shell 里之前 `export` 过旧的 key，`setdefault` 发现已存在就跳过 `.env` 里的新值，最终用旧 key 去请求 → 旧 key 已吊销 → 401。

**问题**：`setdefault` 的语义是"没有才设"，不是"强制覆盖"。

**正确做法**：用 `os.environ["KEY"] = value` 直接覆盖。或启动前先 `unset DEEPSEEK_API_KEY` 清掉旧环境变量。

**面试启示**：不重要，但说明调试时要关注"值到底是从哪来的"——不只看代码，还要看运行时环境。

---

## 2026-07-21 · 坑 8：ReAct 最小原型跑通了，但暴露了 3 个工程漏洞

**经过**：`react_minimal_demo.py` 跑通，模型成功自主调用了 calculate 工具。但代码在以下场景会崩溃或行为异常：

**漏洞 1：模型调用不存在的工具 → KeyError 崩溃**
- `TOOL_FUNCTIONS["nonexistent_tool"]` 直接抛异常
- 工业做法：catch KeyError，返回"没有这个工具"给模型，让它重选

**漏洞 2：参数校验缺失**
- 模型返回的 tool_calls 可能缺 arguments、arguments 不是合法 JSON、或缺少必填字段
- 工业做法：执行前校验参数合法性，不对就返回错误信息给模型

**漏洞 3：模型可能不调工具，直接给错误文字回答**
- 模型觉得"这题我会"→ 口算 → 可能算错
- 工业做法：Prompt 约束"必须用工具"、兜底重试、或自动追加"请使用提供的工具"

**面试启示**：面试官问"你怎么保证 Agent 输出准确性"时，能说出这三个漏洞和对应处理方式，是加分项（证明你写过而不是调过包）。

**正确做法**：第3周加 while 循环时一并补容错逻辑。

---

## 2026-07-22 · 坑 9：容错加了但没循环，模型没机会重试

**经过**：v2 加了 `execute_tool` 容错，但主流程仍是直线（只做一轮工具调用）。模型调了不存在的工具 → 容错返回错误信息 → 结果发回模型 → 但代码直接把模型第2轮回答输出了，**没有再检查第2轮是否又返回了 tool_calls**。模型拿到错误信息后想重试，但代码不给机会。

**问题**：容错只解决了"不崩溃"，没解决"给模型重试机会"。容错 + 循环必须一起用，缺一不可。

**正确做法**：加 while 循环，模型拿到错误信息后可以自己决定重试（再调工具）或放弃（直接文字回答），循环直到模型不再返回 tool_calls。

**面试启示**：能讲清"容错和循环是配合的——容错让错误不崩溃，循环让模型有机会从错误中恢复"，比只讲容错高一个层次。

---

## 2026-07-22 · 知识笔记：ReAct 循环完整实现

> 通过代码实践获得的认知，记录备用。

- **while 循环核心**：`while loop_count < MAX_LOOPS`，每轮调 chat API → 检查 tool_calls → 有则执行工具+结果回灌 → 无则 break 输出最终回答。
- **终止条件**：模型某轮不返回 `tool_calls` → `finish_reason: stop` → break。
- **循环保护**：`MAX_LOOPS = 10`，防止死循环。while 的 `else` 分支处理跑满轮次的情况。
- **多 tool_calls**：模型一次可返回多个 tool_calls（如同时调 get_time + calculate + get_file_info），用 `for tool_call in msg["tool_calls"]` 逐个执行，结果全部回灌。
- **finish_reason**：`tool_calls` = 模型要调工具（还没说完），`stop` = 模型说完了。
- **token 增长**：每轮循环 prompt_tokens 都会涨（因为历史消息越来越多），第1轮436 → 第2轮634。Agent 比"一问一答"贵的原因。
- **prompt_cache_hit_tokens**：DeepSeek 的缓存机制，命中缓存的部分更便宜。
- **代码版本**：v1(最小原型) → v2(容错) → v3(循环+容错) → 当前(3工具+日志)，每个版本独立保存。

---

## 2026-07-23~24 · 坑 10：`if msg.get("tool_calls")` 逻辑写反了

**经过**：手写 `git_agent_practice.py` 时，把终止条件写反了——有 tool_calls 时 break（打印"最终回答"），没有 tool_calls 时去执行工具。导致 Agent 永远只输出模型第一次响应就停，工具永远不会被执行。

**问题**：混淆了"有 tool_calls"和"没有 tool_calls"两种情况应该做什么。

**正确逻辑**：
- `if not msg.get("tool_calls")` → 没有 → 最终回答 → break
- `else` → 有 → 执行工具 → 继续循环

**面试启示**：这是 ReAct 循环最核心的判断，写反了 Agent 就废了。面试时能讲清"tool_calls 有无分别意味着什么"就证明真懂。

---

## 2026-07-23~24 · 坑 11：模型对"hello"也调了工具

**经过**：多轮对话时，用户输入"hello"，Agent 却调了 get_status 工具。模型看到 tools 参数里有工具，就倾向于用一下，即使用户只是打招呼。

**问题**：没有 system prompt 约束模型行为，模型不知道"什么时候该用工具"。

**正确做法**：加 system prompt，明确告诉模型"只在用户问 git/文件相关问题时才调工具，打招呼直接回答"。加完后 hello 不再调工具 ✅。

**面试启示**：Agent 不只是"工具+循环"，**system prompt 是第三个关键零件**——约束模型行为、决定什么时候该用工具。能讲"我遇到过模型乱调工具的问题，通过 system prompt 约束解决"是真实工程经验。

---

## 2026-07-23~24 · 坑 12：多轮对话最终回答没记进 messages

**经过**：加了多轮对话后，第一轮问答正常，但第二轮 Agent 看不到自己第一轮的回答——上下文断了。

**问题**：模型给最终回答后 `break`，但没把最终回答 append 进 messages。下一轮模型看不到上一轮说了什么，不知道"这次提交"指的是哪个。

**正确做法**：`messages.append(msg)` 在 break 前执行，最终回答也记进历史。

**本质**：`messages` 是 Agent 的"记忆"。每条消息（用户的、模型的文字回答、模型的工具调用、工具结果）都要 append 进去，下一轮模型才能看到完整上下文。

---

## 2026-07-23~24 · 知识笔记：多轮对话 + system prompt + Git Agent

> 通过代码实践获得的认知，记录备用。

- **多轮对话**：外层 `while True` 读输入，内层 ReAct 循环处理单个问题。`messages` 在两轮之间是同一个对象，不断 append，模型每轮都能看到完整历史。这就是"多轮对话有上下文"的实现原理——没有魔法，就是一个不断增长的列表。
- **system prompt**：`{"role": "system", "content": "..."}` 放在 messages 最前面，约束模型行为（自己是谁、什么时候该用工具、回答风格等）。不加 system prompt → 模型行为不可控。
- **Git Agent 三工具**：get_commits（提交列表）/ get_diff（提交摘要，用 `--stat` 不要全量 diff）/ read_file（读文件内容+安全检查+长度截断）。
- **read_file 安全检查**：禁止读 `.env`（防密钥泄露）+ 长度截断 max_chars=3000（防撑爆上下文）。
- **function calling 的真实缺陷**：工具定义占 token（工具多就贵）、工具硬编码不能动态、模型对工具的理解只来自 description、每家 API 格式略有差异。MCP 解决标准化+动态问题，但底层仍用 function calling。
- **function calling 的"问题"要分清**：模型乱调工具/参数不对 = 模型决策能力问题（所有方案都有）；工具定义占token/不能动态 = 机制问题（MCP 可解决）。
- **代码文件**：`git_agent.py`（参考版）、`git_agent_practice.py`（手写版）、`subprocess_practice.py`

---

## 2026-07-21 · 知识笔记：Function Calling 核心概念

> 非"坑"，而是今天通过代码实践获得的认知，记录备用。

- **Function Calling 不是另一个 API**：还是同一个 chat 端点，请求里多 `tools` 参数。
- **工具定义提前塞入**：每次请求都带 tools 列表，模型在整个对话里随时能选。
- **模型自主决策**：代码没有 `if then` 逻辑，模型读 tools 后自己判断要不要调、调哪个。
- **终止条件**：模型某轮不再返回 `tool_calls` → 任务完成。
- **`msg.get("tool_calls")`**：dict 的 key check，不是字符串匹配。
- **`TOOL_FUNCTIONS = {"calculate": do_calculate}`**：名字到函数的映射，和 PHP 路由映射同理。
- **f-string**：`f"...{var}..."` 是 Python 字符串格式化，相当于 PHP 双引号嵌变量。
- 代码文件：`react_minimal_demo.py`（第3周改造它的基础）

