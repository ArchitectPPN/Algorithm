# Day 01 解读：types.ts L1–192（PHP 视角）

> **用法**：左边开 `D:\AiAgent\pi\packages\agent\src\types.ts`，右边开本文。先看原文，再看 PHP 对照，最后看"这段在讲什么"。
> 读完再回头答 `day01.md` 里的过关问题——答得上就过，答不上回来重读对应段。

---

## 0. L1–17：imports——先看依赖谁

```ts
import type {
	Api, AssistantMessage, ..., Tool, ToolResultMessage, Usage,
} from "@earendil-works/pi-ai";
import type { Static, TSchema } from "typebox";
```

**PHP 对照**：`use EarendilWorks\PiAi\AssistantMessage;`——但有关键区别：
- `import type` 导入的**只是类型**，编译后完全消失，运行时零开销。相当于"只给 IDE/静态分析用的 use"。
- 供应商相关的类型（`AssistantMessage`、`Tool` 等）全部来自外部包 `pi-ai`——**核心包不自己定义 LLM 消息结构**，依赖一个专门的 AI 协议包。你自己的 `rag_chain.py` 里 RAGAnswer 是自己定义的 dataclass；Pi 把"协议类型"外包了。
- `typebox` 是 JSON Schema 库——工具参数的 schema（你 Week 前裸写 function calling 时手写 JSON Schema，就是它要替代的东西）。

### 💬 元日讨论沉淀（2026-10-25）

**图纸 vs 零件**——学习者最初总结"每层都是独立可运行的零件"，修正一个字：types.ts 恰恰是**不可运行的零件**。

| 类别 | 是什么 | 例子 |
|------|--------|------|
| **图纸**（不可运行） | 接口标准、数据契约——编译后消失，运行时不存在 | types.ts 全部类型、`import type` 进来的 pi-ai 协议 |
| **零件**（可运行） | 真正干活的代码 | agent-loop.ts、供应商实现、工具 |

组装的本质：**零件之间靠图纸对接**，图纸先于零件存在、独立演化——"换供应商零件、循环体不改"才可能。乐高类比：**凸点间距标准不是积木，但它让任何厂商的积木能拼一起**。验证方法：编译 Pi 后产物目录里找不到 types.ts——`import type` 的东西编译即消失，图纸不上机器。

**清单的边界**：types.ts 不是"全项目用到的类型清单"，是**"接缝的形状"清单**（消息类型在 pi-ai 包里、循环内部的局部函数类型也不在）。读每个类型问一句"**这个形状是给谁的**"：

| 类型 | 提问者（谁需要知道这个形状） |
|------|---------------------------|
| `StreamFn` | 供应商实现者（"你要提供一个长这样的函数"） |
| `BeforeToolCallResult` | 钩子作者（"你的拦截钩子可以返回这个"） |
| `AgentToolCall` | 工具作者 / 循环内部（"模型吐出来的工具调用长这样"） |
| `AgentTurnDecision` | `finishTurn` 钩子作者（"你有 continue/end 两种选择"） |

---

## 1. L33–37：`StreamFn`——供应商调用被抽象成一个函数

```ts
export type StreamFn = (
	model: Model<Api>,
	context: TranscriptContext,
	options?: SimpleStreamOptions,
) => AssistantMessageEventStream | Promise<AssistantMessageEventStream>;
```

**PHP 对照**：

```php
// PHP 7.4+ 的 callable 类型签名写法
/**
 * @param Model              $model
 * @param TranscriptContext  $context
 * @param SimpleStreamOptions|null $options
 * @return AssistantMessageEventStream|Promise<AssistantMessageEventStream>
 */
$streamFn = function (Model $model, TranscriptContext $context, ?SimpleStreamOptions $options) { ... };
```

