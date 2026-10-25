# Pi 组件学习地图

> 以「组件」为轴重排 Pi 的学习内容，替代按文件顺序的读法。
> 数据来源：`D:/AiAgent/pi`（v1.4.0-22-g936e5f40a），行数为实测。
> 日期：2026-10-23

---

## 零、结论先讲

你列的四个组件——**工具调用、提示词工厂、MCP 工具、记忆**——抓住了 agent 的主体，但作为一个学习框架，缺了三层，而且其中一个需要拆开：

| 你列的 | 实际状态 | 处理方式 |
|---|---|---|
| 工具调用 | 成立 | 保留，但要连上「工具自己贡献提示词」这一环 |
| 提示词工厂 | 成立，且比想象中重要 | 保留，这是 Pi 最被低估的模块 |
| MCP 工具 | 成立 | 保留，但重点在客户端与暴露策略，不在协议 |
| 记忆 | **需要拆成三层** | 拆为项目记忆 / 会话记忆 / 上下文记忆 |
| ——（缺） | Provider 抽象层 | **补**。没有它，agent 只能接一家模型 |
| ——（缺） | 事件总线与流式 | **补**。没有它，UI 无法实时，也无法观测 |
| ——（缺） | 权限与安全边界 | **补**。这是"能不能上生产"的分水岭 |

一句话概括：**四个主题覆盖了 agent 的"功能面"，缺的三层是"可运行面"**。功能面决定它能做什么，可运行面决定它能不能真的跑起来、扛住误用、被人看懂。

---

## 一、九层组件全景

按「依赖方向由下到上」排列。删掉下面任何一层，Pi 都不再是 agent。

| 层 | 组件 | Pi 落点 | 规模 | 对应你的文件 |
|---|---|---|---|---|
| 1 | **Provider 抽象** | `packages/ai/src/types.ts`、`providers/` | 915 行 + 30+ provider | `agent/providers/`（base 55 + openai 185 + anthropic 138） |
| 2 | **数据契约** | `packages/agent/src/types.ts` | 539 行 | `agent/core/message.py` 75 行 |
| 3 | **控制循环** | `packages/agent/src/agent-loop.ts` | 959 行 | `agent/core/agent_loop.py` 338 行 |
| 4 | **状态与事件** | `packages/agent/src/agent.ts` | 623 行 | 无独立层（散在 loop 与 session） |
| 5 | **工具系统** | `coding-agent/src/core/tools/` | 4,648 行（8 个工具） | `agent/tools/` 884 行（7 个工具） |
| 6 | **提示词工厂** | `core/system-prompt.ts` + `prompt-templates.ts` | 229 + 325 行 | `agent_main.py:150` 的 `build_system_prompt`（28 行） |
| 7 | **上下文与记忆** | `session-manager.ts` / `compaction/` / `resource-loader.ts` | 2,013 / 1,484 / 1,287 行 | `session_manager.py` 176 + `context_manager.py` 104 |
| 8 | **外部接入** | `packages/mcp/` + `core/mcp-servers.ts` + `codemode` | 3,277 + 346 + 254 行 | `mcp_servers/jira/server.py` 357 行（只有 server） |
| 9 | **安全与可观测** | `permissions` 概念散在 extensions + `telemetry.ts` + `durable/` | — | `permissions/engine.py` 197 行 |

三点值得注意：

1. **你的项目在第 4 层是空的**。没有独立的状态层，`isStreaming`、消息列表、token 计数散在 loop 与 session 里。这是后面事件归约改造（Day 7–9）要补的。
2. **第 6 层你只有 28 行，Pi 有 554 行**。差距不在"会不会拼字符串"，而在"能不能分段、能不能 diff、能不能条件装配"。
3. **第 8 层你是反的**。Pi 的核心是「能当 MCP 客户端，去接别人的工具」；你实现的是「当 MCP 服务端，让别人接你的工具」。方向相反，Day 10–11 要补。

---

## 二、四个主题逐个拆解

### 主题一：工具调用

**Pi 的落点**

| 环节 | 文件 | 关键函数/行 |
|---|---|---|
| 工具接口定义 | `agent/src/types.ts` | `AgentToolResult` L424、`AgentToolCallOutcome` L449 |
| 单个工具实现 | `core/tools/read.ts` | `createReadToolDefinition` L86、`createReadTool` L222 |
| 工具注册 | `core/tools/index.ts` | 纯 re-export，无注册表 |
| 工具执行 | `agent/src/agent-loop.ts` | prepare 串行 → execute 并发 → 结果按源序发出 |

**三个必须理解的机制**

**（1）每个工具有两个工厂函数，不是一个。**

