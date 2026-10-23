# Pi 阅读清单（行号级）

> **行号基准**：`D:\AiAgent\pi`，版本 `v1.4.0-22-g936e5f40a`（2026-10-22）。
> **用法**：不要从头读。按「梯队」走，每段读完立刻答一次右侧的「关注点」列，答不出来就回去重读这一段，不要往下走。
> **校验行号是否漂移**（仓库更新后执行）：
> ```bash
> cd D:/AiAgent/pi && grep -n "^async function runLoop\|^function shouldTerminateToolBatch\|^export function formatSkillsForPrompt" packages/agent/src/agent-loop.ts packages/coding-agent/src/core/skills.ts
> ```
> 期望：`runLoop` 在 172，`shouldTerminateToolBatch` 在 700，`formatSkillsForPrompt` 在 358。

---

## 总览：哪些行真的要读

| 梯队 | 文件 | 总行数 | 净读行数 | 读完给什么 |
|---|---|---|---|---|
| **一（必读）** | `packages/agent/src/types.ts` | 539 | 539 | 数据契约 |
| **一（必读）** | `packages/agent/src/agent-loop.ts` | 959 | 959 | 控制流本体 |
| **一（必读）** | `packages/agent/src/agent.ts` | 623 | 623 | 状态与对外 API |
| 二 | `core/system-prompt.ts` | 229 | 229 | 提示词工厂 |
| 二 | `core/skills.ts` | 514 | 约 200 | 渐进披露 |
| 二 | `core/messages.ts` | 196 | 196 | 投影 |
| 二 | `core/tools/read.ts` | 224 | 224 | 工具定义形状 |
| 二 | `core/mcp-servers.ts` | 346 | 约 110 | MCP 暴露策略 |
| 二 | `core/compaction/compaction.ts` | 1,104 | 约 320 | 上下文压缩 |
| 二 | `core/compaction/branch-summarization.ts` | 380 | 约 90 | 分支摘要 |
| 二 | `core/session-manager.ts` | 2,013 | 约 200 | 会话树 |
| 三 | `codemode/src/types.ts` + `declarations.ts` + `index.ts` | 254 + 340 + 65 | 约 300 | 上下文经济学 |

**合计：约 4,000 行精读，对应 Pi 全仓约 6 万行。**

---

## 第一梯队：必须全读（Day 1–6）

### A. `packages/agent/src/types.ts`（539 行，Day 1–2）

| 行区间 | 位置 | 关注的内容 | 答得出来才算过 |
|---|---|---|---|
| **33–46** | `StreamFn` | 供应商调用的抽象类型签名。注意它是**注入式类型**，不是类 | 换供应商要改几处代码？ |
| **47–63** | `ToolExecutionMode` / `QueueMode` / `AgentToolCall` | `parallel` 与 `sequential`、`all` 与 `one-at-a-time` 的两组枚举；`AgentToolCall` 用 `Extract<>` 从 `AssistantMessage` 里抠出来 | 为什么 `AgentToolCall` 不单独定义 |
| **66–106** | `BeforeToolCallResult` / `AfterToolCallResult` | **`terminate` 出现在这里**，不是消息字段。`AfterToolCallResult` 注释里的「不做深合并」 | `terminate` 属于哪一层 |
| **107–146** | `BeforeToolCallContext` / `AfterToolCallContext` / `AgentTurnContext` / `AgentTurnDecision` | 钩子能拿到什么、能返回什么。`AgentTurnDecision` 是 `continue`/`end` 的判别联合 | 想在工具执行前拦截，用哪个钩子 |
| **147–192** | `FinishTurn` / `AgentLoopTurnUpdate` / `PrepareRequest*` / `PrepareNextTurnContext` | 一轮结束后能否改下一轮的模型与提示词 | `finishTurn` 返回 `end` 会怎样 |
| **193–348** | `AgentLoopConfig` | **全文件最密的一段**：10 个回调 + `toolExecution`。逐个看清「不给这个回调时默认行为是什么」 | 能默数出 10 个回调名 |
| **349–373** | `ThinkingLevel` / `CustomAgentMessages` | **空接口 + declaration merging**。核心包不认识任何业务消息类型 | 新增自定义消息要不要改核心包 |
| **374–381** | `AgentMessage` | `Message ∪ CustomAgentMessages[...]`，两层消息模型的起点 | 会话记录与模型输入是同一套类型吗 |
| **382–423** | `AgentState` | `systemPrompt` 为什么 `readonly`；`tools`/`messages` 的 setter 为什么拷贝数组；**L395–400 注释：工具集变更会在下次请求前用 system 消息告知模型** | 接 MCP 动态注册工具时必须补哪条 |
| **424–465** | `AgentToolResult` / `AgentToolCallOutcome` / `AgentToolUpdateCallback` | 结果的三分结构：`content`（给模型）/ `details` / `structuredContent`（给 UI）；`terminate` 在这里 | 工具返回值有几层消费者 |
| **466–501** | `AgentTool` | 工具契约全貌：`parameters`、`execute`、`systemPromptContribution` | 工具能否影响提示词 |
| **502–515** | `AgentContext` | 一次运行期的不可变快照 | 和 `AgentState` 的区别 |
| **516–539** | `AgentEvent` | **10 类事件全集**。逐个标：哪些会改状态 | 哪三类事件丢了不影响状态 |