**这段在讲什么**：整个 agent loop 调 LLM 的方式，被压缩成**一个函数指针**。循环体不 `new OpenAI()`、不 `new Anthropic()`——它只拿到这个函数，调用它，消费返回的流。想换供应商？传另一个实现 `StreamFn` 的函数进来，循环体一行不改。

这就是过关问题"换供应商要改几处代码"的答案方向：**注入处换一个函数，循环体零改动**——和你在 `myagent/llm_adapter.py` 里看到的 `create_adapter()` 工厂是同一个意图，但 Pi 用"纯函数注入"而不是"类继承"。

### 💬 元日讨论沉淀

学习者初答"改一处"，精化为：**新增一处 + 装配点改一行**。原实现不动（L20-21 注释：官方默认实现是 `Models.streamSimple`），接新供应商时**写一个新函数**、在装配点传入；原实现保留，多个 agent 可各用各的供应商。这是**开闭原则的函数式版本**（对扩展开放、对修改关闭）——对比 `llm_adapter.py` 的 `create_adapter()` 工厂：工厂加供应商要加 case/加类，这里只传一个新函数。

且前提有**编译期强制力**：新函数与 `StreamFn` 签名不符 → TS 编译器直接报错，跑都跑不起来。**契约不是注释，是编译期关卡**——PHP 靠 docblock + 静态分析工具（还不一定开了），或运行时炸了才发现。装配点长什么样 → Day 6 `agent.ts` 的 `createLoopConfig`（L476-516）。

**两个 TS 语法点**：
- `type X = (...) => Y` 是**函数类型**。PHP 没有原生等价物（只能靠 docblock 注解 callable），TS 里这是一等公民。
- `X | Promise<X>` 是**联合类型**——"返回流，或返回一个将来会给流的承诺"。PHP 是同步世界没有这个概念；最接近的类比是 Guzzle 的 `Response | Promise<Response>`：调用方可以选择 `wait()` 或 `.then()`。

**Contract 注释（L27-31）值得读两遍**——它规定了失败的处理方式：这个函数**不许抛异常**，失败必须编码进返回的流里（最后的 `AssistantMessage` 带 `stopReason: "error"`）。这是"错误值编码"风格：PHP 习惯 `throw new LlmException()`，这里要求"把失败当成一种正常的返回值"。为什么？因为流式场景里异常中断会撕坏事件流，编码成事件才能让下游统一处理。

---

## 2. L47 / L55：字符串字面量联合——TS 版的 enum

```ts
export type ToolExecutionMode = "sequential" | "parallel";
export type QueueMode = "all" | "one-at-a-time";
```

**PHP 对照**（8.1+，你应该用过）：

```php
enum ToolExecutionMode: string
{
    case Sequential = 'sequential';
    case Parallel   = 'parallel';
}
```

**完全等价**。`"sequential" | "parallel"` 的意思是：这个变量的值**只能是这两个字符串之一**，别的任何值编译器直接报错。PHP 的 backed enum 干的是同一件事。

你实际见过它们起作用：Day 44 你裸写的工具执行是"找到就执行"；后面 Day 4-5 会读到的 `executeToolCalls` 就是按 `ToolExecutionMode` 的值分流到串行/并行两条代码路径（`types.ts` 后面 L518+ 的 `executeToolCalls` 分发就消费这个值）。**今天记住"值是什么"，后天看"值怎么被消费"。**

---

## 3. L58：`Extract<>`——从联合类型里"抠"出一种

```ts
export type AgentToolCall = Extract<AssistantMessage["content"][number], { type: "toolCall" }>;
```

这行是 TS 独有的"类型层运算"，PHP 没有直接等价物。拆开读：

1. `AssistantMessage["content"]` —— 取 AssistantMessage 里 content 字段的类型（PHP 没有这种"取属性类型"的语法）
2. `[number]` —— content 是个数组，取"数组元素的类型"
3. `Extract<那个联合类型, { type: "toolCall" }>` —— 联合类型里可能有好几种块（text 块、image 块、toolCall 块），`Extract` **只保留 type 字段等于 "toolCall" 的那种**

