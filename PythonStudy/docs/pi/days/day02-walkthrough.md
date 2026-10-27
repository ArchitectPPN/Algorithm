# Day 02 解读 · `types.ts` L147–274

> **读法**：左边源码，右边本文。先看原文自己猜，再看 PHP 对照验证。
> **今天两段**：① L147–192 收尾（昨天剩的）② L193–274 `AgentLoopConfig` 前半
> **源码**：`D:\AiAgent\pi\packages\agent\src\types.ts`

---

# 第一部分 · L147–192 收尾

## 11. L147：`AgentTurnDecision`——只有两个值的判别联合

```ts
export type AgentTurnDecision = { action: "continue" } | { action: "end" };
```

昨天已讲过判别联合，这里补一层：**它只有两个成员，而且没有 `{action: "something-else"}`**。

**PHP 对照**：

```php
// 你会怎么写
function finishTurn(array $turn): bool { return true; } // true=继续 false=结束
```

`bool` 也能表达二选一，但**读代码的人不知道 `true` 代表"继续"还是"结束"**——只能去翻文档。TS 这里把语义写进了字面量里，`{action: "end"}` 自身可读。

这就是"命名布尔量"问题的类型级解法。你在 PHP 里做过同样的事：`const STATUS_ACTIVE = 1`，本质相同，只是 TS 用类型系统强制了。

---

## 12. L149–158：`FinishTurn`——注释比签名重要

```ts
/**
 * Called after a completed assistant turn and all of its tool-result messages, but before `turn_end`.
 * On a normal turn, `{ action: "continue" }` ensures one next provider request. Tool-result, steering, or
 * follow-up scheduling can satisfy that request and adds no extra request; otherwise the loop continues once
 * with the current context. Error and aborted responses remain hard exits.
 */
export type FinishTurn = (
	turn: AgentTurnContext,
	signal?: AbortSignal,
) => AgentTurnDecision | void | Promise<AgentTurnDecision | undefined> | Promise<void>;
```

签名只有四行，注释有五行。**这五行是整段最值得读的部分**，拆开是三件事：

**（1）调用时机**：assistant 消息和它全部的 tool-result 消息都发完之后，**`turn_end` 之前**。

顺序要记牢：

```
assistant 消息 → 全部 tool_result → 【finishTurn 在这里】 → turn_end
```

**（2）`continue` 不是"多发一次"，是"保证至少一次"**。原文这句很难读，但意思很精确：

> `{ action: "continue" }` ensures one next provider request. Tool-result, steering, or follow-up scheduling **can satisfy that request and adds no extra request**

意思是：循环接下来本来就要再发一次请求（因为刚有工具结果要回给模型），这个"下一次"就已经满足 `continue` 的要求了，**不会因为你说 continue 就额外多跑一轮**。

反过来理解：`continue` 的作用是"**不许在此停下**"。当循环本来就想停（没有工具调用、没有 steering、没有 follow-up）时，`continue` 把它拽回来再跑一次。

**（3）`error` 和 `aborted` 是硬出口，不受钩子影响**。

> Error and aborted responses remain hard exits.

即使你的 `finishTurn` 返回 `continue`，模型报错或用户中止时循环照样结束。**安全阀优先于扩展点**——这条原则在你自己的框架里也该有。

**PHP 对照**：

```php
// 最接近的体感：Laravel 的 middleware 返回 null 表示"放行"
$next = function ($turn) {
    // 返回 null  => 按默认调度走（等价于 TS 的 undefined）
    // 返回 Decision => 显式表态
};
```

**为什么允许返回 `void`**：绝大多数钩子只想旁听，不想干预。让"没意见"成为最省事的写法，钩子作者才不会为了凑返回值而写 `{action:"continue"}`——一旦所有人都在写 continue，你就再也分不清谁是"真要继续"、谁是"懒得管"。

---

## 13. L160–170：`AgentLoopTurnUpdate`——下一轮能改的四样东西

```ts
/** Replacement runtime state used by the agent loop before starting another provider request. */
export interface AgentLoopTurnUpdate {
	context?: AgentContext;        // 下一轮的上下文
	messages?: AgentMessage[];     // 下一轮之前要追加的消息
	model?: Model<any>;            // 下一轮换模型
	thinkingLevel?: ThinkingLevel; // 下一轮的思考档位
}
```