> Day 2 已把 66–539 的结论写进 `docs/notes/pi-notes-核心机制.md`，可直接对照。

### B. `packages/agent/src/agent-loop.ts`（959 行，Day 3–5）

| 行区间 | 位置 | 关注的内容 | 答得出来才算过 |
|---|---|---|---|
| **1–46** | imports / `AgentEventSink` / `DEFAULT_AGENT_IDENTITY` | `emit` 的类型：`Promise<void> \| void` —— 同步异步都能塞 | 事件消费方可以是同步的吗 |
| **47–110** | `agentLoop`(47) / `agentLoopContinue`(80) | 两个薄封装，只做参数归一 | 它们和 `runAgentLoop` 什么关系 |
| **111–171** | `runAgentLoop`(111) / `runAgentLoopContinue`(137) / `createAgentStream`(162) | 入口如何把 loop 包成 `EventStream` | 事件是怎么流出来的 |
| **172–190** | `runLoop` 初始化 | **L184 首次 steering 轮询**——用户在你还没开始时就输入了 | 为什么循环开始前要轮询一次 |
| **188–192** | 双层循环入口 | 外层 `while(true)` + 内层 `while (hasMoreToolCalls \|\| pendingMessages.length > 0)` | 两层各管什么 |
| **194–232** | turn 准备 | `prepareNextTurn`；**L213 二次轮询**（等了很久的准备工作后补收 steering）；**L216 `turn_start`**；L221–223 把排队的用户消息作为 `message_start/end` 发出并入上下文 | 为什么 steering 要轮询两次 |
| **236–270** | 请求与流式 | `prepareRequest` 覆盖请求；`stopReason === "error" \| "aborted"` 的提前终止分支（L254） | 模型报错时循环怎么退出 |
| **272–300** | 工具执行与轮末 | 有 toolCall 才执行；`turn_end`（L296）；**L298 `finishTurn` 返回 `end` 直接结束** | 谁有权提前结束整个 agent |
| **305–330** | 内层退出 | **L311 follow-up 轮询**；L320 `explicitContinuation` | steering 与 follow-up 的区别 |
| **342–390** | `declareToolChanges`(342) / `withToolChanges`(377) | 工具增删如何变成一条 system 消息（配合 types L397） | 工具变更怎么告知模型 |
| **390–487** | `streamAssistantResponse` | **`transformContext` 与 `convertToLlm` 的调用点与先后**；流式事件转发；`addedPartial` 标志 | 投影发生在循环的哪一步 |
| **488–517** | `failToolCallsFromTruncatedMessage` | **输出被截断时整批工具调用作废**，注释解释原因 | 为什么不尝试执行已解析的调用 |
| **518–539** | `executeToolCalls` 分发 | 按 `toolExecution` 值走串行或并行 | |
| **540–595** | `executeToolCallsSequential` | 串行版：一个失败是否继续下一个 | |
| **596–671** | `executeToolCallsParallel` | **三段式**：prepare 串行 → 并发执行 → 结果按源序组装 | 两种顺序分别给谁 |
| **700–717** | `shouldTerminateToolBatch` | **悲观聚合公式**：全部 `terminate === true` 才停 | 三个工具两个说停会怎样 |
| **718–788** | `prepareToolCall` | 参数解析、`beforeToolCall` 调用、参数改写 | 钩子能改参数吗 |
| **789–800** | `emitToolExecutionUpdate` | **23 处 emit 里唯一不 `await` 的**，为什么 | 为什么它不能阻塞 |
| **801–921** | `runToolCall`(821) / `executePreparedToolCall`(831) / `finalizeExecutedToolCall`(869) | 单个工具从准备到落定的完整生命周期；`afterToolCall` 的合并规则 | 工具抛异常会变成什么 |
| **922–958** | `createErrorToolResult` / `emitToolExecutionEnd` / `createToolResultMessage` / `emitToolResultMessage` | 错误结果如何构造；`tool_result` 消息按什么顺序发出 | |

