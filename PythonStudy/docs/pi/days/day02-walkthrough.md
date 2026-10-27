# Day 02 解读 · `types.ts` L193–280（`AgentLoopConfig` 前半）

> **读法**：左边源码，右边本文。先看原文自己猜，再看 PHP 对照验证。
> **源码**：`D:\AiAgent\pi\packages\agent\src\types.ts`
> **前置**：L147–192 的收尾解读在 [`day01-walkthrough.md`](day01-walkthrough.md) 的「补充深读」一节（`FinishTurn` 三态、`AgentLoopTurnUpdate` 的 Replacement 语义、`Omit` 封堵晚路、两个 prepare 钩子的六维对照）。

---

## 1. 这个接口是什么

```ts
export interface AgentLoopConfig extends SimpleStreamOptions {
	model: Model<any>;
	...
}
```

`AgentLoopConfig` 是**你和循环之间的全部约定**：循环需要什么、你愿意提供什么、哪些点可以插手。

它 `extends SimpleStreamOptions`——直接继承了流式调用的基础选项（温度、maxTokens 之类），所以配置里混着"模型参数"和"回调"两种东西。

**今天只读前 5 个回调 + 1 个必填字段，重点在"必答/可选"的分级。**

---

## 2. L194：`model`——唯一的必填字段

```ts
model: Model<any>;
```

没有 `?`。**整个接口里只有它和 `convertToLlm` 是必填的**——这两样恰好是"不知道该调谁"和"不知道该说什么"的底线。

`Model<any>` 的 `<any>` 是泛型参数，指 provider 特有的选项类型。不需要深究，只要知道"模型对象自带供应商信息"即可——后面 `getApiKey` 用 `model.provider` 取值就是这个道理。

---

## 3. L196–222：`convertToLlm`——接缝里最关键的一个

```ts
/**
 * Converts AgentMessage[] to LLM-compatible Message[] before each LLM call.
 *
 * Each AgentMessage must be converted to a SystemMessage, UserMessage, AssistantMessage, or ToolResultMessage
 * that the LLM can understand. AgentMessages that cannot be converted (e.g., UI-only notifications,
 * status messages) should be filtered out.
 *
 * Contract: must not throw or reject. Return a safe fallback value instead.
 * Throwing interrupts the low-level agent loop without producing a normal event sequence.
 */
convertToLlm: (messages: AgentMessage[]) => Message[] | Promise<Message[]>;
```

三个要点：

**（1）它是唯一的降维点。** `AgentMessage[]`（会话记录，可以有任意形态）→ `Message[]`（只有四种角色）。这就是"投影"——**定义就在这里**。

**（2）必须不抛异常。**

> Throwing interrupts the low-level agent loop **without producing a normal event sequence**.

这句话很重：抛出去不是"报个错"，而是**事件序列断在半路**——`message_start` 发了、`message_end` 没发，订阅方（UI、session 落盘、telemetry）全部卡在不一致状态。

**宁可返回一个降级结果，也不能让序列断掉。** 这是所有"中间件式回调"的通用契约，Pi 在三个回调上重复写了同一句（`convertToLlm`、`transformContext`、`getApiKey`），说明它是系统级约定而非局部要求。

**（3）注释里的示例揭示了一件事**：`convertToLlm` 用 `flatMap` 而不是 `map`：

```ts
convertToLlm: (messages) => messages.flatMap(m => {
  if (m.role === "custom") return [{ role: "user", ... }];  // 1 → 1
  if (m.role === "notification") return [];                  // 1 → 0
  return [m];                                                // 1 → 1（透传）
})
```

`flatMap` 让"一条变零条"和"一条变多条"在语法上统一。**这是"投影不是转换"的代码依据**——转换通常 1:1，投影可以丢、可以拆。

**PHP 对照**：

```php
// Laravel 的 API Resource，本质就是投影
class MessageResource extends JsonResource {
    public function toArray($request): array {
        if ($this->role === 'notification') return [];  // 丢弃
        return ['role' => $this->role, 'content' => ...];
    }
}
```

这个概念不陌生——`array_filter` + `array_map` 串起来就是它。差别在于：**Pi 把这个动作固化成了一个必须存在的注入点**，而不是散落在调用方。

---

## 4. L224–244：`transformContext`——在投影之前动刀

```ts
/**
 * Optional transform applied to the context before `convertToLlm`.
 *
 * Use this for operations that work at the AgentMessage level:
 * - Context window management (pruning old messages)
 * - Injecting context from external sources
 *
 * Contract: must not throw or reject. Return the original messages or another
 * safe fallback value instead.
 */
transformContext?: (messages: AgentMessage[], signal?: AbortSignal) => Promise<AgentMessage[]>;
```

