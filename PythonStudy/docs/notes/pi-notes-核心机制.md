# Pi 核心机制笔记

> 对应 `docs/plans/pi-learning-plan.md` 的阶段一。逐日追加，Day 1–6 完成后即成为"八问答案 + 三张图"的底稿。  
> 源码基准：`D:\AiAgent\pi` v1.4.0-22。文档基准：本地 44 篇官方文档。

---

## Day 1 · `types.ts` L1–260：数据契约

### 一句话结论

`types.ts` 不是"类型定义文件"，它是 **Pi 对「agent 是什么」的形式化回答**。读完这 260 行，**agent 的能力边界就确定了——因为所有策略都以回调和类型的形式**被声明在这里，实现里没有别的地方可以偷偷加策略。

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

#### "投影"的确切含义（2026-10-23 实读 `core/messages.ts` 后补）

"投影"容易被理解成"类型转换"，但两者在三点上不同。实测 `core/messages.ts:148-196` 的 `convertToLlm` 实现：

```ts
return messages
  .map((m): Message | undefined => {
    switch (m.role) {
      case "bashExecution":
        if (m.excludeFromContext) return undefined;        // 1 → 0，丢弃
        return { role: "user", content: [{ type: "text", text: bashExecutionToText(m) }], ... };
      case "custom": { ... return { role: "user", ... }; }
      case "branchSummary": { ... return { role: "user", ... }; }
      case "compactionSummary": { ... return { role: "user", ... }; }
      case "system": case "user": case "assistant": case "toolResult":
        return m;                                           // 1 → 1，原样透传
    }
  })
  .filter((m) => m !== undefined);                          // 执行上面的丢弃
```

**区别一：不是一一对应。** 用 `map` + `filter` 而非简单转换，因为它支持 **1→0（丢弃）**、**1→1（透传）**、以及 1→N（文档示例用 `flatMap` 演示拆分）。

**区别二：有损。** `BashExecutionMessage` 有七个字段（`command`/`output`/`exitCode`/`cancelled`/`truncated`/`fullOutputPath`/`timestamp`），投影后只剩一段文本加时间戳——`exitCode: 1` 变成句子里的 "Command exited with code 1"。`CompactionSummaryMessage.tokensBefore` 则**完全丢弃**。

这正是投影的价值所在：`tokensBefore` 是 UI 显示"压缩了多少"需要的，模型不需要。**降维时只留该留的，其余留在会话记录里**。

**区别三：降维方向固定。** 四种自定义消息在 AgentMessage 层各有独立 `role`，投影后**全部变成 `user`**：

| AgentMessage | 投影结果 | 处理方式 |
|---|---|---|
| `system` / `user` / `assistant` / `toolResult` | 同名类型 | 原样透传 |
| `bashExecution` | `user` | 转文本；`excludeFromContext` 时丢弃 |
| `custom` | `user` | 内容直接搬 |
| `branchSummary` | `user` | 加 XML 前缀后缀 |
| `compactionSummary` | `user` | 加 XML 前缀后缀 |

原因不可能更改：**LLM API 只认四种 role，无法新增第五种**。所以"自定义消息类型"只能存在于 AgentMessage 层，到了 LLM 层必须归一。

这也修正了本文 Day 1 自测题第 4 题的预设（已改）——Pi 并非"用独立角色"，它在会话记录层用独立 role，投影后同样是 `user` 加前缀，**与你的 `Message.summary()` 做法同构**。差别在三处（实测 `message.py:73-75`）：

```python
return cls(role="user", content=f"[对话历史摘要]\n{content}")
```

| | Pi | 你的实现 | 影响 |
|---|---|---|---|
| 前缀定义 | 具名常量 `COMPACTION_SUMMARY_PREFIX` | 内联 f-string | 常量可被测试引用、可被检索；内联串无法断言 |
| 措辞 | "The conversation history before this point **was compacted** into the following summary" | "[对话历史摘要]" | Pi 明确告知模型**前文已被丢弃**；你的措辞让模型以为摘要就是全部历史 |
| 边界标记 | XML `<summary>...</summary>` | 方括号 | XML 可被可靠解析与截断；方括号在内容含 `]` 时会歧义 |

