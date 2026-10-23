# Pi 学习计划 v3（完整档）

> **定位**：RAG 软暂停期主线。目标不是"读懂 Pi 源码"，而是**吃下内核 + 完成四项结构改造 + 产出可演示作品与可量化数据**。
>
> **配套读物**：`docs/notes/pi-architecture-analysis.md`（架构定位）、`docs/notes/pi-code-reading-guide.md`（行号级导读）、`docs/notes/agent-harness-gap-analysis.md`（九项能力缺口）。
>
> **Day 编号说明**：本文 Day 1–22 为本计划内的相对学习日，每天约 2h，与 `transition-plan` 的 Day29–64（RAG 区间）无关，不要混记。

---

## 本地代码环境（2026-10-23 实测核对）

| 项 | 值 |
|---|---|
| 源码路径 | `D:\AiAgent\pi`（origin：`earendil-works/pi`） |
| 源码版本 | `v1.4.0-22-g936e5f40a`（2026-10-22 提交），与 `origin/main` 同步，落后 0 个提交 |
| 全局 CLI | `@earendil-works/pi-coding-agent@1.4.0`，命令 `pi` 可用（`C:\Users\niujunqing\.npm-global`） |
| Node | 要求 `>=22.19.0`，本机 v22.22.2，满足 |
| 源码依赖 | 未安装 `node_modules`；若要跑仓库版，先 `npm install` |
| 用户配置 | `~/.pi` **不存在**，说明装了但从未启动过 |
| 分支 | `main`，工作区干净 |

> **版本已对齐**（2026-10-23）：CLI 1.4.0，源码 `v1.4.0-22`——同一 minor 线，源码只多 22 个提交。读到的代码与跑出来的行为可以直接互相验证，不必再担心"文档说的和实际跑的不一致"。

### 官方文档已在本地，不必联网

