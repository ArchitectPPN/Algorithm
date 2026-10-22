# Pi（earendil-works/pi）架构分析

> 调研日期：2026-10-22。数据来源分三级，正文中以标记区分：
> **[一手]** GitHub API / pi.dev 官方文档 / 包 README；**[二手]** 技术博客与第三方评测；**[待验证]** 无法核实的传播性说法。

---

## 一、事实卡

| 项目 | 值 | 来源 |
|---|---|---|
| 仓库 | `earendil-works/pi`（前身 `badlogic/pi-mono`） | 一手 |
| Stars / Forks | 117,029 / 14,950 | 一手（GitHub API，2026-10-22） |
| 创建时间 | 2025-08-09 | 一手 |
| 最近推送 | 2026-10-21 | 一手 |
| 版本 | v1.4.0 | 一手（Release commit） |
| 语言 / 协议 | TypeScript / MIT | 一手 |
| 运行时要求 | Node.js ≥ 22.19 | 一手 |
| 作者 | Mario Zechner（badlogic），libGDX 作者 | 一手 |
| 商业化 | 2026-04 Earendil 收购，推出 Lefos 云平台 | 二手 |
| 包数量 | **11 个**（不是早期文章的 5 个） | 一手（`packages/` 目录实测） |

> ⚠️ 中文技术圈流传的 "5 个包"、"77K stars"、"86K stars" 均为阶段性快照，已过时。

---

## 二、它是什么：一句话定位

**"A minimal agent harness"** —— 一个极简的 agent 驾驭框架，而不是一个固定产品。

官方 slogan：`There are many agent harnesses, but this one is yours.`
设计主张：`Adapt Pi to your workflows, not the other way around.`

它的产品形态有四种，共用同一套 agent 与 session 机制 **[一手]**：

1. **Interactive** —— 完整 TUI
2. **Print / JSON** —— `pi -p "query"`、`--mode json`，用于脚本和事件流
3. **RPC** —— stdin/stdout 上的 JSONL 协议，供非 Node 系统集成
4. **SDK** —— 在自己应用里嵌入 Pi（OpenClaw 是典型案例）

---

## 三、模块构成：11 个包的分层

`packages/` 实测目录：`agent`、`ai`、`chord`、`codemode`、`coding-agent`、`durable`、`env`、`evals`、`mcp`、`telemetry`、`tui`。

| 层 | 包 | 职责 |
|---|---|---|
| 交互 | `coding-agent` | 面向用户的 CLI 主入口：会话管理、compaction、skills、extensions |
| 交互 | `tui` | 自研终端 UI 库，差分渲染、内联图片、CJK 输入法处理 |
| 编排 | `agent` | **AgentHarness**：工具调用循环 + 状态管理。原 `pi-agent-core` |
| 编排 | `durable` | 持久化运行时：会话、任务、文档；崩溃恢复状态机 |
| 编排 | `chord` | 类型化宿主运行时：热重载插件、契约、复制状态、JSON-link RPC |
| 能力 | `ai` | 统一 LLM API：归一化 4 种 wire protocol（OpenAI Completions / Responses、Anthropic Messages、Google Generative AI），15+ providers |
| 能力 | `mcp` | MCP 集成（**后来加入**，见第六节） |
| 能力 | `codemode` | QuickJS(WASM) 沙箱：让模型写 JS 编排多次工具调用 |
| 能力 | `env` | 远程执行环境：Rust daemon + SSH，工具跑在远端机器，凭据留在本地 |
| 支撑 | `telemetry` | 厂商中立遥测契约、参考适配器、一致性测试 |
| 支撑 | `evals` | 评估 |

**关键设计约束**：分层依赖是被强制的，且每层可独立使用。可以只用 `ai` 做多供应商归一化，不引入 agent、TUI、CLI 任何代码 **[一手]**。

---

## 四、运行机制

### 4.1 Agent loop（核心）

官方描述极简 **[一手]**：