第一条是工程习惯，后面两条是**语义正确性**问题。第二处尤其值得改：模型不知道上下文被压缩过，就会把摘要当成完整历史，遇到"你刚才说的第三点是什么"这类追问会答得似是而非。

**三个"契约"值得单独记**，它们都是"must not throw"：

| 位置                       | 契约                                                                          | 为什么                     |
| ------------------------ | --------------------------------------------------------------------------- | ----------------------- |
| `StreamFn`（L27-32）       | 不得抛出、不得返回 rejected promise；失败必须编码进流，以 `stopReason: "error" \| "aborted"` 结束 | 错误是数据，不是异常——这是全局错误策略的源头 |
| `convertToLlm`（L203-204） | 不得抛错，返回安全兜底值                                                                | 抛错会打断底层 loop 且不产生正常事件序列 |
| `transformContext`（L231） | 同上                                                                          | 同上                      |

### 二、`AgentLoopConfig`：一份策略注入清单（L193–341）

实测是 **10 个回调 + 1 个策略字段**（早前导读写的"九回调"按不同口径统计，此处以代码为准）：

| #  | 成员                    | 时机                | 用途                                        | 不给会怎样          |
| -- | --------------------- | ----------------- | ----------------------------------------- | -------------- |
| 1  | `convertToLlm` **必填** | 每次请求前             | 投影到 LLM 消息                                | 无法调用模型         |
| 2  | `transformContext`    | 转换前               | 窗口裁剪、外部上下文注入                              | 上下文无管理         |
| 3  | `getApiKey`           | 每次请求前             | 短期 token（OAuth 会过期）                       | 依赖静态 key       |
| 4  | `finishTurn`          | 每轮结束、`turn_end` 前 | 返回 `{action:"end"}` 强制收尾                  | 只能用自然收敛        |
| 5  | `prepareRequest`      | 每次请求前             | 替换 context/model/thinking                 | 模型中途不可换        |
| 6  | `prepareNextTurn`     | `turn_end` 后、下一轮前 | 同上，但可追加消息                                 | 同上             |
| 7  | `getSteeringMessages` | 工具批次结束后           | 中途纠偏                                      | 无 steering 通道  |
| 8  | `getFollowUpMessages` | agent 本该停止时       | 排队后续任务                                    | 无 follow-up 通道 |
| 9  | `beforeToolCall`      | 参数校验后、执行前         | 权限拦截 `{block:true}`                       | 无权限层           |
| 10 | `afterToolCall`       | 执行后、发事件前          | 结果改写（逐字段替换）                               | 结果不可加工         |
| —  | `toolExecution`（字段）   | —                 | `sequential` / `parallel`，**默认 parallel** | —              |

这就是"核心包不认识任何 provider、存储、权限模型"的实现方式：**全部依赖注入**。也是它能被当 SDK 嵌入的根本原因。

### 三、`terminate` 的三态语义（易错点）

`terminate` 出现在**三处**，意思各不相同：

| 位置                                    | 含义              |
| ------------------------------------- | --------------- |
| `BeforeToolCallResult.terminate`（L73） | 被拦截时建议"本批次后停止"  |
| `AfterToolCallResult.terminate`（L103） | 改写结果时建议"本批次后停止" |
| `AgentToolResult.terminate`           | 工具自己返回的停止建议     |

三处的注释都是同一句话：**"只有批次内每个 finalized 结果都为 true 才提前终止"**。聚合实现在 `agent-loop.ts:700`。

**关键**：`terminate` 不是消息字段，是**工具结果**的字段。这一点在东侧改造时必须分清——它不该加在 `Message` 上。

---

## Day 1 · 与 `agent/core/message.py` 的对照

### 现状

`message.py` 75 行，`Message` 只有五个字段：`role` / `content` / `tool_calls` / `tool_call_id` / `name`，`content` 是 `Optional[str]` 纯文本。

### 字段级差距

