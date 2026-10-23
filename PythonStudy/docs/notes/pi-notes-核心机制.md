# Pi 核心机制笔记

> 对应 `docs/plans/pi-learning-plan.md` 的阶段一。逐日追加，Day 1–6 完成后即成为"八问答案 + 三张图"的底稿。
> 源码基准：`D:\AiAgent\pi` v1.4.0-22。文档基准：本地 44 篇官方文档。

---

## Day 1 · `types.ts` L1–260：数据契约

### 一句话结论

`types.ts` 不是"类型定义文件"，它是 **Pi 对「agent 是什么」的形式化回答**。读完这 260 行，agent 的能力边界就确定了——因为所有策略都以回调和类型的形式被声明在这里，实现里没有别的地方可以偷偷加策略。

### 一、两层消息模型（本日核心）

Pi 有**两套消息类型**，而不是一套：

```
┌─ Agent 层（AgentMessage） ────────────────────────────┐
│  Message  ∪  CustomAgentMessages[keyof ...]           │
│  L374                                                  │
│  ↑ 会话记录的真实形态：可以有 UI 通知、状态提示、       │
│    artifact 等自定义消息，它们完整存在于 transcript     │
└───────────────────┬───────────────────────────────────┘
                    │  convertToLlm（L222）
                    │  ★ 唯一转换边界，每次 LLM 调用前执行
                    ▼
┌─ LLM 层（Message，来自 @earendil-works/pi-ai） ───────┐
│  SystemMessage / UserMessage /                          │
│  AssistantMessage / ToolResultMessage                   │
│  ↑ 模型能理解的四类，别的都在转换时被过滤或投影          │
└─────────────────────────────────────────────────────────┘
```

**收益**：会话记录是事实，模型看到的只是投影。你可以往 transcript 里塞任何东西而不污染提示词。

值得注意的是 `CustomAgentMessages` 默认是**空接口**（L365-367），靠 TypeScript 的 declaration merging 由应用侧扩展。核心包不认识任何具体自定义类型——这是"分层不带业务"的具体手法。

**三个"契约"值得单独记**，它们都是"must not throw"：

| 位置 | 契约 | 为什么 |
|---|---|---|
| `StreamFn`（L27-32） | 不得抛出、不得返回 rejected promise；失败必须编码进流，以 `stopReason: "error" \| "aborted"` 结束 | 错误是数据，不是异常——这是全局错误策略的源头 |
| `convertToLlm`（L203-204） | 不得抛错，返回安全兜底值 | 抛错会打断底层 loop 且不产生正常事件序列 |
| `transformContext`（L231） | 同上 | 同上 |

### 二、`AgentLoopConfig`：一份策略注入清单（L193–341）

实测是 **10 个回调 + 1 个策略字段**（早前导读写的"九回调"按不同口径统计，此处以代码为准）：

| # | 成员 | 时机 | 用途 | 不给会怎样 |
|---|---|---|---|---|
| 1 | `convertToLlm` **必填** | 每次请求前 | 投影到 LLM 消息 | 无法调用模型 |
| 2 | `transformContext` | 转换前 | 窗口裁剪、外部上下文注入 | 上下文无管理 |
| 3 | `getApiKey` | 每次请求前 | 短期 token（OAuth 会过期） | 依赖静态 key |
| 4 | `finishTurn` | 每轮结束、`turn_end` 前 | 返回 `{action:"end"}` 强制收尾 | 只能用自然收敛 |
| 5 | `prepareRequest` | 每次请求前 | 替换 context/model/thinking | 模型中途不可换 |
| 6 | `prepareNextTurn` | `turn_end` 后、下一轮前 | 同上，但可追加消息 | 同上 |
| 7 | `getSteeringMessages` | 工具批次结束后 | 中途纠偏 | 无 steering 通道 |
| 8 | `getFollowUpMessages` | agent 本该停止时 | 排队后续任务 | 无 follow-up 通道 |
| 9 | `beforeToolCall` | 参数校验后、执行前 | 权限拦截 `{block:true}` | 无权限层 |
| 10 | `afterToolCall` | 执行后、发事件前 | 结果改写（逐字段替换） | 结果不可加工 |
| — | `toolExecution`（字段） | — | `sequential` / `parallel`，**默认 parallel** | — |

这就是"核心包不认识任何 provider、存储、权限模型"的实现方式：**全部依赖注入**。也是它能被当 SDK 嵌入的根本原因。