> **这一段是全计划最高价值区**。`runLoop`（172–341）建议读三遍：第一遍跟流程，第二遍只盯 `emit`，第三遍只盯 `pendingMessages` 的进出。

### C. `packages/agent/src/agent.ts`（623 行，Day 6）

| 行区间 | 位置 | 关注的内容 |
|---|---|---|
| **39–48** | `defaultConvertToLlm` | 核心包给的默认投影：直接 `filter` 掉非 `Message` 类型 |
| **49–114** | 常量 / `createMutableAgentState` | 可变状态与只读视图的分离 |
| **115–193** | `AgentOptions` / `PendingMessageQueue`(149) / `ActiveRun`(182) | 队列的 `all` 与 `one-at-a-time` 两种取法 |
| **194–274** | `class Agent` 字段 / `constructor`(238) | 一个 Agent 实例持有什么 |
| **275–307** | `subscribe`(275) / `state` getter(285) | **订阅返回取消函数**；`state` 是只读快照 |
| **308–344** | `steer` / `followUp` / `clear*` / `peek*` | 双通道的**对外入口**，与 loop 里的两个轮询点对应 |
| **350–378** | `abort` / `waitForIdle` / `reset` | `abort` 触发什么、`waitForIdle` 等的是什么 |
| **380–475** | `prompt`(382) / `continue`(393) / `normalizePromptInput`(422) / `runPromptMessages`(441) / `runContinuation`(457) / `createContextSnapshot`(469) | 一次 run 的完整装配过程 |
| **476–516** | `createLoopConfig` | **回调在这里被注入 loop**，与 types L193–348 对应 |
| **517–574** | `runWithLifecycle`(517) / `handleRunFailure`(542) / `finishRun`(560) | 失败时如何伪造一串事件（L554–557）把状态收敛到一致 |
| **575–623** | `processEvents` | **归约器**：7 个 case 改状态，3 类事件纯转发；`isStreaming` 何时落回 false（等所有 `agent_end` 监听器结算） |

---

## 第二梯队：按你的四个主题读

### 主题一 · 工具调用

| 文件 | 行区间 | 关注的内容 |
|---|---|---|
| `core/tools/read.ts` | **14–24** | `readSchema` + **`readToolSystemPromptContribution`(20)**：工具自己贡献提示词片段 |
| `core/tools/read.ts` | 40–72 | `ReadToolDetails`（给 UI 的结构化数据）/ `ReadOperations`（可替换的文件系统操作，用于测试） |
| `core/tools/read.ts` | **86–221** | `createReadToolDefinition`：一个工具的完整形状——参数校验、权限、截断、图片处理、错误构造 |
| `core/tools/read.ts` | 222–224 | `createReadTool` 工厂：为什么是工厂而不是单例 |
| `core/tools/index.ts` | **93–163** | `ToolName` 联合(95) / `allToolNames`(96) / `createToolDefinition`(118) / `createTool`(141)：工具注册表如何按名字建工具 |
| `core/tools/index.ts` | 164–224 | 三档工具集：`createCodingToolDefinitions`(164) / `createReadOnlyToolDefinitions`(173) / `createAllToolDefinitions`(182)——**同一批工具按用途切三种视图** |

### 主题二 · 提示词工厂

| 文件 | 行区间 | 关注的内容 |
|---|---|---|
| `core/system-prompt.ts` | 9–57 | `BuildSystemPromptOptions` / `SystemPromptSections = Record<string,string>` / **段名正则 `^[a-z][a-z0-9_-]*$`** |
| `core/system-prompt.ts` | 60–87 | 参数归一化 / `renderProjectContext` |
| `core/system-prompt.ts` | 88–127 | `buildRules`：规则段是拼出来的，不是硬编码 |
| `core/system-prompt.ts` | **128–198** | **核心**：段装配顺序 preamble→tools→rules→docs→addendum→project_context→skills→cwd→custom；**L174–185 skills 段的三级降级**（declared `read` → declared `bash` → selected 里的 indirect → 整段不装配）；L187–195 每段包 `<name>` |
| `core/system-prompt.ts` | 199–216 | `buildSystemPromptState`（返回结构）/ `buildSystemPrompt`（压成字符串） |
| `core/system-prompt.ts` | **217–229** | `diffSystemPromptSections`：知道自己哪段变了，才知道缓存能不能命中 |
| `core/messages.ts` | **148–196** | `convertToLlm`：投影的唯一实现，1→0 / 1→1 / 1→N |
| `core/messages.ts` | 11–28 | 两个摘要前缀常量：**措辞明确告知模型「前文已被压缩」** |