**PHP 运行时类比**（类型层做不到，PHP 只能在运行时筛）：

```php
$toolCallBlocks = array_filter(
    $assistantMessage->content,
    fn ($block) => $block->type === 'toolCall'
);
```

区别在于：**PHP 的过滤发生在运行时**（拿到对象后筛），**TS 的 Extract 发生在类型层**（编译期就把"toolCall 块"算成一个独立类型，之后所有用到 `AgentToolCall` 的地方，IDE 都知道它有 `arguments`、`name` 这些字段，不用运行时判断）。

**过关问题"为什么 `AgentToolCall` 不单独定义"**的答案方向：因为"toolCall 块"的结构已经在 `AssistantMessage` 的 content 联合里定义过了，单独再写一份会出现两份手写类型，改一处漏一处。`Extract` 让它**从源类型推导**——源头改了，这里自动跟着变。

---

## 4. L66–74：`BeforeToolCallResult`——工具执行前的"否决权"

```ts
export interface BeforeToolCallResult {
	block?: boolean;
	reason?: string;
	terminate?: boolean;
}
```

**PHP 对照**：

```php
final class BeforeToolCallResult
{
    public function __construct(
        public readonly ?bool   $block = null,
        public readonly ?string $reason = null,
        public readonly ?bool   $terminate = null,
    ) {}
}
```

**三个语法点**：
- `interface` 在这里当 **DTO 定义**用（不是 PHP 里"类必须实现的方法集合"）。TS 的 interface 描述"这个形状的对象长什么样"——PHP 最接近的是 readonly 属性类，或 Psalm 的 `@psalm-shape`。
- **字段名后的 `?`** = 可选字段（可以不给）。PHP 对应 nullable + 默认 null。
- 没有方法体、没有逻辑——纯数据契约。

**这段在讲什么**：`beforeToolCall` 钩子（工具执行**前**的拦截点）可以返回这个对象行使否决权：
- `block: true` → 这次工具调用不执行了，loop 会生成一个错误 tool result 顶上（`reason` 就是错误文案）
- `terminate: true` → 不光否决这一次，还提示 agent 在这批工具跑完后**停下整个循环**

你认得这个设计——你裸写 agent 时 `execute_tool()` 里"工具不存在返回错误字符串给模型自纠错"是同一类机制，但 Pi 把"拦不拦"从硬编码变成了**可注入的钩子决策**。你在 `agent/permissions/engine.py` 见过的权限引擎，本质上就是一个 `beforeToolCall` 钩子的实现。

**过关问题"terminate 属于哪一层"**：注意它出现在**钩子的返回值**里，而不是消息/工具结果的字段里——它是"钩子给循环的信号"，不是"数据本身带的状态"。这个归属决定了谁能设置它（只有钩子），谁不能（工具自己不能在返回值里说"停"……但注意 L103 的 `AfterToolCallResult` 里也有 terminate——工具执行后的钩子也能设。记住：都是**钩子层**的信号）。

### 💬 元日讨论沉淀（本节是讨论最密的一段）

学习者两个误解与纠正：

**误解 1："block 阻止本次循环所有的命令"** → `block` 管的是**批内单个**。`beforeToolCall` 是**逐个工具调用触发**的（L111 字段 `toolCall: AgentToolCall` 是单数）——模型吐 3 个调用，钩子就被调 3 次，每次只针对其中一个。`block: true` 拦的就是"当前这一个"。

**误解 2："最后一个工具打 terminate 标签，一批有且仅有一个 terminate"** → terminate 是**逐个投票**：每个钩子裁决都可以返回它，批里可以有 0 个、1 个、多个、或全部。批末聚合规则是**全部 true 才停**。用学习者自己的模型推演会发现问题：如果"有且仅有一个"，批里永远凑不齐"全部 true"——**terminate 机制永远失效**。为什么用悲观聚合（全员同意制）：工具并行执行时互相不知情，如果任何一个单独的投票就能停，晚到的 true 会误杀整批——"停"这个重动作必须在无异议时才发生。