```ts
createReadToolDefinition(...)  // 定义：schema + 执行 + 渲染器
createReadTool(cwd, options)   // 实例：绑定 cwd 后可直接调用
```

分两层是因为**定义要能被序列化传给模型，实例要绑定运行时环境**。你的 `tools/base.py` 62 行只做了实例，没有把"定义"独立出来——所以工具无法在运行时被动态注册或替换。

**（2）工具自己贡献提示词片段。**

```ts
// core/tools/read.ts:20
export const readToolSystemPromptContribution = { ... }
```

这是一条把「工具调用」和「提示词工厂」缝在一起的线：**每个工具自带一段说明，装配 system prompt 时按当前启用的工具集动态拼接 `tools` 段**。你在 `agent_main.py:158` 用一段固定文字列了七个工具名，工具增删要手改提示词；Pi 是工具注册时自动带入。

**（3）`terminate` 是悲观聚合的。**

`AgentToolResult.terminate`（L445）注释写得很清楚：只有当**批次内每一个** finalize 的结果都为 true 才提前终止。你项目里 `max_rounds` 是硬上限，语义不同。

**你的现状**：7 个工具、884 行，覆盖 read/write/edit/bash/grep/glob/task。缺 `find`/`ls`，更缺的是「工具 → 提示词」的自动联动。

**对应 Day**：3–5（读 loop 里的执行流程）、7–9（工具结果三态：`isError` / `terminate` / `durationMs`）。

---

### 主题二：提示词工厂

这是 Pi 最被低估的模块，也是最容易被当成"拼字符串"而跳过的地方。

**Pi 的真实形态**

```ts
// system-prompt.ts:128
export function buildSystemPromptSections(input): SystemPromptSections
//                       ↑ 返回 Record<string, string>，不是 string
```

返回**分段字典**而非一整串，每一段再被 `<name>...</name>` 包裹（L191）。这个选择带来三个能力：

| 能力 | 由什么支撑 | 用途 |
|---|---|---|
| **精确 diff** | `diffSystemPromptSections`（L217） | 知道这次请求相对上次哪段变了 → 缓存命中率优化 |
| **条件装配** | `skills` 段依赖 `read`/`bash` 是否存在 | 工具裁剪后提示词自动瘦身 |
| **可插拔** | `customSections` 参数 | 应用侧可插入 `mcp_servers`、`project_context` 等自定义段 |

**已实测的分段清单**（`buildSystemPromptSections` 实际产出的键）：

```
preamble        ← 身份定义，一句 "You are an expert coding assistant..."
tools           ← 按启用工具动态生成，来自各工具的 SystemPromptContribution
rules           ← buildRules(declaredTools, toolGuidelines, promptGuidelines)
docs            ← Pi 自身文档路径（仅当用户问 pi 时读）
addendum        ← appendSystemPrompt，上层追加
project_context ← AGENTS.md 等上下文文件，见主题四
skills          ← 渐进披露的 name/description/location
cwd             ← 工作目录
<mcp_servers>   ← 由 mcp-servers.ts 注入，见主题三
<自定义段>       ← customSections
```

**一处极细的设计**（L176–182）：

```ts
const readers = ["read", "bash"] as const;
const skillFileReadTool =
  readers.find((t) => declaredTools.includes(t)) ??
  (readers.some((t) => selectedTools.includes(t)) ? "indirect" : undefined);
if (skillFileReadTool && skills.length > 0) { /* 才装配 skills 段 */ }
```

三级降级：先找 `read`，没有则退到 `bash`（能用 `cat` 读），两个都被隐藏则标为 `indirect`，都不可用则**整个 skills 段不装配**。含义是：**渐进披露的前提是模型有办法自己去读文件**。如果只给了 name/location 却没给任何读文件的工具，这段提示词就是纯噪音。

这一条可以直接搬到你项目：你现在无论工具集如何都无条件注入 skills 提示。

**你的现状**：`build_system_prompt` 28 行，`"".join(parts)`，单字符串。没有分段、无法 diff、工具清单硬编码。

**对应 Day**：6（`agent.ts` 的装配边界）、12–14（skills 段渐进披露，改的就是这里）。

---

### 主题三：MCP 工具

**关键判断：重点不在协议，在暴露策略。**

协议部分（`initialize` / `tools/list` / `tools/call`）是标准 JSON-RPC，你已经完整实现过一遍（`mcp_servers/jira/server.py` 357 行），不需要重学。真正值得学的是 Pi 解决的那个问题：**MCP 服务器一多，工具数会爆炸**。

**四档暴露策略**（`mcp-servers.ts:52`，实测代码）：