全局包内含 **44 篇**官方文档，路径 `C:\Users\niujunqing\.npm-global\node_modules\@earendil-works\pi-coding-agent\docs\`。计划中要读的几篇都在（行数已按 1.4.0 核对，除 `extensions.md` 由 277 增至 400 外，其余与 1.3.0 一致）：

| 文档 | 行数 | 对应 Day |
|---|---|---|
| `how-pi-works.md` | 49 | Day 3 |
| `message-types.md` | 279 | Day 1–2 |
| `skills.md` | 93 | Day 12 |
| `session-format.md` | 293 | Day 15 |
| `compaction.md` | 463 | Day 19 |
| `codemode.md` | 230 | Day 21 |
| `mcp.md` | 272 | Day 10 |
| `extensions.md` | 400 | Day 6 后可选 |
| `sdk.md` / `rpc.md` / `security.md` | — | 备查（嵌入与安全模型） |

示例代码在同级 `examples/`（含 `sdk/`、`rpc-client.ts`、`extensions/`），可直接运行参考，比看官网示例省事。

### 用 CLI 验证机制（读代码时的活体对照）

版本对齐后，CLI 可作为"读到的代码是否真的这样跑"的验证器。四个最有用的观察点：

| 想验证什么 | 命令入口 | 对应 Day |
|---|---|---|
| 事件流顺序：`tool_result` 与 `tool_execution_end` 谁按源序、谁按完成序 | `pi -p "<需要多次工具调用的任务>" --mode json` | Day 4 |
| 工具集变更是否增量声明、默认工具是否只有四个 | `pi -p "..." --tools read,bash --mode json` | Day 5 |
| 无工具模式下提示词还剩什么（验证 skills 段依赖 read/bash） | `pi -p "..." --no-tools --mode json` | Day 6 |
| 会话树分叉：从旧节点另开分支 | `pi --fork <session-id> -p "..."` | Day 18 |

> 原则：**先读代码形成预期，再用 CLI 验证**。反过来的话，看到的现象无法解释，等于白看。

**行号已逐项校验**，与 `pi-code-reading-guide.md` 第八节标注一致：

| 文件 | 行数 | 关键锚点（已命中） |
|---|---|---|
| `packages/agent/src/types.ts` | 539 | — |
| `packages/agent/src/agent-loop.ts` | 959 | `runLoop` L172、`failToolCallsFromTruncatedMessage` L488、`shouldTerminateToolBatch` L700、`declareToolChanges` L342 |
| `packages/agent/src/agent.ts` | 623 | — |
| `packages/coding-agent/src/core/skills.ts` | 514 | `formatSkillsForPrompt` L358 |
| `packages/coding-agent/src/core/system-prompt.ts` | 229 | `diffSystemPromptSections` L217 |
| `packages/coding-agent/src/core/compaction/compaction.ts` | 1104 | `shouldCompact` L267 等五个函数 |

> **旧快照（v0.82.0）的用法**：更新前的本地版本为 v0.82.0（2026-07-25，落后 1,960 个提交），结构为集中式 `agent/src/harness/`，且 skills 是**整篇注入**（`harness/skills.ts:39` 直接拼 `${skill.content}`）。它不进工作区，需要对照时用 `git show v0.82.0:packages/agent/src/harness/skills.ts`。
>
> 这个对照在 Day 12–14 有直接用途：**Pi 自己从「整篇注入」演进到了「渐进披露」**，与你 `loader.py` 的处境同构。面试可讲"对比两个版本理解演进动机"，比只讲最终实现更有说服力。

---

## 零、为什么从 v2 的"18 天"改成 22 天

v2 把完整档写成"标准 12 天 + 6 天增量"，是加法估算法，细排后不成立：

| 项 | v2 估值 | 细排后 | 差异原因 |
|---|---|---|---|
| 会话树 | 3 天 | **4 天** | 除 PoC 外还要读 `session-format.md` 15.7KB、验证 session 实现的三处边界、做字段级迁移 |
| 压缩修正 | 2 天 | 2 天 | 一致 |
| codemode | 1 天 | 1 天 | 一致 |
| 收口（数据汇总 / 面试卡 / 对照表） | 未单列 | **2 天** | v2 把它摊进了各阶段，实际无法摊 |
| MCP client | 未计入 | **2 天** | 属于 harness 缺口的 P0，与结构改造同源，应并入本轮 |

**22 个学习日 = 44h 净投入，另留 3 天缓冲。** 缓冲不是浪费——按你过去的实际节奏（Day42–46 期间多次被工作打断），无缓冲的计划基本都会在第三周失控。

---

## 一、时间账：这 22 天从哪来

必须先把账算清，否则计划执行到中途会因为"挤占了工程化周次"而崩。

| 来源 | 天数 |
|---|---|
| 原计划 Week8 评估专题（软暂停） | +7 |
| 原计划 Week9 可观测性（软暂停） | +7 |
| 原计划给 Pi 学习的空档 | +6 |
| **可用合计** | **20** |
| 本计划需求 | 22 |
| **缺口** | **−2** |

两个缺口两种填法，建议选后者：

1. 顺延 Week10 工程化约 1 周（影响面小，但会推后 Celery/Docker 那条线）。
2. **把可观测性并入 Day 22**（推荐）。它要统计的 token 用量、工具成功率、平均轮次，口径正好在 Day 14 测 token 时建立起来——先有测量习惯再做埋点，比另起 2–3 天做一套系统更省。这样 20 天里能省出 7 天，缺口自然消失。

**明确代价**：Week10 工程化起始时间后移约 1 周；`transition-plan` 附录 C 的"第 8 周末简历有量化数据"里程碑由 Day 14 与 Day 22 两次测量补齐，不算缺失。

---

## 二、七个阶段与门禁

每个阶段设一道门禁，**过不去不进入下一阶段**。这是防止"带着误解往下建"的唯一有效手段。

| 阶段 | Day | 内容 | 门禁（必须满足） |
|---|---|---|---|
| 一 | 1–6 | 读通内核（2,122 行） | 八道自测题能答对 6 题以上 |
| 二 | 7–9 | 消息三态 + 事件归约 | 现有 agent 全工具跑通，事件流可重放 |
| 三 | 10–11 | MCP client | 能接入一个外部 MCP server 并调用其工具 |
| 四 | 12–14 | 上下文经济学（渐进披露） | 拿到 system token 前后对比数字 |
| 五 | 15–18 | 会话树 | 能演示「回到第 N 轮换问法，两条分支都在」 |
| 六 | 19–20 | 压缩正确性 | 摘要失败不再丢历史；压缩按 token 预算触发 |
| 七 | 21–22 | codemode + 收口 | 简历能写出 3 行带数字的描述 |

---

## 三、逐日日程

### 阶段一 · 读通内核（Day 1–6，12h）

| Day | 读什么 | 产出 | 完成判定 |
|---|---|---|---|
| 1 | `agent/src/types.ts` L1–260 | 内部 `AgentMessage` / LLM 消息两层投影图 | 说出 `convertToLlm` 为何是唯一转换边界 |
| 2 | `types.ts` L260–540 | 10 回调 + 1 策略字段表（时机 / 用途 / 不给会怎样） | 答出自测 2、6 |
| 3 | `agent-loop.ts` L111–390 | 默写 `runLoop` 双层骨架 | 不看代码写出两个退出条件 |
| 4 | `agent-loop.ts` L390–620 | 笔记：截断作废、并行三段式 | 答出自测 3、5 |
| 5 | `agent-loop.ts` L620–960 | 笔记：终止悲观聚合、工具集增量、abort 四点 | 答出自测 1、2、4 |
| 6 | `agent.ts` 全 623 行 | 「loop 发事件 → reducer 改状态」边界说明 | 答出自测 6 |

**门禁**：八道自测题答对 6 题以上。答不出的题回到对应文件重读，**不要带着误解进入阶段二**——后面三项改造全靠这层理解。

三个必须抓住的判断（都可直接作为面试答案）：

- **没有 `max_rounds`**。收敛靠"模型不再调工具"或"整批 terminate"，不是计数器。你的 `max_rounds=30` 是安全网，Pi 认为那是提示词与工具设计的问题。
- **终止是悲观聚合**（`agent-loop.ts:700`）。一批三个工具，两个说停一个说继续 → 继续。任何一个认为还有事要做都被尊重。
- **截断时整批作废**（`agent-loop.ts:488`）。被 output limit 截断的参数可能"能解析但语义不完整"，执行比失败更危险。宁可全失败让模型重发。

### 阶段二 · 结构改造（Day 7–9，6h）

| Day | 改什么 | 交付 |
|---|---|---|
| 7 | `message.py` 75 行 → 增 `terminate` / `isError` / `durationMs`，同步改 `agent_loop.py` 的构造点 | 全工具跑通，无回归 |
| 8 | 事件归约层：把 `session_manager.py` 的"直接改状态"拆成 `事件 → reducer → state` | 事件类型枚举 + reducer 骨架 |
| 9 | 接上 reducer，`agent_end` 语义对齐（等所有监听器结算） | 端到端跑通，一条事件流可重放 |

**单独开分支**：`git checkout -b refactor/pi-aligned`。这是全计划风险最高的一段，失败要能整体丢弃。

**门禁**：`agent/` 原有 7 个工具全部跑通，且能用录制的事件流重建出同一份 state（重放一致性）。

### 阶段三 · MCP client（Day 10–11，4h）

| Day | 做什么 | 交付 |
|---|---|---|
| 10 | 读 `agent/mcp_servers/jira/server.py` 反推协议契约；实现 stdio 连接 + `initialize` 握手 | 能连上一个外部 server |
| 11 | `tools/list` 拉取 → JSON Schema 转 `Tool` 对象注册进 `ToolRegistry`；`tools/call` 转发；断连重连 | 外部工具可被 agent 调用 |

**为什么插在这里**：阶段二刚动过工具注册链路，此时接入成本最低；若放到最后，`message.py` 与注册表要改两遍。

**门禁**：接一个现成 MCP server（不自己写），让 agent 调用其工具拿到正确结果。

> 当前状态见 `agent-harness-gap-analysis.md`：项目里只有 server 端实现（`mcp_servers/jira/server.py` 完整实现 initialize/tools/list/tools/call），**没有任何 client 连接代码**，`agent_main.py:65` 的 `MCP_FILE` 定义后从未使用。这是"能造服务、接不进别人的服务"。

### 阶段四 · 上下文经济学（Day 12–14，6h）

| Day | 做什么 | 交付 |
|---|---|---|
| 12 | `skills.ts` L150–460 精读（发现规则 / 冲突处理 / `disable-model-invocation`） | 与 `loader.py` 的差异清单 |
| 13 | 改造 `loader.py`：整篇注入 → 只注入 name/description/location | 代码改动 + 回归测试 |
| 14 | 测 system token 前后对比（同任务、同模型，各跑 3 次取中位数） | **一个可写进简历的数字** |

**测量方法**：本地 ollama `/api/chat` 返回的 `prompt_eval_count` 即为输入 token 数；固定同一个任务与同一版本技能库，只变量是加载策略。

**门禁**：数字出来。Day 14 与 Day 22 是整份计划唯一产出简历素材的节点，不可跳过。

### 阶段五 · 会话树（Day 15–18，8h）

| Day | 做什么 | 交付 |
|---|---|---|
| 15 | `docs/session-format.md` 15.7KB 精读 + 验证 session 实现三处边界 | 边界答案：多个 compaction 取哪个 / `context_edit` 怎么叠加 / retain-none 时 `firstKeptEntryId` 存什么 |
| 16 | 设计稿：entry 类型、字段、`chat_memory.json` 迁移策略（旧线性数组 → 树） | 字段级设计文档 |
| 17 | PoC：`chat_memory` 改为 `{id, parentId}` 条目表，读写与回溯 | 可运行 |
| 18 | 接入 `agent_loop.py`，做分支回溯演示 | 能演示「回到第 N 轮换问法，两条分支都在」 |

**为什么必须排在阶段二之后**：树需要每条 entry 有 `id` / `parentId`，而阶段二之前 `message.py` 连三态都没有。在错误的消息模型上建树等于给流沙打地基。

**门禁**：演示通过。这是全计划"作品感"最强的一项，也是"你懂状态管理"的硬证据。

### 阶段六 · 压缩正确性（Day 19–20，4h）

| Day | 做什么 | 交付 |
|---|---|---|
| 19 | `compaction.ts` 五个函数（`shouldCompact` 267 / `estimateContextTokens` 196 / `findCutPoint` 446 / `prepareCompaction` 859 / `compact` 952） | 「历史摘要 + 拆轮前缀」拼接结构图 |
| 20 | 改 `context_manager.py`：`keep_recent_rounds=10` → token 预算；`except` 分支不再 `return recent`；补拆轮处理 | 三项修复 + 验证 |

**这一阶段修的是 bug，不是优化**。当前 `context_manager.py` 在摘要异常时直接 `return recent`，早期历史被无声丢弃且无任何提示，属数据丢失级问题。同时按轮数保留（10 轮）在不同场景下差 25 倍——10 轮短对话约 2k token，10 轮读大文件可达 50k。

**门禁**：人为让摘要失败，验证历史仍在且用户可见提示；压缩触发点按 token 预算而非轮数。

### 阶段七 · codemode 与收口（Day 21–22，4h）

| Day | 做什么 | 交付 |
|---|---|---|
| 21 | `codemode/src/types.ts` + `declarations.ts`：`deliver(seq, ...)` 的定序投递、schema→TS 类型签名渲染 | 「十次 tool call vs 一段脚本」的上下文成本对照表 |
| 22 | 汇总所有数字；写 3 张面试卡；更新 `pi-vs-myagent.md`；在 `agent_loop` 埋第一版指标（token / 轮次 / 工具成功率） | 3 张卡 + 对照表 + 埋点 |

**门禁**：简历能写出 3 行带数字的描述，例如"skill 加载改为按需披露，system token 降低 X%；会话结构由线性数组改为树，支持任意节点回溯"。

---

## 四、降级路径（超期时砍哪里）

22 天是真实估算，但仍有超期可能。**砍的顺序是预先定好的，不在压力下临时决定**：

| 触发点 | 砍什么 | 保留什么 | 结果档位 |
|---|---|---|---|
| Day 14 结束超期 3 天以上 | 砍阶段五（会话树，8h） | 阶段一–四全保留 | 回到标准档 |
| Day 18 结束超期 | 阶段六只做"静默丢弃修复 + token 预算"，砍拆轮处理 | 正确性修复不能砍 | 标准档 + 压缩修正 |
| Day 21 结束超期 | 砍 codemode（1 天） | 收口与面试卡 | — |

**三条不可砍**：

1. **阶段一（理解）**——砍了后面全是抄形状。
2. **Day 20 的静默丢弃修复**——数据丢失级缺陷，不修就是留着雷。
3. **Day 14 与 Day 22 的两次测量**——没有数字，整轮投入在简历上等于没发生。

---

## 五、明确不学

| 不学 | 理由 |
|---|---|
| 写 TS 应用或 Pi 扩展 | 目标是搬设计，不是换栈 |
| 通读 42 篇官方文档 | 只需 `how-pi-works`、`session-format`、`skills`、`compaction`、`extensions` 五篇 |
| `coding-agent` 三个大文件（35KB / 37KB / 23KB） | 装包与 CLI 装配，与 agent 机制无关 |
| `tui` / `env` / `chord` / `telemetry` / `evals` 实现 | 表现层与运维层 |
| Durable 状态机实现 | 规模不匹配，只读设计文档（问题定义 + v2 决策两节） |
| 自研 TUI | Python 有 rich / prompt_toolkit |

**TS 语言门槛只需 2–3h**，插在 Day 1 之前：类型注解、`async/await`，以及唯一必须专门补的**判别联合类型**（Pi 的会话条目全靠 `type` 字段分流）。不学装饰器、编译配置、前端框架。

---

## 六、产出物清单

**已完成**（2026-10-22）
- [x] `docs/notes/pi-architecture-analysis.md`
- [x] `docs/notes/pi-code-reading-guide.md`
- [x] `docs/notes/agent-harness-gap-analysis.md`

**阶段产出**
- [ ] `docs/notes/pi-notes-核心机制.md`（Day 1–6：八问答案 + 三张图）
- [ ] `agent/core/message.py`：消息三态（Day 7）
- [ ] `agent/core/event_reducer.py`：事件归约层（Day 8–9）
- [ ] `agent/mcp_client.py`：stdio 连接 + 工具注入（Day 10–11）
- [ ] `agent/skills/loader.py`：渐进披露 + **token 对比数据**（Day 12–14）
- [ ] `agent/core/memory_tree.py`：树形会话 + 分支回溯（Day 15–18）
- [ ] `agent/core/context_manager.py`：token 预算 + 修静默丢弃 + 拆轮（Day 19–20）
- [ ] `docs/notes/pi-vs-myagent.md`：完整对照表（Day 22）
- [ ] `docs/interview/` 新增 3 张卡：loop 收敛 / 上下文经济学 / 状态持久化取舍（Day 22）

---

## 七、简历与面试映射

| 计划项 | 命中模块 | 怎么讲 |
|---|---|---|
| 阶段一理解 | Agent 原理 | "Pi 用 terminate 悲观聚合替代 max_rounds，我认为工具设计良好时可去掉硬上限" |
| 渐进披露（Day 14） | 系统设计 + 数据 | "skill 从常驻改为按需，system token 降 X%" |
| 消息三态 + 事件归约（Day 7–9） | 系统设计 | "loop 只发事件、reducer 改状态，因此会话可重放" |
| MCP client（Day 10–11） | 工程能力 | "实现了 MCP 客户端，能把第三方 server 的工具映射进自己的注册表" |
| 会话树（Day 15–18） | 差异化亮点 | "会话是树不是数组，可回溯任意节点开新分支" |
| 压缩修正（Day 19–20） | 严谨性 | "发现原实现摘要失败会静默丢历史，改为保留原文 + 可见降级提示" |

前四项必答，后两项有则明显加分。

---

## 八、风险与止损

| 风险 | 触发信号 | 应对 |
|---|---|---|
| 卡在 `agent-loop.ts` | Day 3 结束仍写不出骨架 | 不追求逐行，先默写骨架；必要时只读 L172–330 |
| 阶段二回退 | Day 9 事件归约跑不通 | 整体回退分支，只保留阶段一笔记；半成品事件层比没有更糟 |
| 工作挤占 | 连续 3 天没推进 | 动用缓冲；缓冲耗尽按第四节降级路径砍 |
| 读完就忘 | Day 6 自测答不出 4 题 | 回八问清单定位薄弱点，不进入阶段二 |
| 会话树失控 | Day 17 PoC 超出预计 | 只做"新建分支 + 回溯"两个操作，不做 UI，不做合并 |

**一条总止损**：如果 Day 14 时阶段二仍未跑通，说明这次改造规模超出当前代码基础，**应停止并回退**，转为只做阶段一（理解）+ 阶段四（渐进披露）+ 阶段六（压缩修复）。这三项不依赖事件层重构，价值最高且风险最低。