### 三、`terminate` 的三态语义（易错点）

`terminate` 出现在**三处**，意思各不相同：

| 位置 | 含义 |
|---|---|
| `BeforeToolCallResult.terminate`（L73） | 被拦截时建议"本批次后停止" |
| `AfterToolCallResult.terminate`（L103） | 改写结果时建议"本批次后停止" |
| `AgentToolResult.terminate` | 工具自己返回的停止建议 |

三处的注释都是同一句话：**"只有批次内每个 finalized 结果都为 true 才提前终止"**。聚合实现在 `agent-loop.ts:700`。

**关键**：`terminate` 不是消息字段，是**工具结果**的字段。这一点在东侧改造时必须分清——它不该加在 `Message` 上。

---

## Day 1 · 与 `agent/core/message.py` 的对照

### 现状

`message.py` 75 行，`Message` 只有五个字段：`role` / `content` / `tool_calls` / `tool_call_id` / `name`，`content` 是 `Optional[str]` 纯文本。

### 字段级差距

| Pi 的字段 | 所在类型 | 你的现状 | 缺失后果 |
|---|---|---|---|
| `isError: boolean` | `ToolResultMessage` | 无 | **失败与成功无法区分**。工具出错只能靠抛异常或内容约定，`agent_loop` 无法统一处理 |
| `timestamp: number` | 四种消息都有 | 无 | 无法排序、无法统计耗时、会话树与压缩都依赖时间基准 |
| `usage: Usage` | `AssistantMessage`（必有）/ `ToolResultMessage`（可选） | 无 | **压缩阈值算不出来**、可观测性无从谈起（`context_manager` 现在用"字符数/3"估算） |
| `stopReason`（7 态） | `AssistantMessage` | 无 | 分不清正常结束 / 截断 / 出错 / 被中断，`"length"` 截断作废逻辑无处挂 |
| `errorMessage?: string` | `AssistantMessage` | 无 | 错误只能抛异常，不能降级成消息 |
| `details?: TDetails` | `ToolResultMessage` | 无 | 结构化结果无处存放，只能序列化进 `content` 字符串 |
| `nestedCalls?: NestedToolCalls` | `ToolResultMessage` | 无 | 子 agent / 嵌套调用无法追溯 |
| `content: (Text\|Image\|Thinking\|ToolCall)[]` | `AssistantMessage` | `Optional[str]` | 单条消息装不下"一边思考一边调工具"；无多模态 |

### 一处需要修正的旧判断

此前导读写"你的消息类型缺 `terminate`、`isError`、`durationMs` 三态"。实测后修正：

- **`terminate` 不属于消息**，属于工具结果（见上一节），不该加到 `Message` 上；
- **`durationMs` 不在 `ToolResultMessage`**，它出现在 `NestedToolCallRecord.durationMs`——只有嵌套调用才记录耗时。单次工具调用的耗时靠 `timestamp` 差值算。

正确的改造清单是四项，且顺序有依赖：

```
isError → timestamp → usage → stopReason
   ↑         ↑          ↑         ↑
 区分成败   时间基准   可测量   "length" 语义
```

前两项是结构性的（Day 7 就能做完），后两项分别服务于压缩（Day 19）与截断作废（Day 4 读到、Day 7 落地）。

---

## Day 1 自测（不看代码作答）

1. `convertToLlm` 为什么不能放在 provider 层，而要作为 `AgentLoopConfig` 的必填回调？
2. 如果要让 agent 支持"图片输入"，改动应该落在 `AgentMessage` 还是 `Message`？为什么？
3. 一个工具返回 `{terminate: true}`，另一个返回 `{terminate: false}`，同批次——循环会停吗？依据是哪一行？
4. 你的 `Message.summary()` 用 `role="user"` 加前缀模拟摘要。Pi 用独立的 `CompactionSummaryMessage` 角色。两种做法各有什么代价？

> 第 4 题没有标准答案，但答案会直接决定 Day 19 怎么改 `context_manager.py`。

---

## Day 2 预习：剩下的 L260–540 有什么

- `getSteeringMessages` / `getFollowUpMessages` 的**调用点差异**（本日只看到声明，时机要等 Day 3–5 读 loop）
- `AgentState` 与 `systemPrompt` 为什么是 `readonly`（L376 起）
- `AgentEvent` 的事件全集——**Day 4–5 的所有结论都建立在这组事件上**