四个字段都可选，**全不填等于什么都不改**。注意它是"**替换**（Replacement）"不是"合并"：

> Replacement runtime state

给了 `context` 就是整个换掉，不是往旧的里塞。这一点和 `afterToolCall` 的"不做深合并"是同一套设计取向：**要么别给，要么给全**。

**PHP 对照**：

```php
class AgentLoopTurnUpdate {
    public function __construct(
        public ?AgentContext $context = null,
        public ?array $messages = null,
        public ?Model $model = null,
        public ?string $thinkingLevel = null,
    ) {}
}
```

一模一样。唯一要注意的是 TS 的 `?` 是"字段可不存在"，PHP 的 `?Type = null` 是"字段存在但值为 null"——**在"区分有没有给"这件事上，TS 的可选字段语义更准**（`"model" in obj` 能判断，PHP 里 `array_key_exists` 和 `$x === null` 要分开写）。

**业务含义**：这四样正是"下一轮和这一轮可以不同"的全部。模型可以逐轮降级（简单轮用便宜模型），思考档位可以逐轮调整（工具轮 off、推理轮 high），上下文可以整块替换（压缩后换新）。

---

## 14. L179–189：`Omit<>`——用类型表达生命周期

```ts
/** Replacement runtime state for the provider request being prepared. */
export type AgentRequestUpdate = Omit<AgentLoopTurnUpdate, "messages">;

/**
 * Called immediately before every conversational provider request, including the first.
 * Pending messages have already been appended and emitted when this callback runs.
 */
export type PrepareRequest = (
	request: PrepareRequestContext,
	signal?: AbortSignal,
) => AgentRequestUpdate | void | Promise<AgentRequestUpdate | undefined> | Promise<void>;
```

昨天已讲过 `Omit` 的语法，这里补的是**为什么**：

`PrepareRequest` 跑在"消息已经追加并发出之后"（L184 注释原文：*Pending messages have already been appended and emitted*）。此刻你再返回 `messages` 也没人处理了。

**类型系统直接把这条路封掉**——不是文档里写一句"别返回 messages"，而是编译期报错。

**PHP 对照**：做不到。PHP 里你只能：

```php
// 方案 A：一个类，文档写"messages 字段无效"（靠自觉）
// 方案 B：拆成两个类（重复定义）
```

TS 的 `Omit` 是"从已有类型派生新类型"，一处改动两处同步。**这是"用类型表达业务规则"最典型的场景**，比任何注释都硬。

---

## 15. L191：`PrepareNextTurnContext`——空继承的意义

```ts
export interface PrepareNextTurnContext extends AgentTurnContext {}
```

现在它和 `AgentTurnContext` 完全一样。留着它是为了**将来能单独演化**——加字段只改这一行，用它的地方自动跟上。

**命名即文档**："turn 结束时的上下文"和"准备下一轮时的上下文"今天是同一个东西，但**它们是两个概念**，值得两个名字。

---

## 16. ⚠️ 本节最重要的区分：`prepareRequest` vs `prepareNextTurn`

名字很像，实际差别很大。这两个是**最容易混的一对**，而混了就写错钩子。

| | `prepareRequest`（L186） | `prepareNextTurn`（L278） |
|---|---|---|
| **调用时机** | **每次**请求 provider 前，**含第一次** | `turn_end` **之后**、且循环**将要继续**时 |
| **参数** | `PrepareRequestContext`（context/model/thinkingLevel） | `PrepareNextTurnContext`（= `AgentTurnContext`，含 turn 消息与工具结果） |
| **能返回什么** | `AgentRequestUpdate`：context / model / thinkingLevel | `AgentLoopTurnUpdate`：**四样全给，含 messages** |
| **返回值作用域** | **本次及之后所有请求**（"this and later requests", L269） | **只影响下一轮**（"affect that turn", L275） |
| **能否追加消息** | ❌ 类型禁止（已追加完毕） | ✅ `messages[]` |
| **是否轮询队列** | 不轮询（L269 明说 *This hook does not poll queues*） | 不轮询（由循环自己轮询 steering/follow-up） |

一句话记：**`prepareRequest` 改的是"运行时的常驻设置"，`prepareNextTurn` 改的是"下一轮的一次性补丁"。**

**为什么需要两个**：一个是"改状态"，一个是"递参数"。如果合成一个，你就得分清"这次改的模型是只影响这一轮，还是往后都算"——那是靠返回值猜，不是靠类型表达。