```
提交消息 → 加入当前活动分支
  ↓
构建请求（system prompt + 活动分支 + 工具定义 + 模型设置）
  ↓
Provider 流式返回（文本 + tool calls）
  ↓
记录响应 → 逐个执行 tool call → 记录结果     ← 一个 turn 结束
  ↓
工具结果或排队消息需要再请求？──是──→ 下一 turn
  ↓否
run 结束
```

与多数框架的三个差异点：

- **无终止工具**：循环在"助手回复不含 tool call"时自然停止，没有 task-complete 工具，也没有步数上限。工具可返回 `terminate: true` 跳过后续请求。
- **两条消息通道**：`steering message`（Enter，当前工具批次结束后注入，打断剩余工具）与 `follow-up message`（Alt+Enter，等 agent 完成后再进入）。这是"边跑边纠偏"的机制，不是简单的中断。
- **无 plan mode / 无内置 todo**：任务分解交给模型，或由扩展实现。

### 4.2 Context 装配

- system prompt = 基础指令 + 发现的 context 文件（`AGENTS.md`，从 `~/.pi/agent/`、父目录、当前目录逐级查找；`SYSTEM.md` 可整体替换或追加）
- 工具定义 + skill 描述随请求携带
- **skill 全文按需加载**（progressive disclosure）：平时只有一行描述占上下文，触发时才载入完整指令与工具 schema
- 扩展可在每轮前注入消息、过滤历史、实现 RAG 或长期记忆

### 4.3 Session 存储

- JSONL 文件，每条 entry 有 `id` 与 `parentId` → **会话是一棵树，不是一条线**
- `/tree` 导航到任意历史节点并从那里继续，产生新分支，**全部存在同一文件**
- compaction 插入一条摘要 entry，替换后续请求中的旧消息，**原始 entry 永久保留**
- compaction 触发条件：`contextTokens > contextWindow - reserveTokens`（默认 reserve 16384），保留最近约 20k tokens

### 4.4 Durable AgentHarness（工程上最硬的部分）**[二手，但细节具体]**

解决的命题：**外部效应无法同时做到"持久"与"恰好一次"**——Provider 已扣费、工具已改文件、Hook 已发请求，进程崩溃后既不能假装没发生，也无法保证只发生一次。

v2 的核心决策：**把程序计数器直接持久化**，用 `OperationStateRecord` 存全量可变状态（revision 单调递增，最新一条即权威状态），明确拒绝增量链与补丁重放。配套 `Lane` 机制：一个会话可挂多条命名执行通道（如 Slack 每 thread 一条 Lane），共享同一棵对话树但各自维护 leaf、队列与日志，harness 仍是单写者。

一句话概括其设计模式：**全量状态外置**——宁可多存，不可靠推理恢复。

---

## 五、为什么它能脱颖而出

按证据强度排序，前四条是一手可核的，后两条是二手传播。

### 1. 反向定位：所有人在做加法时它做减法

主流 harness（Claude Code / Codex / Cursor）在持续增功能：plan mode、sub-agents、权限弹窗、内置 todo、后台 bash、MCP 生态。Pi 把这些**做成可选项而非默认项**，官网甚至单列一节 "What we didn't build"，逐条给出替代方案（用 tmux 代替 sub-agent 与后台 bash、用容器代替权限弹窗、用 TODO.md 代替内置 todo）。

差异化极其清晰——它不为"功能多"竞争，为"你是主人"竞争。

### 2. 上下文经济学：<1000 token 的 system prompt

对比 Claude Code 约 10k token 的提示词。作者论点（2025-11 博客）**[二手]**：frontier model 已被 RL 训练成知道怎么当 coding agent，重提示词是在重复教它已经会的东西，而每个指令 token 都是从任务预算里扣的。

工程收益链条（**[二手]，但推理成立**）：
- 短且稳定的前缀 → KV cache 命中率高 → 重复轮次实际计费下降
- 工具少（默认 4 个：read / write / edit / bash）→ 工具选择熵低
- 无隐式注入 → 行为可预测

**代价同样明确**：这个赌注只在强模型上成立。弱模型上，厚 harness 是在承担"保底可靠性"的职责，抽掉它就抽掉了可靠性。