| Pi 的字段                                         | 所在类型                                            | 你的现状            | 缺失后果                                                  |
| ---------------------------------------------- | ----------------------------------------------- | --------------- | ----------------------------------------------------- |
| `isError: boolean`                             | `ToolResultMessage`                             | 无               | **失败与成功无法区分**。工具出错只能靠抛异常或内容约定，`agent_loop` 无法统一处理     |
| `timestamp: number`                            | 四种消息都有                                          | 无               | 无法排序、无法统计耗时、会话树与压缩都依赖时间基准                             |
| `usage: Usage`                                 | `AssistantMessage`（必有）/ `ToolResultMessage`（可选） | 无               | **压缩阈值算不出来**、可观测性无从谈起（`context_manager` 现在用"字符数/3"估算） |
| `stopReason`（7 态）                              | `AssistantMessage`                              | 无               | 分不清正常结束 / 截断 / 出错 / 被中断，`"length"` 截断作废逻辑无处挂          |
| `errorMessage?: string`                        | `AssistantMessage`                              | 无               | 错误只能抛异常，不能降级成消息                                       |
| `details?: TDetails`                           | `ToolResultMessage`                             | 无               | 结构化结果无处存放，只能序列化进 `content` 字符串                        |
| `nestedCalls?: NestedToolCalls`                | `ToolResultMessage`                             | 无               | 子 agent / 嵌套调用无法追溯                                    |
| `content: (Text\|Image\|Thinking\|ToolCall)[]` | `AssistantMessage`                              | `Optional[str]` | 单条消息装不下"一边思考一边调工具"；无多模态                               |

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
4. 你的 `Message.summary()` 用 `role="user"` 加前缀模拟摘要。Pi 在 AgentMessage 层用独立的 `role: "compactionSummary"`，但投影到 LLM 层后**同样是 `user` + XML 前缀**。既然如此，多设一层独立 role 换来了什么？（提示：看 `tokensBefore` 字段在投影前后是否还在）

> 第 4 题没有标准答案，但答案会直接决定 Day 19 怎么改 `context_manager.py`。

---

## Day 2 · `types.ts` L260–540：回调契约、状态与事件

读取范围：`types.ts` L260–540（281 行）。为确认声明与实现是否一致，另核了 `agent-loop.ts` 的 23 处 `emit` 调用点与 `agent.ts` 的 `processEvents`。

### 一句话结论

**events 不是日志，是状态迁移的输入。** `loop` 只负责发，`agent.ts` 的 `processEvents` 负责折叠成状态，监听器只读不改。10 类事件里有 3 类**压根不改变任何状态**，只是转发。

---

### 一、十类事件的真实分类（实测 `agent.ts:575-622`）

`AgentEvent` 声明了 10 个 type（L516–539），但 `processEvents` 的 switch 只有 **7 个 case**：

| 事件                      | `processEvents` 有没有 case | 它做了什么                                    |
| ----------------------- | ------------------------ | ---------------------------------------- |
| `agent_start`           | **无**                    | 纯通知，直接转发监听器                              |
| `agent_end`             | 有                        | 清 `streamingMessage`                     |
| `turn_start`            | **无**                    | 纯通知                                      |
| `turn_end`              | 有                        | 若 assistant 且带 `errorMessage` → 记入 state |
| `message_start`         | 有                        | `streamingMessage = event.message`       |
| `message_update`        | 有                        | 同上（覆盖）                                   |
| `message_end`           | 有                        | 清 `streamingMessage`，`messages.push`     |
| `tool_execution_start`  | 有                        | `pendingToolCalls.add(id)`               |
| `tool_execution_update` | **无**                    | 纯通知                                      |
| `tool_execution_end`    | 有                        | `pendingToolCalls.delete(id)`            |

**这个分类是理解整层的钥匙**：无 case 的三个事件不携带状态语义，它们存在的唯一目的是让 UI 知道"某件事开始了"。判断标准很简单——**看它是否需要改变 `AgentState` 的任一字段**。