```ts
export type McpExposure = "codemode" | "deferred" | "direct" | "hidden";
```

| 档位 | 行为 | 什么场景用 |
|---|---|---|
| `direct` | 像普通工具一样声明给模型 | 少量、高频、必须直连的工具 |
| `deferred` | 先不声明，等 `tool_search` 载入后才可直调 | 工具多但不确定用哪个 |
| `codemode` | 不声明、不进描述，只能在 codemode 脚本里用 `searchTools()` 找 | **默认值** |
| `hidden` | 注册但完全不可达 | 临时禁用单个工具 |

**默认值是 `codemode`，不是 `direct`**（L77 注释：`Default: codemode`）。这个反直觉的选择是整块设计的核心：新接一个 MCP 服务器时，它的十几个工具**默认一个都不进上下文**，模型只看到这个服务器的 namespace 和一个描述，需要时写脚本去调。

还支持按工具覆盖，支持通配符（`toolExposure: {"jira_*": "hidden"}`），精确名优先于通配符，通配符按对象里第一个匹配生效（`getMcpToolExposure` L234）。

**你的现状**：只有 server，没有 client。`agent_main.py:65` 定义了 `MCP_FILE` 但从未使用，全项目无 `ClientSession` / `stdio_client` 痕迹。补 client 时，**建议直接按四档设计**，而不是简单地把 MCP 工具平铺进工具列表——后者在你接第二个服务器时就会失控。

**对应 Day**：10–11。原本只计划了「连上并调通」，现在应加半天读 `McpExposure` 的设计动机。

---

### 主题四：记忆——必须拆成三层

这是你最需要修正的一处。你说"记忆"，但在 Pi 里这是三套**机制完全不同**的东西，混在一起学必然模糊。

| 层 | 存什么 | 生命周期 | Pi 落点 | 你的对应 |
|---|---|---|---|---|
| **项目记忆** | 项目规范、约定，如"这个仓库用 tabs" | 跨会话、跨用户，文件在仓库里 | `resource-loader.ts` L185 候选链 + `system-prompt.ts:79` `renderProjectContext` | 部分（`build_system_prompt` 里的 `[项目指令 (CLAUDE.md)]`） |
| **会话记忆** | 说过的话、调用过的工具 | 单个会话，可分支回溯 | `session-manager.ts` 2,013 行 + JSONL 树 | `session_manager.py` 176 行，线性 JSON |
| **上下文记忆** | 当前这次请求真的发给模型的部分 | 单次请求，随时被压缩 | `compaction.ts` 1,104 + `branch-summarization.ts` 380 | `context_manager.py` 104 行 |

**项目记忆的候选链**（`resource-loader.ts:185`，实测）：

```ts
const candidates = ["AGENTS.override.md", "AGENTS.md", "AGENTS.MD", "CLAUDE.md", "CLAUDE.MD"];
```

注意两点：`AGENTS.override.md` 排在最前（用于本地覆盖，可 gitignore）；同时兼容 `CLAUDE.md`（生态兼容，不是疏忽）。加载范围是**全局 + 从仓库根到 cwd 的整条祖先链**（L239–273），不是只读当前目录。

**三层的区分标准**（判断你该改哪一层时用）：

- 想改「agent 知道的项目规范」→ 项目记忆
- 想改「上次聊到哪、能不能回退」→ 会话记忆
- 想改「token 超了怎么办、压缩后丢不丢信息」→ 上下文记忆

**你的现状**：`context_manager.py` 104 行同时管"保留最近 N 轮"和"生成摘要"，是会话层与上下文层混在一起；且摘要失败时静默丢弃早期历史（`return recent`），这是数据丢失级问题。

**对应 Day**：15–18（会话树，对应会话记忆）、19–20（压缩，对应上下文记忆）。项目记忆目前不在计划里，建议并入 Day 14——它和提示词工厂是同一处代码。

---

## 三、需要补充的四层

### 1. Provider 抽象层（第 1 层，最底层）

Pi 有 30+ 个 provider 文件（`packages/ai/src/providers/`），但抽象只有一个：`Api` 类型 + `StreamFn`。

```ts
// agent/src/types.ts:33
export type StreamFn = (...) => Promise<AgentMessageEventStream>;
```

**关键设计**：`StreamFn` 在 `types.ts` 里被定义为**类型而非实现**，由调用方注入。核心包完全不知道 OpenAI 和 Anthropic 的区别。这是"可替换"能成立的前提。

你的 `providers/base.py` 55 行做了同样的事（抽象基类），只是没做成可注入的函数类型。这项你在第 1 周已经做对了，**学习时可以跳过，不要重复投入**。

### 2. 事件总线与流式（第 4 层）