### 主题三 · MCP

| 文件 | 行区间 | 关注的内容 |
|---|---|---|
| `core/mcp-servers.ts` | 12–50 | 工具名通配匹配 / 三个内置 resource 工具 / `isMcpToolName` |
| `core/mcp-servers.ts` | **52–58** | **`McpExposure` 四档 + 别名（`codemode-deferred` → `codemode`）** |
| `core/mcp-servers.ts` | 59–150 | stdio 与 http 两种服务器配置；OAuth 字段；`isLoopbackRedirectUri` 的安全判断 |
| `core/mcp-servers.ts` | 155–233 | `mcpNamespace`（服务器名做工具前缀，防冲突）/ 配置校验链 |
| `core/mcp-servers.ts` | **234–247** | `getMcpToolExposure`：精确匹配 → 通配符匹配 → **默认值 `codemode`** |
| `core/mcp-servers.ts` | 308–346 | `McpServerRegistry`：注册、查找、按 server 批量取暴露策略 |
| 本地文档 | `docs/mcp.md` 全文 272 行 | 客户端协议细节、断线重连、resource 用法 |

### 主题四 · 记忆（三层，分别读）

#### 层 1：项目记忆

| 文件 | 行区间 | 关注的内容 |
|---|---|---|
| `core/resource-loader.ts` | **185 附近（约 180–205）** | 候选链 `AGENTS.override.md > AGENTS.md > AGENTS.MD > CLAUDE.md > CLAUDE.MD`（185 行）；加载范围为全局 + 仓库根到 cwd 的祖先链 |
| `core/system-prompt.ts` | 79–87 | `renderProjectContext`：这些文件怎么进提示词 |

#### 层 2：会话记忆

| 文件 | 行区间 | 关注的内容 |
|---|---|---|
| `core/session-manager.ts` | 41–56 | `CURRENT_SESSION_VERSION = 3` / `SessionHeader` |
| `core/session-manager.ts` | **57–196** | `SessionEntryBase`(57) 的 **`id` + `parentId`**；13 种 entry 类型；`SessionEntry` 联合(183) |
| `core/session-manager.ts` | 91–105 | `CompactionEntry`：`firstKeptEntryId` + `tokensBefore` + 完整提示词快照 |
| `core/session-manager.ts` | **286–315** | `migrateV1ToV2`：**v1 没有树，v2 才加上 `id/parentId`** —— 树结构如何长出来的直接证据 |
| `core/session-manager.ts` | 372–380 | `getLatestCompactionEntry`：多个压缩取哪个 |
| `core/session-manager.ts` | **390–416** | `buildSessionPath`：从 leaf 沿 `parentId` 回溯到根，再 reverse —— **会话树的全部实现就这 20 行** |
| `core/session-manager.ts` | 439–475 | `sessionEntryToContextMessages`：非消息类 entry 怎么变回消息 |
| `core/session-manager.ts` | **476–511** | `buildContextEntries`：压缩感知——取最新 compaction，丢弃其覆盖的旧条目，保留 `firstKeptEntryId` 之后 |
| `core/session-manager.ts` | 543–588 | `buildSessionProjection` / `buildSessionContext`：对外两个视图 |
| 本地文档 | `docs/session-format.md` 全文 293 行 | JSONL 字段定义、分支示例、版本迁移 |
| **跳过** | 588–2013 | 全是文件 IO、目录发现、头解析、锁与并发，与树模型无关 |

#### 层 3：上下文记忆