对照你自己的项目，这个分类的意义是：`turn_start` / `agent_start` / `tool_execution_update` 是**可以丢的**（丢了 UI 会少一次提示，但状态不会错）；`message_end` / `tool_execution_end` 是**不能丢的**（丢了状态就残）。

---

### 二、发射顺序（实测 `agent-loop.ts` 行号）

主路径（`runLoop`，L155 起）：

```
L155  agent_start
L156  turn_start
        ↳ 外层循环每轮：L216 turn_start（除首轮已发）
        ↳ L221/L222 message_start + message_end（注入的 steering/follow-up 消息）
L296  turn_end { message, toolResults }
L299  agent_end { messages }
```

单次 provider 响应内（`streamAssistantMessage`，L394–479）：

```
L430  message_start（仅当 provider 发 start 事件时）
L445  message_update × N（text_delta / thinking_delta / toolcall_delta…）
L464  message_end
```

工具执行（L494 / L552 / L607 三处 `tool_execution_start`，L930 `tool_execution_end`）：

```
tool_execution_start
tool_execution_update × N   ← 工具主动调 onUpdate 才有
tool_execution_end { result, isError, durationMs }
L957  message_start（toolResult 角色）
L958  message_end
```

注意最后一组：**工具结果也是一个正式消息**，有完整的 start/end 对。所以 `message_*` 事件覆盖四种角色，不止 assistant。

---

### 三、三处只有读代码才知道的契约

**（1）`message_start` 靠一个布尔标志保证恰好一次。**

`types.ts` 的声明看不出这一点。`agent-loop.ts:421-422` 有：

```ts
let partialMessage: AssistantMessage | null = null;
let addedPartial = false;
```

- provider 发了 `start`（L426）→ `addedPartial = true`，此时发 `message_start`（L430）
- provider **没发** `start`（非流式，或直接给 `done`）→ L456–463 判断 `!addedPartial` 才**补发** `message_start`

**含义**：不同 provider 的流式行为不一致，但事件消费方不需要知道这件事。**契约的稳定性由循环内部的一个标志维护，而不是靠 provider 自觉**。这是个值得借鉴的手法——你在写任何"对外承诺事件序列"的代码时，都需要这种幂等保护。

**（2）`tool_execution_update` 是唯一不 `await` 的事件。**

全文件 23 处 `emit`，22 处是 `await emit(...)`，只有 L791 是裸 `emit(...)`：

```ts
// agent-loop.ts:789 — 返回给工具的 onUpdate 回调
return (partialResult) => emit({ type: "tool_execution_update", ... });
```

因为它被包进 `ToolUpdateSink` 同步返回给工具，工具调用它时**不能阻塞自己的执行**。设计意图明确：**UI 渲染慢，不该拖慢工具**。

**（3）其余事件 `await` 的后果：监听器会阻塞控制流。**

`await emit` 意味着 `processEvents` 里 `for (const listener of this.listeners) await listener(event, signal)`（L619–621）会串行等待每个监听器。所以：

- 一个慢监听器（比如同步写磁盘）会拖慢整个 agent
- 这解释了 `types.ts:512-514` 那句：`agent_end` 是最后一个事件，但 **await 的 `agent_end` 监听器仍属 run settlement**，`isStreaming` 要等它们全部结算才变 false

**这是一条常被忽略的性能约束**：想加 telemetry 时，写文件必须异步化，否则每个事件都会成为一次 I/O 等待。

---

### 四、`AgentState` 的三个非常规设计（L382–421）

```ts
readonly systemPrompt: string;      // 只读
set tools(...) / get tools()        // accessor
set messages(...) / get messages()  // accessor
readonly isStreaming: boolean;
```


**（1）`systemPrompt` 只读，改提示词必须 append 一条 system 消息。**

L386 注释：`to change the prompt, append a system message with content or sections`。

原因在 L405：`System messages in the transcript carry the prompt and tool declarations`。**提示词不是外部配置，是对话记录的一部分**。这带来一个直接好处：会话回放时提示词自动还原，不需要单独存一份。

**（2）`tools` / `messages` 用 setter 拷贝数组。**

L395 / L403 注释都写了 `Assigning a new array copies the top-level array`。防的是调用方持有引用后原地 push，导致内部状态被外部篡改。