**PHP 对照**：`prepareRequest` ≈ Guzzle 的 `on_stats` / 请求中间件（每次请求都过一遍）；`prepareNextTurn` ≈ 循环体里的"下一轮参数"回调（只跑一次）。

---

# 第二部分 · L193–274 `AgentLoopConfig` 前半

## 17. 这个接口是什么

```ts
export interface AgentLoopConfig extends SimpleStreamOptions {
	model: Model<any>;
	...
}
```

`AgentLoopConfig` 是**你和循环之间的全部约定**：循环需要什么、你愿意提供什么、哪些点可以插手。

它 `extends SimpleStreamOptions`——直接继承了流式调用的基础选项（温度、maxTokens 之类），所以配置里混着"模型参数"和"回调"两种东西。

**今天只读前 5 个回调，且全是"必答/可选"的分级**。

---

## 18. L194：`model`——唯一的必填字段

```ts
model: Model<any>;
```

没有 `?`。**整个接口里只有它和 `convertToLlm` 是必填的**——这两样恰好是"不知道该调谁"和"不知道该说什么"的底线。

`Model<any>` 的 `<any>` 是泛型参数，指 provider 特有的选项类型。你不需要深究，只知道"模型对象自带供应商信息"即可——后面 `getApiKey` 用 `model.provider` 取值就是这个道理。

---

## 19. L196–222：`convertToLlm`——接缝里最关键的一个

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

**（1）它是唯一的降维点。** `AgentMessage[]`（会话记录，可以有任意形态）→ `Message[]`（只有四种角色）。这就是"投影"，昨天你问过的那个概念，**定义就在这里**。

**（2）必须不抛异常。**

> Throwing interrupts the low-level agent loop **without producing a normal event sequence**.

这句话很重：抛出去不是"报个错"，而是**事件序列断在半路**——`message_start` 发了、`message_end` 没发，订阅方（UI、session 落盘、telemetry）全部卡在不一致状态。

**宁可返回一个降级结果，也不能让序列断掉。** 这是所有"中间件式回调"的通用契约，Pi 在三个回调上重复写了同一句（`convertToLlm`、`transformContext`、`getApiKey`），说明它是系统级的约定而非局部要求。

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
    public function toArray($request): array|JsonResource {
        if ($this->role === 'notification') return [];  // 丢弃
        return ['role' => $this->role, 'content' => ...];
    }
}
```

你对这个概念不陌生——`array_filter` + `array_map` 串起来就是它。差别在于：**Pi 把这个动作固化成了一个必须存在的注入点**，而不是散落在调用方。

---

## 20. L224–244：`transformContext`——在投影之前动刀

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

**（1）顺序：先 transform，后 convert。** 注释第一句就是 *before* `convertToLlm`。为什么是这个顺序——因为剪枝要在**富信息**上做：`AgentMessage` 里有 `usage`、`timestamp`、`isError`，投影成 `Message` 之后这些都没了，想按 token 预算剪枝就没依据了。

**（2）它的返回类型有个不对称，值得注意**：

| | `convertToLlm` | `transformContext` |
|---|---|---|
| 是否必填 | **必填** | 可选 |
| 返回类型 | `Message[] \| Promise<Message[]>` | 只有 `Promise<AgentMessage[]>` |

**可选的那个，反而强制异步。** 这不是笔误，是设计判断：压缩要调模型生成摘要（Day 19 的 `compaction.ts` 就是干这个），天然是异步的。Pi 干脆不给同步写法，省掉"先同步剪枝、剪不动再异步压缩"的两套分支。

**（3）这就是你 `context_manager.py` 该挂的地方。**

你现在是在 `agent_loop.py` 的循环里直接调 `context_manager`——那是"框架硬编码了一个压缩策略"。Pi 的做法是**循环完全不知道有压缩这回事**，压缩是外部注入的一个 `transformContext`。差别在于：换策略不用改循环。

**（4）接线实证**（提前偷看一眼 Day 4 的代码，`agent-loop.ts` L397–410）：

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

## 21. L246–254：`getApiKey`——为什么每次请求都要问一遍

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

一个 agent 跑十分钟不奇怪，中间执行几十个工具。**如果 key 在启动时取一次，跑到第 8 分钟过期了，后面全部请求都失败**。所以 Pi 选择最笨也最稳的做法：每次请求前重新解析。

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

你写过的所有"token 可能过期"的对接，逻辑都是这一条。

---

## 22. L256–264：`finishTurn`——就是 L155 那个类型

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

---

## 23. L266–271：`prepareRequest`

```ts
/**
 * Called immediately before every conversational provider request, including the first.
 * Pending messages have already been appended. The returned context, model, and thinking level
 * replace the runtime values for this and later requests in the run. This hook does not poll queues.
 */