运行时序（模型吐出 T1/T2/T3 三个调用，钩子对不同调用返回不同值）：

| 时序 | 钩子返回 | 发生什么 |
|------|---------|---------|
| T1 执行前 | `{block: true, reason: "无权限"}` | **T1 不执行**，生成错误 tool result 占位 |
| T2 执行前 | `{}` | T2 正常执行 |
| T3 执行前 | `{terminate: true}` | **T3 照常执行**——terminate 不拦单个！ |
| 批末聚合 | — | 只有 1/3 投停，不是全部 → **不停，循环继续** |

**blocked ≠ 消失**：被 block 的调用仍会生成一条（错误的）tool result——function calling 协议要求 **N 个 tool_call 对应 N 条 tool_result，缺一不可**，否则下轮请求不合法。"拦截"的实现方式是"**错误结果占位**"，不是"跳过"。学习者裸写时"工具不存在返回错误字符串塞回 messages 让模型自纠错"是同一件事。

**三层术语**（防止"循环"一词混用）：**批（batch）**= 模型一条 assistant 消息吐出的全部 tool_call（1 个也算一批）；**轮（turn）**= 一批 + 结果回喂 + 后续处理；**循环（loop）**= 多轮直到终止。`block` 作用域：批内单个调用；`terminate` 作用域：批结束后的循环去留。

PHP 类比：中间件——路由管道里 middleware 对**单个请求**返回 403（请求打不到 controller），管道继续服务下一个请求。`block` = 单请求 403；`terminate` = "这轮服务完就打烊"的信号，且要求全员同意。

---

## 5. L92–104：`AfterToolCallResult`——执行后的"部分覆盖"

```ts
export interface AfterToolCallResult {
	content?: (TextContent | ImageContent)[];
	details?: unknown;
	structuredContent?: JsonValue;
	isError?: boolean;
	usage?: Usage;
	terminate?: boolean;
}
```

**PHP 对照**（语义上这是最 PHP 的一段）：

```php
// 字段级覆盖 = array_merge 的行为
$result = array_merge($executedResult, array_filter($override, fn ($v) => $v !== null));
```

**这段的注释（L79-90）比代码重要**，它规定了合并语义：
- 给了哪个字段就**整体替换**那个字段
- **没有给的字段保持原值**
- **"不做深合并"**——`content` 是个数组，你给了新数组就是整个换掉，不会和旧的逐元素合并。这正是 PHP `array_merge` 的行为（顶层键替换，不递归）。
- 最微妙的一条：`structuredContent`（给 UI 的结构化数据）如果单独给了新 `content` 而没带它，会被**丢弃**——因为内容都换了，旧的结构化说明可能对不上了。这种"字段间的一致性规则"在 PHP 里通常靠约定，这里写进了类型注释。

**`unknown` 类型**（L94）：TS 的"任何值，但用之前必须先检查"。比 `any` 严格——PHP 的 `mixed` 类型提示和它几乎一对一。

---

## 6. L107–146：三个 Context——钩子能拿到什么

三个 interface 全是**纯只读 DTO**，一个逻辑，分开记：

| 类型 | 给哪个钩子 | 关键字段 | 多出来的那个字段 |
|------|-----------|---------|----------------|
| `BeforeToolCallContext` | 工具执行**前** | `assistantMessage` / `toolCall` / `args` / `context` | —（还没执行，没有结果） |
| `AfterToolCallContext` | 执行后、覆盖前 | 同上 | **`result`**（执行结果）+ **`isError`** |
| `AgentTurnContext` | 一整轮结束后 | 同上 | **`toolResults`**（这轮所有工具结果）+ **`newMessages`** |