| 文件 | 行区间 | 关注的内容 |
|---|---|---|
| `core/compaction/compaction.ts` | 120–139 | `CompactionSettings` / 默认值：`reserveTokens`、`keepRecentTokens` |
| `core/compaction/compaction.ts` | **140–265** | 计量层：`calculateContextTokens`(140) / `getLastAssistantUsage`(148,166) / `estimateContextTokens`(196) —— **优先用真实 usage，拿不到才估算** |
| `core/compaction/compaction.ts` | **267–297** | `shouldCompact`：**阈值公式 `contextTokens > contextWindow - reserveTokens`** |
| `core/compaction/compaction.ts` | 298–350 | `estimateTokens`：per-message 估算，含图片固定折算（`ESTIMATED_IMAGE_CHARS = 4800`） |
| `core/compaction/compaction.ts` | 351–445 | 切点合法性：`isCutPointMessage` / `isTurnStartMessage` / `findValidCutPoints` |
| `core/compaction/compaction.ts` | **446–506** | `findCutPoint`：既要够 token 预算，又要不切断 tool_call 与 tool_result 的配对 |
| `core/compaction/compaction.ts` | **507–584** | 三套提示词：首次摘要(507) / 更新规则(540) / 更新提示(577) —— **增量摘要而非重摘要** |
| `core/compaction/compaction.ts` | 585–758 | 摘要生成：`getSummarizationFailure`(585) 失败判定 → `generateSummary`(644) → `generateSummaryWithUsage`(696) |
| `core/compaction/compaction.ts` | 759–858 | `prepareCompaction`(859) 的准备：`findProjectedCutPoint`(789)、`isProjectedTurnStart`(777) |
| `core/compaction/compaction.ts` | **929–1104** | `TURN_PREFIX_SUMMARIZATION_PROMPT`(929)：**切点落在轮中间时的补摘要**；`compact`(952) 主流程；`generateTurnPrefixSummary`(1062) |
| `core/compaction/branch-summarization.ts` | 108–252 | 分支摘要的取材与 token 预算裁剪 |
| `core/compaction/branch-summarization.ts` | 253–380 | 提示词与 `generateBranchSummary`(293) |
| 本地文档 | `docs/compaction.md` 全文 463 行 | 与代码互证，重点是保留策略与失败处理 |

---

## 第三梯队：codemode（Day 21，可选）

| 文件 | 行区间 | 关注的内容 |
|---|---|---|
| `codemode/src/types.ts` | 1–111 | `CodemodeTool`(14) / `CodemodeCall`(53) / **`CodemodeResult`(94)**：为什么结果是结构化的而不是文本 |
| `codemode/src/types.ts` | 112–254 | 沙箱选项：超时、内存、允许的内置函数 |
| `codemode/src/declarations.ts` | 45–140 | `renderDeclarations` / `renderToolSignature`：**把工具 schema 渲染成 TS 声明**，让模型写代码时能补全 |
| `codemode/src/declarations.ts` | 10–44 | 上限常量：`DEFAULT_INPUT_SCHEMA_MAX_CHARS = 16_000`、`MAX_REF_EXPANSIONS = 32` |
| `codemode/src/index.ts` | 1–65 | 全读，包入口很薄 |

---

## 项目侧对照（改造前先读自己的）

| 文件 | 行区间 | 关注的内容 | 对应 Pi |
|---|---|---|---|
| `agent/core/message.py` | **全 75 行** | `ToolCall`/`Message` 结构；`to_dict` 序列化边界；**`summary()` L73–75 的 `[对话历史摘要]` 前缀** | types.ts 374–539 / messages.ts 11–28 |
| `agent/core/agent_loop.py` | 98–137 | `run()`：主循环，注意**没有 terminate 判定，靠 max_rounds** | agent-loop.ts 172–341 |
| `agent/core/agent_loop.py` | 143–162 | `_check_skill_trigger` / `_build_tools_schema`：技能激活与工具 schema 组装 | agent-loop.ts 718–788 |
| `agent/core/agent_loop.py` | **163–275** | `_call_model`：**循环里直接 `console.print` 渲染，无事件层** | agent.ts 575–623 |
| `agent/core/agent_loop.py` | 276–338 | `_render_markdown` / `_execute_tool_call`：工具执行与结果构造 | agent-loop.ts 801–958 |
| `agent/tools/base.py` | 全 62 行 | `ToolResult.success` 有标志，但 `to_str()` **把错误压平成文本** | types.ts 424–465 |
| `agent/core/context_manager.py` | 25–69 | `estimate_tokens`：粗估方式 | compaction.ts 140–265 |
| `agent/core/context_manager.py` | **70–95** | `should_compress`（按轮数而非 token）/ `compress`，**摘要失败时 `return recent` 静默丢历史** | compaction.ts 267–297 / 585–758 |
| `agent/core/context_manager.py` | 96–104 | `_find_split_index`：按轮切，不检查 tool 配对 | compaction.ts 446–506 |
| `agent/core/session_manager.py` | 42–110 | `load_session` / `save_session`：线性 JSON 数组，无 `id/parentId` | session-manager.ts 57–196 / 390–416 |
| `agent/core/session_manager.py` | 133–176 | `pick_session_interactive`：会话选择，改造树的入口 | |
| `agent/skills/loader.py` | **96–113** | `build_skills_hint`（只输出 name+description）/ `extract_activation` | skills.ts 358–389 |
| `agent/agent_main.py` | 150–178 | `build_system_prompt`：**硬编码七个工具名，`"".join(parts)`** | system-prompt.ts 128–198 |