**（1）顺序：先 transform，后 convert。** 注释第一句就是 *before* `convertToLlm`。为什么是这个顺序——因为剪枝要在**富信息**上做：`AgentMessage` 里有 `usage`、`timestamp`、`isError`，投影成 `Message` 之后这些都没了，想按 token 预算剪枝就没依据。

**（2）它的返回类型有个不对称，值得注意**：

| | `convertToLlm` | `transformContext` |
|---|---|---|
| 是否必填 | **必填** | 可选 |
| 返回类型 | `Message[] \| Promise<Message[]>` | 只有 `Promise<AgentMessage[]>` |

**可选的那个，反而强制异步。** 这不是笔误，是设计判断：压缩要调模型生成摘要（Day 19 的 `compaction.ts` 就是干这个），天然是异步的。Pi 干脆不给同步写法，省掉"先同步剪枝、剪不动再异步压缩"的两套分支。

**（3）这就是 `context_manager.py` 该挂的地方。**

现在是在 `agent_loop.py` 的循环里直接调 `context_manager`——那是"框架硬编码了一个压缩策略"。Pi 的做法是**循环完全不知道有压缩这回事**，压缩是外部注入的一个 `transformContext`。差别在于：换策略不用改循环。

**（4）接线实证**（提前偷看 Day 4 的代码，`agent-loop.ts` L397–410）：

```ts
// Apply context transform if configured (AgentMessage[] → AgentMessage[])
let messages = context.messages;
if (config.transformContext) {
    messages = await config.transformContext(messages, signal);
}

// Convert to LLM-compatible messages (AgentMessage[] → Message[])
const llmMessages = await config.convertToLlm(messages);

const llmContext = normalizeContext({ messages: llmMessages });

// Resolve API key (important for expiring tokens)
const resolvedApiKey =
    (config.getApiKey ? await config.getApiKey(config.model.provider) : undefined) || config.apiKey;
```

四个动作的顺序一目了然：**transformContext → convertToLlm → normalizeContext → getApiKey**。这段只有 14 行，是整个"请求装配"的全部——没有藏在别处。

---

## 5. L246–254：`getApiKey`——为什么每次请求都要问一遍

```ts
/**
 * Resolves an API key dynamically for each LLM call.
 *
 * Useful for short-lived OAuth tokens (e.g., GitHub Copilot) that may expire
 * during long-running tool execution phases.
 *
 * Contract: must not throw or reject. Return undefined when no key is available.
 */
getApiKey?: (provider: string) => Promise<string | undefined> | string | undefined;
```

**关键在注释第二段**：*may expire during long-running tool execution phases*。

一个 agent 跑十分钟不奇怪，中间执行几十个工具。**如果 key 在启动时取一次，跑到第 8 分钟过期了，后面全部请求都失败。** 所以 Pi 选择最笨也最稳的做法：每次请求前重新解析。

**参数是 `provider: string` 而不是 `model`**——因为 key 属于供应商（openai / anthropic），不属于具体模型。这个细节说明接口作者想过"谁能决定 key"。

**取不到时怎么办**（L410 的写法）：

```ts
(config.getApiKey ? await config.getApiKey(config.model.provider) : undefined) || config.apiKey;
```

`||` 兜底到静态 `config.apiKey`。**动态失败退回静态**，而不是直接抛错。

**PHP 对照**：

```php
// OAuth refresh token 的标准做法
$token = $this->tokenProvider->get($provider) ?: $this->config->staticKey;
```

写过的所有"token 可能过期"的对接，逻辑都是这一条。

---

## 6. L256–264：`finishTurn`——就是 L155 那个类型

```ts
/**
 * Called after the assistant message and all tool-result messages have been emitted, immediately before `turn_end`.
 * `{ action: "end" }` ends the run without polling queues or preparing another request.
 * ...
 */
finishTurn?: FinishTurn;
```

注意它只是**引用**了 L155 定义的类型，自己没重写签名。这是"类型别名复用"的常规做法：文档注释写在配置字段上（因为读者是从这里进），签名定义在类型别名里（因为别处也要用）。

注释比 L149 那版多了两句信息：

- `{action:"end"}` 结束运行时**不轮询队列、不准备下一次请求**——是"真停"，不是"暂停一下看有没有新消息"
- `Returning undefined preserves normal scheduling`——**undefined 等于没意见**，走默认调度