**（3）`tools` 变更会主动告知模型。**

L397–398：`Differences from the tools declared in the transcript are announced to the model with a system message before the next request`。

这一条很关键——**工具集变化不是静默的，会生成一条 system 消息**。否则模型可能按旧工具列表发起调用，得到"工具不存在"的错误。你的项目 `build_tools()` 是启动时一次装配，没有这个机制，但一旦支持动态注册工具（Day 10 接 MCP 就要），这条就必须补。

---

### 五、`AgentTool` 的六个字段（L466–499）

```ts
interface AgentTool<...> extends Tool<...> {
  label: string
  prepareArguments?: (args: unknown) => Static<TParameters>
  outputSchema?: TSchema
  execute: (...) => Promise<AgentToolResult<TDetails>>
  replay?: "never" | "safe"
  executionMode?: ToolExecutionMode
}
```

四个值得单独记：

| 字段 | 作用 | 为什么需要 |
|---|---|---|
| `prepareArguments` | schema 验证**前**的兼容垫片 | 模型给的原始参数可能不合 schema（如把数字写成字符串），这里是最后的修正机会 |
| `outputSchema` | 声明 `structuredContent` 的形状 | **给程序调用方看，不给模型看**（L430–431）。这是"同一结果两种消费者"的落地方式 |
| `executionMode` | 单工具覆盖全局并行/串行 | 有状态操作（改文件、执行命令）必须串行，纯读可以并行 |
| `replay` | durable 恢复策略 | `"never"` = 该副作用不可重放（如发消息）；`"safe"` = 可重放（如读文件） |

**`execute` 有一条强制契约**（L480–482）：

> Throw on failure, or return a result with `isError: true`; **do not only describe the failure in `content`**.

这是对"把错误写成正常返回值"这种做法的明确禁止。理由很实际：如果失败只体现在文本里，`isError` 为 false，那么重试逻辑、统计、UI 红色标记全部失效。

对照你的 `agent/tools/base.py`（62 行，已实读）——**这一处你比预期做得好，但有一个真实的压平问题**：

```python
@dataclass
class ToolResult:
    success: bool
    output: str = ""
    error: Optional[str] = None

    def to_str(self) -> str:
        if self.success:
            return self.output
        return f"[ERROR] {self.error or '未知错误'}\n{self.output}"
```

- **好的部分**：`success` 是独立字段，不是靠文本判断。这一点与 Pi 的 `isError` 同构，Day 7 加 `isError` 时改动很小。
- **问题在 `to_str()`**：错误被**压平成文本**，返回 `"[ERROR] 原因\n输出"`。Python 对象里的 `success=False` 在序列化成消息时就丢了，模型看到的只是一段以 `[ERROR]` 开头的普通字符串。

这正是 Pi 注释警告的变体——**标志留在对象里，却在序列化边界上被抹掉**。后果是可推的：模型无法通过结构化信号区分"工具失败"与"工具成功但输出里恰好有 ERROR 字样"，重试策略与统计也就无法可靠。
- **两处结构性缺失**：`output: str` 只能装字符串，没有 `details`（UI 渲染用的结构化数据）与 `structuredContent`（程序调用方用）的分离；也没有 `terminate`。`durationMs` 可以用 `timestamp` 差值算，不需要独立字段。

---

### 六、10 回调 + 1 策略字段：契约细节表

Day 1 只列了清单，这里补"时机"与"不给会怎样"。全部出自 L193–342 的注释，无推测。