`agent.ts` 的机制是「loop 只发事件，不直接改状态」，事件全集共 10 类（`types.ts` L518–540）：

```
agent_start / agent_end
turn_start  / turn_end
message_start / message_update / message_end
tool_execution_start / tool_execution_update / tool_execution_end
```

这 10 个事件是所有上层能力的共同基础：TUI 靠它实时渲染、telemetry 靠它埋点、session 靠它落盘、测试靠它断言。**你现在没有这一层**，所以 UI 只能用轮询或直接读全局状态。

### 3. 权限与安全边界（第 9 层）

Pi 的做法是**权限外置**——核心包不含权限逻辑，靠三处：

- `project-trust.ts` 97 行：项目级信任（首次进入某目录要确认）
- `trust-manager.ts` 246 行：信任状态管理
- `extensions/` 里的 permission gate 扩展（官方示例，非内置）

这与你相反：你的 `permissions/engine.py` 197 行是内置的 allow/deny/ask 分级。

**两种做法各有代价**，这是个好面试题：内置意味着强制、不可绕过、但要改核心；外置意味着灵活、可替换、但默认不安全（要用户主动装扩展）。Pi 敢外置，前提是它同时提供了容器隔离方案；你没有容器，所以内置是更合理的选择——**这个判断可以讲成主动设计，不是能力不足**。

### 4. 可观测与持久化（第 9 层）

`telemetry.ts` 只有 13 行（薄封装），真正的观测能力来自事件流的可订阅性。持久化则在 `durable/` 包（1,228 行 types + 914 行 harness），核心是「全量状态外置」，规模不匹配你的项目，只学原则。

**这一层是你简历数字的来源**：token 用量、工具成功率、平均轮次，全部从这里产出。

---

## 四、与 22 天计划的映射

好消息：**现有计划不需要重排**。它本来就是主题导向的，只是用了阶段名而非组件名。换个视角看即可：

| 现有阶段 | Day | 对应组件主题 |
|---|---|---|
| 一 · 读通内核 | 1–6 | 第 2 层（契约）+ 第 3 层（循环）+ 第 4 层（事件）+ 主题二（提示词工厂读法） |
| 二 · 消息三态 + 事件归约 | 7–9 | 主题一（工具结果）+ 第 4 层（补上事件层） |
| 三 · MCP client | 10–11 | 主题三 |
| 四 · 渐进披露 | 12–14 | 主题二 + 主题四之项目记忆 |
| 五 · 会话树 | 15–18 | 主题四之会话记忆 |
| 六 · 压缩正确性 | 19–20 | 主题四之上下文记忆 |
| 七 · codemode + 收口 | 21–22 | 主题三（暴露策略的延伸）+ 可观测 |

**唯一需要插入的**：`McpExposure` 四档设计（+0.5 天，并入 Day 10）；项目记忆候选链（+0.5 天，并入 Day 14）。合计 +1 天，仍在 3 天缓冲内。

**明确不投入的**：Provider 抽象（你已做对）、`durable` 状态机（规模不匹配）、自研 TUI（Python 有替代）。

---

## 五、四道自测题

按 `learning-checklist.md` 的三步法（能写 / 能一句话讲 / 能答追问），主题级验收：

1. **工具调用**：为什么工具要分 `Definition` 和实例两层？只做一层会失去什么能力？
2. **提示词工厂**：`buildSystemPromptSections` 返回字典而非字符串，除了 diff 之外还有什么好处？如果只返回字符串，`skills` 段的依赖判断要写在哪里？
3. **MCP**：为什么默认暴露策略是 `codemode` 而不是 `direct`？如果你的 agent 要接 5 个 MCP 服务器，每个 10 个工具，`direct` 会让 system prompt 增加多少 token？
4. **记忆**：三层记忆各自失效时的表现是什么？（提示：项目记忆失效 → 每次都违反项目约定；会话记忆失效 → 无法回退；上下文记忆失效 → 失忆或丢历史）

第 3 题可以实际测：接一个 MCP 服务器，用 `--mode json` 对比 `direct` 与 `codemode` 两种配置下的 system prompt 长度。

---

## 六、下一步

组件地图是索引，不替代动手。当前进度在 Day 1 已完成的 `types.ts` L1–260，明天 Day 2 读 L260–540 的 `AgentEvent` 事件全集——那正好是第 4 层的基础，也是主题一与本地图第 2、4 层的交汇点。

若要按主题优先，可以调整顺序为：**主题二（提示词工厂）提前到 Day 7 之前**，因为它的改造量最小、收益最直接（token 降幅可量化），且不依赖事件层。这是唯一一处我建议偏离文件顺序的地方。