### 3. 自扩展：把"功能清单"变成"能力上限无穷"

扩展是 TypeScript 模块，运行时编译加载，无需预编译。能注册自定义工具、拦截工具调用、加斜杠命令、改 TUI 渲染、做会话持久化，提供 20+ 生命周期钩子。

最反直觉的一点：**用户缺什么，直接让 Pi 自己写**——它能读自己的源码和文档，写完 `/reload` 继续干活。官方示例目录有 50+ 个扩展（sub-agent、plan mode、permission gate、path protection、SSH、sandbox 等），本质上把"其他 harness 的内置功能"变成了"参考实现"。

### 4. 分层可复用 + 供应链偏执

11 个包任意组合使用，`ai`、`tui`、`codemode` 都能脱离 Pi 单独用。供应链侧：精确版本锁定、`save-exact=true`、`min-release-age=2`（不拉当天新发的包）、lifecycle script 白名单、`npm ci --ignore-scripts`、定时 `npm audit signatures`。对一个会执行代码的工具，这套防护的强度高于多数商业项目。

### 5. 生态飞轮（[二手]，需打折扣）

- OpenClaw 基于 Pi SDK 构建并爆发式增长，为 Pi 带来大量流量
- 社区扩展包 2000+（早期数据）
- Earendil 收购并商业化，形成"开源核心 + 商业云"结构

### 6. 数据佐证（[待验证]）

- Composio 评测（2026-08）：Pi 20/30 通过、单例成本 $0.028；Claude Code 16/30、$0.195
- Terminal-Bench 2.0 上与 Codex/Cursor/Windsurf 对比不落下风

> 这两组数字被中文博客广泛引用，但未见原始报告全文，只能作为"方向性佐证"，不宜当作定论。

---

## 六、争议与局限（比优点更值得读）

### 1. MCP 立场已经反转 **[一手，重要]**

早期 Pi 最标志性的主张之一是 "No MCP"，理由是某些 MCP server 会给上下文塞进 13,700+ token。但当前官网明确写着：

> **"No MCP — Now with MCP+Codemode"** —— MCP 已内置，Codemode 让 agent 在 JS 沙箱里组合工具调用。官方甚至专门写了一篇《Why we changed our minds》。

**这个反转的意义**：纯粹的"极简原教旨"在真实需求面前守不住。Pi 的应对方式不是加回工具，而是用 `codemode` 改变工具进入上下文的方式——不把 200 个工具 schema 塞进 prompt，而是让模型写脚本调用它们。这是"用架构解决取舍"，不是"妥协"。

对学习者而言，这是比"极简主义"本身更有价值的案例：**原则遇到需求时，应该换实现方式，而不是换原则**。

### 2. 没有内置权限系统 **[一手]**

工具与扩展以 Pi 进程的 OS 权限运行。官方明确说"project trust 只控制加载哪些项目资源，不沙箱化工具调用"，建议用户自行容器化（Gondolin 微 VM / Docker / OpenShell）。

诚实，但对无脑使用者是实打实的风险。

### 3. Bus factor 极低 **[二手]**

核心提交集中在 3 人以内。11.7 万 star 与个位数核心维护者之间的落差，是采用时的真实风险。

### 4. 其他已知问题 **[二手]**

- 长会话流式输出时 TUI 可能占满一个核（issue #6665，据称有第三方插件可压到 10-16%）
- 用 Claude Pro/Max 订阅跑第三方 harness 时，计费走 extra usage 而非 plan 额度
- 新贡献者 PR 默认自动关闭（需维护者 `lgtm` 才放行）

---

## 七、与本项目 `agent/` 的对照

你的 `agent/`（Python，7 工具 + 权限引擎 + 技能 + 子 agent + 上下文压缩）与 Pi 恰好是**同一问题的两个反向答案**，这个对照本身就是面试素材。