prepareRequest?: PrepareRequest;
```

三句话三个约束，逐个对应上一节的对比表：

1. *including the first* —— **第一次也调**。想在第一次请求就换模型、换上下文，只能靠它（`prepareNextTurn` 第一次来不及）
2. *replace the runtime values for **this and later requests*** —— 改的是常驻状态
3. *does not poll queues* —— 它**不管 steering/follow-up**，那是循环自己的事

**业务含义**：这是最实用的一个钩子。你 Day 44 做的三方案对照里，如果要"按代码内容动态选检索词"，这就是落点——在发请求前根据上下文改 prompt。

---

## 24. L273–280：`prepareNextTurn`

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

**注意它的返回值类型比 `prepareRequest` 窄**：只接受 `AgentLoopTurnUpdate | undefined | Promise<...>`，**没有 `void`**。相比之下 `FinishTurn` 和 `PrepareRequest` 都允许 `void`。

为什么——`void` 和 `undefined` 在"没意见"这个语义上重复了，这个字段只留一种写法。**同一个代码库里风格不完全统一是常态**，看到差异时先想"是不是两个作者两个时间写的"，而不是硬找统一解释。

---

## 25. 前半 5 个回调的默认行为表（今天核心产出）

| 回调 | 必填 | 不给时会发生什么 | 一句话用途 |
|---|---|---|---|
| `model` | **必填** | 编译不过 | 调哪个模型 |
| `convertToLlm` | **必填** | 编译不过 | 会话记录 → 四种 LLM 消息（投影） |
| `transformContext` | 可选 | **上下文原样送去投影**，无剪枝、无压缩 | 在富信息层剪枝/注入 |
| `getApiKey` | 可选 | 用静态 `config.apiKey` | 每次都取一遍，应对过期 |
| `finishTurn` | 可选 | 按默认调度：有工具结果就再发一次，没有就停 | 抢在 `turn_end` 前决定继续/结束 |
| `prepareRequest` | 可选 | 用当前 context/model/thinkingLevel 直接发 | 每次请求前的最后修改窗口 |
| `prepareNextTurn` | 可选 | 沿用当前 context/config | 轮间追加消息、改下一轮参数 |

**这张表就是"不给会怎样"的答案**——比记住每个回调怎么用更重要。因为绝大多数时候你不需要它们，需要知道的是"不给我会不会出问题"。

---

## 26. 今天这 128 行在说什么

一句话：**循环向你索要的五样东西，以及你在哪四个时刻可以插手。**

- 必答两样：调谁（`model`）、说什么（`convertToLlm`）
- 可插手四个时刻：投影前（`transformContext`）、请求前（`prepareRequest`）、轮末（`finishTurn`）、下一轮前（`prepareNextTurn`）
- 一个反复出现的契约：**三个回调都写了 "must not throw"**——中间件不许炸断事件流

**和你的框架对比**：你这四项全是硬编码在 `agent_loop.py` 里的 if/else。Pi 把它们变成注入点，代价是"读代码时要跳四处"，收益是"改策略不用碰循环"。

这就是 959 行 vs 338 行的真实差距——**不是功能多，是每个决策点都变成了可替换的接缝**。

---

## 自测（答完贴回来核对）

1. `finishTurn` 返回 `{action: "continue"}` 时，会不会**额外**多发一次模型请求？为什么？
2. `prepareRequest` 和 `prepareNextTurn` 都叫"下一轮之前"，差别在哪三处？（提示：时机、作用域、能不能带 messages）
3. `transformContext` 是可选的，为什么返回类型却**不给同步写法**，强制 `Promise`？
4. `convertToLlm` 的 "must not throw" 如果违反，后果是什么？为什么不是"报个错而已"？
5. `getApiKey` 的参数为什么是 `provider` 而不是 `model`？它取不到值时循环怎么办？