---

## 明确不读（省下来的时间就在这里）

| 范围 | 行数 | 为什么不读 |
|---|---|---|
| `coding-agent/src/core/agent-session.ts` | 单文件 164 KB | CLI 装配层，删掉 Pi 仍是 agent |
| `coding-agent/src/core/package-manager.ts` + `package-manager-cli.ts` | 87 KB + 37 KB | 包管理，与 agent 机制无关 |
| `packages/tui/**` | 整包 | 终端渲染，Python 有 rich / prompt_toolkit |
| `packages/durable/**` | 整包 | 崩溃恢复状态机，对个人规模是过度设计 |
| `packages/ai/src/**` | 整包（`types.ts` 915 行） | 只需知道 `Message` 有四种；多供应商归一化不必照搬 |
| `packages/telemetry/**`、`packages/evals/**`、`packages/env/**`、`packages/chord/**` | 整包 | 知道存在即可 |
| `session-manager.ts` 588–2013 | 1,425 行 | 文件 IO 与目录发现 |
| `compaction.ts` 1–51、1063–1104 | 约 190 行 | 辅助与边缘分支 |

---

## 按天拆开（配合 `pi-learning-plan.md` 的 22 天）

| Day | 文件 | 行区间 | 当天必须答出来的问题 |
|---|---|---|---|
| 1 | `types.ts` | 1–260 | 会话记录与模型输入是同一套类型吗 |
| 2 | `types.ts` | 260–539 | 哪三类事件丢了不影响状态 |
| 3 | `agent-loop.ts` | 111–341 | 默写双层循环骨架；steering 为什么轮询两次 |
| 4 | `agent-loop.ts` | 342–595 | 投影在循环哪一步；截断为何整批作废 |
| 5 | `agent-loop.ts` | 596–958 | 悲观聚合；并行时两种顺序分别给谁 |
| 6 | `agent.ts` | 1–623 | 事件如何归约成状态；`isStreaming` 何时落回 |
| 7–9 | 改造 `message.py` / `session_manager.py` | — | 消息三态 + 事件归约跑通 |
| 10–11 | `mcp-servers.ts` + `docs/mcp.md` | 12–58、234–346 | 默认暴露档位是什么；`deferred` 如何懒加载 |
| 12–14 | `skills.ts` + `system-prompt.ts` + `read.ts` | 358–389 / 128–229 / 14–24 | 渐进披露省多少 token；提示词分段为何返回字典 |
| 15–18 | `session-manager.ts` + `docs/session-format.md` | 57–196、286–315、390–511 | 多个 compaction 取哪个；树如何回溯 |
| 19–20 | `compaction.ts` + `branch-summarization.ts` | 120–297、446–584、929–1104 | 阈值公式；切轮中间为何要补摘要 |
| 21 | `codemode/src/*` | types 1–111、declarations 45–140 | 中间结果为何不进上下文 |
| 22 | 收口 | — | 三个数字 + 三张面试卡 |

---

## 阅读方法（三条）

1. **文件读、文档查。** 代码是正本，`docs/*.md` 用来对照术语和验证自己的理解，不要先读文档再读代码——会带着别人的框架看，看不出实现里的取舍。
2. **每段读完写一行。** 一行就够：「这段解决了什么问题，用什么手段」。写不出来说明只看了语法。
3. **每个判断都标行号。** 「工具失败会继续」不够，要能写「agent-loop.ts:869–921，`finalizeExecutedToolCall` 里……」。行号是可复现性的凭证，也是面试时唯一能证明你真读过的东西。