| # | 回调 / 字段 | 调用时机 | 不给会怎样 |
|---|---|---|---|
| 1 | `convertToLlm` | 每次请求前，`AgentMessage[]` → `Message[]` | 无默认值，必填（Day 1 已记 must-not-throw） |
| 2 | `transformContext` | 同上，在 convert 之前，可裁剪/压缩 | 上下文原样发送，压缩能力为零 |
| 3 | `getApiKey` | 请求前取密钥 | 由 provider 自行从环境读 |
| 4 | `finishTurn` | 本轮 assistant 回复后，判断是否结束 | 结束条件只看有无 tool call |
| 5 | `prepareRequest` | **每次** provider 请求前（含首次），待发消息已 append | 无法在请求前替换 model / thinking level |
| 6 | `prepareNextTurn` | `turn_end` 之后、下一轮开始前 | 无法按轮次动态换模型 |
| 7 | `getSteeringMessages` | 当前 tool calls 执行完后，**除非** `finishTurn` 已结束 | 无法中途插话纠偏 |
| 8 | `getFollowUpMessages` | 无 tool call 且无 steering 时 | agent 直接停，不会继续 |
| 9 | `toolExecution` | 策略字段，默认 `"parallel"` | 并行模式，见下 |
| 10 | `beforeToolCall` | 参数验证后、执行前，可返回 `{block:true}` | 无拦截能力 |
| 11 | `afterToolCall` | 执行后、发事件前，可**逐字段替换**结果 | 工具返回什么就是什么 |

**三处容易错过的细节：**

**（7 与 8 的顺序是刚性的。** steering 优先于 follow-up：先看有没有"打断式插话"，没有才看"等你说完再说的话"。这对应 TUI 里的 Enter（steering）与 Alt+Enter（follow-up）。

**（9 的并行语义在 L311–313 写全了**，不用等 Day 4 去代码里猜：

```
preflight tool calls sequentially           ← 先串行预检
→ execute allowed tools concurrently        ← 再并发执行
→ emit tool_execution_end in completion order   ← 事件按完成序
→ emit tool-result message artifacts in assistant source order  ← 消息产物按源序
```

**两个消费者看到两种顺序**：UI 要实时（谁先完成谁先显示），模型要确定性（结果顺序必须与调用顺序一致，否则缓存失效、模型困惑）。

**（11 不做深合并**（L338 原文：`Any omitted fields keep their original values. No deep merge is performed.`）。传 `{isError: true}` 只改标志，`content` 保持原样；但要换 `content` 就得给完整数组，不能给片段。这个选择避免了合并语义的歧义，代价是调用方必须拿全量。

---

### 七、与 `agent/core/agent_loop.py` 的对照

你的 loop 在这一层是**空的**，证据在 `_call_model`（L163–275）：

```python
for event in events:            # L192 消费 provider 流
    ...
    console.print("[bold magenta]🤖 助手:[/bold magenta]")   # L199-200 直接渲染
```

**渲染逻辑硬编码在模型调用函数内部**，没有中间层。后果直接可推：

| 需求 | 你现在要改哪 | Pi 要改哪 |
|---|---|---|
| 加 telemetry 埋点 | 改 `_call_model` | 注册一个监听器 |
| 加 Web UI | 重写 `_call_model` | 注册一个监听器 |
| 断言事件顺序（测试） | 无法做 | `subscribe` 收集后断言 |
| 进度条 / 状态栏 | 改 `_call_model` | 监听 `tool_execution_*` |

另外两处差异：

- `max_rounds: int = 30`（L67）是硬上限；Pi 无上限，靠 `terminate` 悲观聚合收敛
- 无 `tools` 变更通知机制；接 MCP 动态注册后必须补

**Day 7–9 的改造目标由此明确**：把 `_call_model` 里那几条 `console.print` 抽成事件发射，`ui/cli.py` 改成订阅方。

---

## Day 2 自测（不看代码作答）

1. 十类事件里有三类不改变任何状态，是哪三类？判断某事件"是否改变状态"的标准是什么？
2. `message_start` 如何保证恰好发一次？为什么要靠循环内部的标志，而不是约定 provider 必须发 `start`？
3. 20 多处 `emit` 里只有一处不 `await`，是哪一处、为什么？其余 `await` 会带来什么性能风险？
4. `afterToolCall` 返回 `{content: [...]}` 会发生什么？只返回 `{isError: true}` 又会发生什么？
5. `AgentState.tools` 变更后，模型是怎么知道的？如果不做这件事会出什么问题？
6. `execute` 的契约明确禁止"只在 content 里描述失败"，理由是什么？