**PHP 对照**：就是三个 DTO 类，字段递增——before 拿"请求"，after 拿"请求+结果"，turn 拿"整轮总结"。你在 `agent/permissions/` 里写过 `ToolContext`，同一个思路。

**`args: unknown`**（L113）：注意类型是 unknown 而不是具体结构——参数在调用前会按工具声明的 schema 校验，但契约层面不假设形状（每个工具的参数都不同）。

**过关问题"想在工具执行前拦截，用哪个钩子"**：`beforeToolCall`，返回 `{ block: true, reason: "..." }`——拦截材料就是 `BeforeToolCallContext`。

---

## 7. L147：`AgentTurnDecision`——判别联合（TS 最有表现力的武器）

```ts
export type AgentTurnDecision = { action: "continue" } | { action: "end" };
```

**PHP 对照**——PHP 没有联合类型，惯用做法是**接口 + 两个最终类**模拟：

```php
interface AgentTurnDecision {}
final class ContinueDecision implements AgentTurnDecision {}
final class EndDecision implements AgentTurnDecision {}

// 消费端用 match（PHP 8.0）：
$next = match (true) {
    $decision instanceof EndDecision     => 'stop_loop',
    $decision instanceof ContinueDecision => 'keep_going',
};
```

**TS 的写法把这一切压进一行**：一个类型 = 两种对象形状的并集，区分靠 `action` 字段（判别字段）。消费端写 `if (decision.action === "end")` 时，编译器能自动收窄类型——这种"**tagged union / 判别联合**"是 TS 处理"有限几种情况"的标准武器，你会在 Pi 源码里见到几十次（`AgentEvent` 的 10 类事件、`stopReason` 的各种值……全部是这个模式）。

**认祖归宗**：它和你 Day 39 处理 LLM 输出的 `"action": "search" | "answer"` JSON、Day 44 function calling 的 `tool_calls` vs 纯文本分支，是同一个建模问题——**"结果是有限几种之一，每种带不同的数据"**。TS 用类型系统表达，Python/PHP 用运行时判断表达。

**过关问题"`finishTurn` 返回 `end` 会怎样"**：看 L146 注释——返回 undefined = 一切照常调度；返回 `{action: "end"}` = 一轮结束**提前终止整个 agent**（L295-298 的消费代码 Day 4 读）。也就是这个钩子有"踩刹车"的权力。

---

## 8. L155–158：`FinishTurn`——返回类型的"全家桶"

```ts
export type FinishTurn = (
	turn: AgentTurnContext,
	signal?: AbortSignal,
) => AgentTurnDecision | void | Promise<AgentTurnDecision | undefined> | Promise<void>;
```

返回类型四选一，拆开看其实就两个维度：

| | 同步 | 异步（Promise） |
|---|------|----------------|
| **有决定** | `AgentTurnDecision` | `Promise<AgentTurnDecision \| undefined>` |
| **没意见** | `void` | `Promise<void>` |

**PHP 对照**：没有原生对应（PHP 同步模型里回调返回值就完事了）。最接近的体感是 Guzzle 的 handler：可以同步返回 `Response`，也可以返回 `Promise<Response>`，调用方统一处理。

**为什么这么宽容**：这是给**钩子作者**的自由——你的回调可以是简单同步函数（`void`，只是旁听一下），也可以是异步的（要查数据库再决定 continue/end）。循环体消费时统一 `await`——**await 一个非 Promise 值在 TS 里完全合法**（原样通过），所以两种写法在调用点无差别。这就是上一段 `Promise<void> | void` 存在的原因：**接口对实现者宽容，对消费者统一**。

**`signal?: AbortSignal`**（第二个参数）：取消令牌——用户按了 Ctrl+C 或点了停止按钮，运行时会把这个 signal 置为 aborted，钩子内部可以检查 `signal.aborted` 提前收工。PHP 没有这个原生概念（最长见识的一段：TS 的异步生态里"可取消"是一等公民，每个长操作都带）。