（`continue` 的准确语义见 day01-walkthrough 补充 1：它是"保证至少一次"，不是"多发一次"。）

---

## 7. L266–271：`prepareRequest`

```ts
/**
 * Called immediately before every conversational provider request, including the first.
 * Pending messages have already been appended. The returned context, model, and thinking level
 * replace the runtime values for this and later requests in the run. This hook does not poll queues.
 */
prepareRequest?: PrepareRequest;
```

三句话三个约束：

1. *including the first* —— **第一次也调**。想在第一次请求就换模型、换上下文，只能靠它（`prepareNextTurn` 第一次来不及）
2. *replace the runtime values for **this and later requests*** —— 改的是**常驻状态**
3. *does not poll queues* —— 它**不管 steering/follow-up**，那是循环自己的事

**业务含义**：这是最实用的一个钩子。Day 44 三方案对照里如果要"按代码内容动态选检索词"，这就是落点——在发请求前根据上下文改 prompt。

---

## 8. L273–280：`prepareNextTurn`

```ts
/**
 * Called after `turn_end` when the loop will continue, immediately before the next turn starts.
 * Return replacement context/model/thinking state or messages to append to affect that turn.
 * Return undefined to keep using the current context/config.
 */
prepareNextTurn?: (
	context: PrepareNextTurnContext,
) => AgentLoopTurnUpdate | undefined | Promise<AgentLoopTurnUpdate | undefined>;
```

**它多一个能力：`messages`**。这是唯一能在轮与轮之间**往对话里追加消息**的钩子。

典型用途：把上一轮的工具执行结果整理成一条"阶段性总结"塞进去；或者注入一条"你已连续调用 grep 五次，考虑换个思路"的提示。

**它的返回值类型比 `prepareRequest` 窄**：只接受 `AgentLoopTurnUpdate | undefined | Promise<...>`，**没有 `void`**。`void` 和 `undefined` 在"没意见"这个语义上重复了，这里只留一种写法。

---

## 9. 前半 7 项的默认行为表（Day 02 核心产出）

| 回调 | 必填 | 不给时会发生什么 | 一句话用途 |
|---|---|---|---|
| `model` | **必填** | 编译不过 | 调哪个模型 |
| `convertToLlm` | **必填** | 编译不过 | 会话记录 → 四种 LLM 消息（投影） |
| `transformContext` | 可选 | **上下文原样送去投影**，无剪枝、无压缩 | 在富信息层剪枝/注入 |
| `getApiKey` | 可选 | 用静态 `config.apiKey` | 每次都取一遍，应对过期 |
| `finishTurn` | 可选 | 按默认调度：有工具结果就再发一次，没有就停 | 抢在 `turn_end` 前决定继续/结束 |
| `prepareRequest` | 可选 | 用当前 context/model/thinkingLevel 直接发 | 每次请求前的最后修改窗口 |
| `prepareNextTurn` | 可选 | 沿用当前 context/config | 轮间追加消息、改下一轮参数 |

**这张表就是"不给会怎样"的答案**——比记住每个回调怎么用更重要。因为绝大多数时候不需要它们，需要知道的是"不给我会不会出问题"。

---

## 10. 这 88 行在说什么

一句话：**循环向你索要的两样底线，以及你在三个时刻可以插手。**

- 必答两样：调谁（`model`）、说什么（`convertToLlm`）
- 可插手三个时刻：投影前（`transformContext`）、请求前（`prepareRequest`）、下一轮前（`prepareNextTurn`）
- 一个反复出现的契约：**三个回调都写了 "must not throw"**——中间件不许炸断事件流

**和现有框架对比**：这四项全是硬编码在 `agent_loop.py` 里的 if/else。Pi 把它们变成注入点，代价是"读代码时要跳四处"，收益是"改策略不用碰循环"。

这就是 959 行 vs 338 行的真实差距——**不是功能多，是每个决策点都变成了可替换的接缝**。

---

## 自测（答完贴回来核对）

1. 整个 `AgentLoopConfig` 里只有两个必填字段，除了 `model` 还有谁？为什么偏偏是这两个？
2. `transformContext` 是可选的，为什么返回类型却**不给同步写法**，强制 `Promise`？
3. 为什么 `transformContext` 必须在 `convertToLlm` **之前**，反过来行不行？
4. `convertToLlm` 的 "must not throw" 如果违反，后果是什么？为什么不是"报个错而已"？
5. `getApiKey` 的参数为什么是 `provider` 而不是 `model`？它取不到值时循环怎么办？