| 维度 | 你的项目 | Pi |
|---|---|---|
| 工具集 | 7 个（Read/Write/Edit/Grep/Glob/Bash/Task） | 默认 4 个（read/write/edit/bash） |
| 权限 | 内置三级引擎（allow/deny/ask + 危险模式） | 不内置，交给容器 |
| 子 agent | 内置 Task 工具 + 工具白名单 | 不内置，示例扩展或 tmux |
| 技能 | Markdown frontmatter + 关键词/模型自选 | 按需加载 + 渐进披露 |
| 上下文 | 70% 阈值 + 摘要压缩 | `contextWindow - reserveTokens` + 保留最近 20k |
| 会话 | 单条线性记忆（chat_memory.json） | JSONL 树 + parentId 分支 |
| 扩展 | 无 | TypeScript 运行时加载，20+ 钩子 |
| 崩溃恢复 | 无 | Durable Harness 全量状态机 |

**可直接迁移的三个点**（按投入产出排序）：

1. **会话树**（改造成本低，收益高）：把 `chat_memory.json` 从线性数组改成 `{id, parentId}` 条目表。立刻获得分支、回溯、多方案对比能力，且是"你懂状态管理"的硬证据。
2. **Skill 渐进披露**：你的技能是整篇注入，改成"常驻一行描述 + 触发后载入全文"，是最容易量化的上下文优化（可测 token 前后对比）。
3. **Compaction 公式化**：把"70% 阈值"换成 `contextTokens > window - reserve`，并区分"保留窗口"与"摘要范围"，理由更站得住。

**不建议照搬**：Durable 状态机（对你的规模是过度设计）、自研 TUI（Python 生态有 rich/prompt_toolkit，自研性价比低）。

---

## 八、面试可用的三句话

1. **定位**：Pi 不是一个功能更多的 coding agent，而是一个刻意做减法的 harness——默认 4 个工具、千 token 级 system prompt，把 sub-agent、plan mode、权限这些"别人内置的东西"降级为扩展，赌的是强模型不需要厚脚手架。
2. **机制**：它的循环没有终止工具也没有步数上限，靠"助手不再调工具"自然收敛；会话存成带 parentId 的 JSONL 树，支持任意节点分叉；上下文用 `window - reserve` 触发压缩，且压缩不销毁原始记录。
3. **判断**：它最值得学的不是极简主义，而是 MCP 立场反转的处理方式——需求回来了，不改原则，改工具进入上下文的方式（Codemode 用 JS 沙箱编排，避免工具 schema 淹没 prompt）。这说明架构的价值在于"如何取舍"，不在于"取舍哪一边"。

---

## 九、待验证清单

- [ ] OpenClaw "一周 14.5 万 star" 的说法原始出处（疑似把 OpenClaw 自身总 star 数误传为周增长）
- [ ] Composio 评测报告的原始链接与测试方法
- [ ] Terminal-Bench 2.0 的完整成绩表
- [ ] 默认工具集是否仍为 4 个（有源称默认含 grep/find/ls，可能是版本差异或 opt-in factory）
- [ ] `chord` 与 `durable` 的实际边界（两者都涉及状态与宿主，职责可能有重叠）

---

## 十、来源

**一手**
- GitHub API `repos/earendil-works/pi`（star/fork/创建时间/语言/协议）
- https://pi.dev （官网首页：定位、四模式、What we didn't build、MCP 立场）
- https://pi.dev/docs/latest/how-pi-works （agent loop / context / sessions / interfaces / trust）
- `packages/` 目录列表（11 包）、`packages/env/README.md`、`packages/codemode/README.md`

**二手**
- agentwiki.daytonellwanger.com/tools/pi（架构、扩展钩子、与 Claude Code/LangGraph 对比）
- 0bserver07.github.io/chimera/field-guide/pi（循环、工具约束、prompt 策略、终止启发式）
- pyshine.com/Pi-Mono-Full-Stack-AI-Agent-Toolkit（四层架构）
- wangruofeng007.com/blog/2026-08/pi-durable-agent-harness（Durable Harness v2、Lane、compaction、供应链）
- dreaming.press/posts/pi-minimal-coding-agent-harness（system prompt 对比与论点）
- altsol.tw/pi-agent-intro（Composio 评测、作者动机）