---

## 9. L161–189：`AgentLoopTurnUpdate` / `PrepareRequest`——"下一轮之前，你还能改牌"

```ts
export interface AgentLoopTurnUpdate {
	context?: AgentContext;        // 下一轮的上下文
	messages?: AgentMessage[];     // 追加的消息
	model?: Model<any>;            // 下一轮换模型！
	thinkingLevel?: ThinkingLevel; // 下一轮的思考档位
}

export type PrepareRequest = (
	request: PrepareRequestContext,
	signal?: AbortSignal,
) => AgentRequestUpdate | void | Promise<AgentRequestUpdate | undefined> | Promise<void>;
```

**PHP 对照**：又一个 DTO + 回调签名，结构你已经熟了。值得停下的只有 **L180 的 `Omit<>`**：

```ts
export type AgentRequestUpdate = Omit<AgentLoopTurnUpdate, "messages">;
```

`Omit<T, "messages">` = "拿 AgentLoopTurnUpdate 的全部字段，**去掉 messages**"。类型层的"剔除运算"——PHP 做不到。为什么这里要去掉 messages？因为 `PrepareRequest` 跑在"消息已经追加完毕"之后（L184 注释），此刻再追加消息为时已晚——**类型系统直接禁止你返回一个会被忽略的字段**。用类型表达生命周期规则，这是 TS 工程化的典型手法。

**业务含义**：`PrepareRequest` 是每次调 LLM **前**的最后修改窗口——可以换模型（下一轮降级到便宜模型）、改上下文、调思考档位。你 Day 44 的三方案对照里"固定管道 B 的检索词写死"，如果用 Pi 的钩子体系，"按代码内容动态选检索词"就可以是 `PrepareRequest` 钩子里干的事。

---

## 10. L191：`PrepareNextTurnContext`——空继承

```ts
export interface PrepareNextTurnContext extends AgentTurnContext {}
```

**PHP 对照**：`interface PrepareNextTurnContext extends AgentTurnContext {}`——**一模一样**，PHP 你写过这种空接口（标记接口）。

它现在的意义是"**目前和 AgentTurnContext 完全相同，但给它留了独立演化的名字**"——以后要给它加字段，只动这一行，所有用它的地方不用改。你裸写代码时常常直接复用同一个类，类型名即文档：**"turn 结束的上下文"和"准备下一轮的上下文"今天是同一个东西，明天可能不是。**

---

## 全文串一遍：这 192 行在定义什么

一句话：**agent loop 和外部世界之间的全部接缝**。

- 循环怎么调 LLM？→ 一个 `StreamFn` 函数（L33）
- 工具串行还是并行？→ `ToolExecutionMode`（L47）
- 钩子们能拿到什么、能返回什么？→ 三个 Context + 两个 Result（L66–146）
- 谁能提前终止？→ `FinishTurn` 返回 `{action: "end"}`（L147）
- 每次请求前还能改什么？→ `PrepareRequest` 返回 `AgentRequestUpdate`（L180，注意 messages 被类型级禁掉）

你在 9 月裸写 ReAct loop 时，这些接缝是**散落在循环体里的 if/else**（工具不存在怎么处理、max_loops 到顶怎么办）；Pi 把它们**全部抽成了类型 + 注入点**——循环体（Day 4-5 读的 959 行）只是这些契约的消费者。这就是"裸写 100 行 vs 生产框架 959 行"的差距所在：不是功能多，是**每一个决策点都变成了可注入、可类型校验的接缝**。

---

## 💬 元日溢出讨论（超出 L1–192，Day 4–5 的预修结论）

讨论从钩子自然溢出到"**一批调用怎么执行、执行完怎么回模型**"——这些结论先立在这里，Day 4-5 读 L518-671 时是"验证已知"而不是"从零理解"。

### 1. 顺序性是全局配置，不是调用属性

tool_call 协议字段只有 `id` / `name` / `arguments`——**没有"我依赖上一个调用"的声明字段**。所以不存在"部分顺序、部分并行"的混合模式：`AgentLoopConfig.toolExecution` 全局二选一（sequential | parallel），一批调用要么全串行、要么全并行。判断"调用 B 是否依赖调用 A 的输出"需要语义理解，猜错的代价是数据错乱——**框架不猜，政策留给配置**。

### 2. 两种模式

- **串行**（`executeToolCallsSequential`，L540-595）：第 1 个 prepare → execute → finalize **全部完成**才开始第 2 个——裸写 for 循环的精装版
- **并行三段式**（`executeToolCallsParallel`，L596-671）：**prepare 串行**（钩子裁决、参数改写要有序可预测）→ **并发执行**（活下来的调用一起跑）→ **结果按源序组装**

### 3. 双顺序并存

`tool_execution_end` **事件**按完成顺序发（谁先跑完谁先发，UI 实时刷新）；tool-result **消息**按源序发（LLM 看到的结果顺序 = 它调用时的顺序，因果可读）。**事件的消费者是人要快，消息的消费者是模型要稳**——L43-45 注释把这个区分写得很清楚。

### 4. 批末一次性回模型（N 对 N 协议硬约束）

消息序列画面（模型吐 3 个调用）：

```
assistant(3 tool_calls) → result_1 → result_2 → result_3 → 【这时才发起下一次模型调用】
```

反例：完成一个就回 → `assistant(3 calls) → result_1` 就发请求 → call_2/call_3 成"孤儿" → **API 400**；即便协议宽容也是灾难——模型要求 3 件事只见 1 件答案，会重复调用或在信息不全时决策。

**"回复"的三种含义**（混用即糊涂）：

| 输出对象 | 时机 | 内容 |
|---------|------|------|
| 给**模型**的消息（协议层） | 批末一次性 | N 条 tool_result，按源序 |
| 给**前端**的事件（展示层） | 实时 | 每个工具开始/结束都发 |
| 给**用户**的最终答案（产品层） | 整个循环结束后 | 模型的自然语言回答 |

PHP 类比：批处理队列——**事务**全处理完才 commit（给模型的消息，原子性），**进度日志**一条条实时打（给前端的事件）。

### 5. 模型想要顺序怎么办

协议没有依赖声明 → 模型用**分轮**表达依赖：这轮吐第一个 → 拿结果 → 下轮吐第二个。ReAct 多轮循环天然承载顺序依赖；**并行只加速真正独立的批**。框架不验证独立性——并发安全的责任在**工具实现者**（幂等、无共享状态冲突，同 PHP 多进程 worker 的责任线）。

### 6. 双层循环的存在意义

```
外层 while(true)                     ← 管"轮"：发请求 → 收批 → 处理批 → 再发请求
  └─ 内层 while (批里还有活没干完)     ← 管"批"：逐个执行、攒结果、全齐了才出去
```

裸写版 for 循环 + `call_model()` 行为等价；Pi 显式分开两层，因为生产版还要在批的缝隙里处理 steering（用户插话）、错误中止这些杂事。

**一句话钉死：模型的每次输出是一张点菜单，agent 把整桌菜全做完、按上菜顺序摆好，才端回后厨——绝不端一半回去。**

---

## 自测（答完贴回来）

1. 换 LLM 供应商要改几处代码？为什么？
2. `beforeToolCall` 钩子想否决一次工具调用，返回什么？想连带让整批跑完就停，加什么字段？
3. `AgentTurnDecision` 这种"二选一"建模，PHP 里你怎么写？TS 里为什么能一行？
4. `PrepareRequest` 的返回类型为什么是 `Omit<AgentLoopTurnUpdate, "messages">` 而不是完整的 `AgentLoopTurnUpdate`？
