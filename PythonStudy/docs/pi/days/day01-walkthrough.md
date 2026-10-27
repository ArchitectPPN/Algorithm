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
- `terminate: true` → **仅当同时有 `block: true` 时才生效**（`agent-loop.ts:755-759` 只在 block 分支里读它），含义是"这批跑完后不必再回模型一轮"。单独返回 `{terminate:true}` 不给 block，是**空操作**——这是本文件最容易踩的一个坑

你认得这个设计——你裸写 agent 时 `execute_tool()` 里"工具不存在返回错误字符串给模型自纠错"是同一类机制，但 Pi 把"拦不拦"从硬编码变成了**可注入的钩子决策**。你在 `agent/permissions/engine.py` 见过的权限引擎，本质上就是一个 `beforeToolCall` 钩子的实现。

**过关问题"terminate 属于哪一层"**：它不是消息字段，而是**工具结果对象上的一个可选布尔**（`AgentToolResult.terminate`，`types.ts:445`）。一共有**三个设置点**，工具自己就是一个：

| 设置点 | 代码位置 | 语义 |
|--------|---------|------|
| 工具 `execute()` 的返回值 | `types.ts:445` | 工具自己声明"我这个结果是终局" |
| `beforeToolCall` 返回 | `agent-loop.ts:755-759` | **必须先 `block: true`**，terminate 才会被写进占位结果 |
| `afterToolCall` 返回 | `agent-loop.ts:902` | `afterResult.terminate ?? result.terminate`，可加也可撤（撤要显式给 `false`） |

所以它既不是"只有钩子能设"，也不是"数据本身带的状态"——准确说法是：**工具结果上的一个 hint 字段，执行链上三个位置都能写它**。

实测一条硬事实：`packages/coding-agent/src/core/tools/` 下的**八个**内置工具（`bash` / `edit` / `find` / `grep` / `ls` / `powershell` / `read` / `write`，按各文件 `name` 字段实测）**没有一个设置 terminate**，全仓库设成 `true` 的只有测试与 `examples/extensions/structured-output.ts`。这条事实本身就是"什么情况下该打 true"的最强判据，见下一节。

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

#### 什么情况下该打 `true`（判据与反例）

先看清 terminate **唯一的作用**：跳过"这批工具结果回给模型"的那一次自动请求。它不终止循环、不拦截工具、不改消息——`agent-loop.ts:281` 只有一行 `hasMoreToolCalls = !executedToolBatch.terminate`。

所以判据就是一句话：**这次调用的结果本身就是最终产物，模型再回一轮也产生不了新信息。**

#### 四个例子走一遍：同一个字段，四种命运

先破一个误解：terminate **不是工具的属性**，是**那一次调用结果对象上的字段**。同一个工具这次给 `true`、下次给 `false` 完全合法；它也完全不关心自己是批里的第几个。字段的生命周期只有五步：

| 步骤 | 谁写/谁读 | 位置 |
|---|---|---|
| ① 模型吐 `tool_call` | — | — |
| ② `beforeToolCall` 可写 | 钩子（**仅当同时 `block`**） | `agent-loop.ts:755-759` |
| ③ `execute()` 返回结果对象 | **工具自己** | `types.ts:445` |
| ④ `afterToolCall` 可覆盖 | 钩子（`??` 语义，可加可撤） | `agent-loop.ts:902` |
| ⑤ 批末 `every()` 聚合 | 循环读标 | `agent-loop.ts:700-702` |

②③④ 是"打标"，⑤ 是"读标"。⑤ 之后这个字段就作废了——**它不进消息、不进会话记录**（`README.md:509` 明确写 *runtime-only*，编译进 transcript 的 `toolResult` 是标准结果），所以事后回放历史查不到它。这也是它容易让人困惑的原因：**没有任何地方能"看见"它，只能从行为反推**。

#### `true` 从哪来：框架从不算它，全靠作者签字

把 `agent-loop.ts` 里出现 `terminate` 的全部 9 行按角色分一遍，困惑就解开了：

| 行 | 代码 | 角色 |
|---|---|---|
| L281 | `hasMoreToolCalls = !executedToolBatch.terminate` | **用** |
| L701 | `finalizedCalls.every((f) => f.result.terminate === true)` | **读** |
| L537 | `terminate: boolean;` | 类型声明 |
| **L512** | `return { messages, terminate: false };` | **全文件唯一一处硬编码 —— 而且是 `false`** |
| L757–758 | `if (beforeResult.terminate === true) result.terminate = true;` | 从钩子**复制** |
| L902 | `terminate: afterResult.terminate ?? result.terminate` | 从钩子**覆盖或继承** |
| L592 / L668 | `terminate: shouldTerminateToolBatch(...)` | **聚合** |

**九行里没有一行"产生" `true`。** 唯一的字面量是 L512 那个 `false`——`failToolCallsFromTruncatedMessage()` 的返回值，含义是"被输出上限截断的批，永远不许终止"。框架在 terminate 上只做三件事：**接受别人给的标、全员同意时停、截断时强制不许停**。它从不自己判断"任务完成了吗"。

所以 `true` 只能从外面进来，而"外面"是世界上的三种人：

| 谁 | 凭什么写 |
|---|---|
| 写工具的人 | "我这个工具的产出是终点" |
| 写 `beforeToolCall` 的人 | "按这条策略，整个运行该作废" |
| 写 `afterToolCall` 的人 | "看了结果才知道，这轮该收" |

回到 `structured_output`：作者写 `terminate: true` 的那一刻，相当于签了个字——"`details` 是交给调用方入库的，模型再复述一遍毫无价值，这里就是终点"。**这个决定在他写工具名的时候就做完了，跟运行时状态没有关系。**

对照同一结果对象上的其他字段，就能看出 terminate 有多特殊：

| 字段 | 值怎么来的 | 性质 |
|---|---|---|
| `isError` | 运行时**算**出来的：`execute()` 抛异常 → `true` | 系统观察到的客观事实 |
| `durationMs` | 运行时**测**出来的：单调时钟差值 | 客观事实 |
| `usage` | 运行时**统计**出来的：模型返回的 token 数 | 客观事实 |
| **`terminate`** | **作者在设计时"声明"的** | **主观意图** |

这就是它最难理解的地方：**其他字段你能顺着代码找到"谁算出来的"，terminate 找不到——那个判断逻辑根本不在代码里，在你脑子里。** 所以别问"什么条件下它会变成 true"，该问"**我作为工具作者，什么时候该签这个字**"。

签字的三个判据（作者视角，设计时回答，不是运行时判断）：

| 判据 | 问法 |
|---|---|
| 产出不归模型消费 | 这个结果是给调用方/用户直接用，还是给模型当下一步的输入？ |
| 语义上不存在下一步 | 调完它，模型还能合法做什么？答案是"只能复述" → 就是终点 |
| 位置在流程末尾 | 它是"交付"动作，还是"取料"动作？ |

三问都偏向"终点"，才写 `true`。任何一个偏向"取料"，就不写——`read` 的作者在设计时就知道它只是手段，所以永远不会写。


**例 1 · `read` 工具，不打** —— 用户看到的是"一句总结"

```
用户  看看 README 讲了什么
模型  (tool_call: read {path:"README.md"})        ← 批里只有这一个调用
read  execute → {content:"# Pi\n...", details:{}} ← 没有 terminate 字段
批末  every([undefined]) → false
循环  hasMoreToolCalls = true → 再发一次请求
模型  "这个 README 讲的是…"                       ← 用户看到的正是这句
```

**例 2 · `structured_output`，打** —— 用户看到的是"数据"，且没有那句总结

```
调用方 给我这次评审的 JSON 结论（SDK/RPC 场景）
模型   (tool_call: structured_output {headline, summary, actionItems})
工具   execute → {content:..., details:{...}, terminate: true}
批末   every([true]) → true
循环   hasMoreToolCalls = false → 跳过回喂 → agent_end
结果   调用方拿到 details；用户端没有任何"好的，以下是…"
```

两个例子的差别就一句话：**打不打 `true`，用户端看到的是"有没有那句总结"**。第 2 例里模型若要回一轮，只能把同一份 JSON 再念成散文——纯浪费，所以停。

**例 3 · 守卫 `block + terminate`** —— 整个运行作废

```
项目 settings 禁用 bash，模型仍试了一次
beforeToolCall → {block:true, reason:"bash is disabled", terminate:true}
占位错误结果 result.terminate = true             ← L757-759
批末 every([true]) → true → 本轮到此为止
```

注意这里的代价：模型**失去了解释的机会**。不加 terminate，它会说"这个项目禁用了命令执行，我改用 read 看看"——多数时候这才是你要的。所以这个写法只在"整个会话都该结束"时成立，不是"某个工具被拦"的默认反应。

**例 4 · 同一个工具，这次 true、下次 false**

```ts
// 假设的 finish_task 工具：只有真干完了才允许停
async execute(_id, _params) {
	const remaining = countRemainingTasks();          // 6 → 0 取决于当次状态
	return {
		content: [{ type: "text", text: remaining === 0 ? "全部完成" : `还剩 ${remaining} 项` }],
		details: {},
		terminate: remaining === 0,                    // ← 逐次决定，不是声明一次
	};
}
```

这一例专治"字段什么时候打上"的困惑：**打标发生在 `execute()` 返回的那一刻，值由当时的业务状态决定**，不是写在工具定义里的静态配置。

**为什么实践中"打 true"几乎总等于"批里只有一个调用"**：全员同意制下，多调用批要同时满足几乎不可能。Pi 的解法是用**提示词**约束模型单独调用它——`structured-output.ts` 里那条 `promptGuidelines` 就是干这个的：

> "Use structured_output as your final action… After calling structured_output, do not emit another assistant response in the same turn."

所以真正落地一个终止型工具，是"**工具返回 `true` + 提示词要求它单独调用**"两件事配套，缺一半就会退化成"偶尔生效"。

#### 想自己跑一遍：`docs/pi/demos/terminate-demo.mjs`

`node docs/pi/demos/terminate-demo.mjs` 直接跑，无依赖。它把 `agent-loop.ts` 的 `L281` 与 `L700` 两行照抄成一个 60 行循环，用假模型驱动四种跑法：

| 场景 | 工具返回 | 请求次数 | 用户看到总结 | 最后一条消息 |
|---|---|---|---|---|
| A | `read_file`，无该字段 | 2 | 有 | `assistant`（一句总结） |
| **B** | `submit_review`，`terminate: true` | **1** | **无** | **`toolResult`（裸数据）** |
| **C** | 同一个 `submit_review`，不写该字段 | **2** | **有**（"好的，以上就是本次评审结论"） | `assistant` |
| D | 混合批：一个不打 + 一个打 | 2 | 有 | `assistant` |

**B 与 C 之间只差 `execute()` 返回对象里的一个字面量**，其余完全相同。这正是"`true` 从哪来"的答案：它不是被算出来的，是你敲上去的。

跑完还能验证一个副作用：四种场景下 `JSON.stringify(messages)` 里**都搜不到 `terminate`**——它是 runtime-only，进了会话记录就被剥掉。

**用 D 记住聚合规则**：`every([undefined, true]) → false`。掺了一个 `true` 也没用，必须是**全员**。

**用 C 记住判据**：不看代码，看那句总结。跑一次不写 `terminate` 的版本，看模型自愿说了什么——
- 说"以上就是本次评审结论"这类**复述** → 该写 `true`
- 说"另外 app.py 第 30 行也有同样的注入"这类**新信息** → 不该写，它还要干活

这条判据比任何规则都好用，因为它直接测的是"那一轮请求有没有产出"。



| 该打 `true` | 为什么 | Pi 的实例 |
|---|---|---|
| 结果即交付物（结构化数据、最终答案对象） | 模型回一轮只能把同样的内容复述成散文，纯浪费 | `structured-output.ts`：`execute()` 返回 `terminate: true` |
| 把 agent 当函数调用（RPC/SDK 场景），调用方只要返回值 | 调用方不读自然语言，多一轮是纯延迟 | 同上，导出场景 |
| 守卫判定整个运行已无意义（如"本项目禁用 bash"） | 停下来是结论本身 | `README.md:249`：`{ block: true, reason: "bash is disabled", terminate: true }` |
| 控制权交接给外部系统，结果即终点 | 后续由别的系统接手 | 交接/委派类工具 |

| 不该打 `true` | 为什么 |
|---|---|
| read / bash / grep / edit 等供模型继续推理的工具 | 模型必须看到结果才能决定下一步。**这就是内置工具全部不设 terminate 的原因** |
| 结果是 `isError` | 错误是"该纠错"的信号，打 true 会让"失败"直接变成"静默结束"，用户只看到一条报错 |
| 用户期待一句解释 | terminate 之后没有 assistant 文本，用户看到的是裸工具结果 |
| 纯省 token 的动机 | 省下的一轮通常几百 token，误判一次却整轮白跑 |

**成本是不对称的，所以默认值必须是 `false`。** 漏打 → 多一次请求（延迟 + 几百 token）；误打 → 模型失去总结与纠错机会，且"本来还要继续"的任务会静默中断。后者是功能缺失，前者只是开销。Pi 八个内置工具全部取默认，正是这条不对称的选择结果。

四个自问，全 yes 才打 `true`：

1. 这个结果**本身就是**交付物，而不是供模型判断的材料？
2. 模型看到它之后**确实无事可做**（无后续工具、无纠错、无汇报）？
3. 用户/调用方要的是**数据**而不是**一段话**？
4. 这批里其他调用**也都不需要回模型**？（并行批里几乎不可能同时成立，所以实践中等价于"这批只有一个调用且它是终局动作"）

第 4 条也解释了为什么"批里只有一个 terminate"的直觉会流行：**能触发终止的批，现实中几乎都是单调用批**。但规则是"全部 true 才停"，不是"只能有一个 true"——两者在单调用批上表现一致，在多调用批上完全不同（见上文 1867 号测试：一个 block+terminate、一个正常执行 → 继续）。

**terminate 是弱提示，只关掉四个"继续理由"中的一个**：

| 继续理由 | terminate 能关掉吗 |
|---|---|
| 有工具结果要回给模型 | ✅ 能（这是它唯一的作用） |
| 队列里有 steering 消息（用户边跑边插话） | ❌ 不能，`pendingMessages.length > 0` 仍进内层循环 |
| 有 follow-up 消息（等 agent 干完再补的） | ❌ 不能，外层循环继续 |
| `finishTurn` 返回 `{action:"continue"}` | ❌ 不能，`explicitContinuation` 仍强制一轮 |

对成本敏感的路径还有一处副作用：`compaction.md:37` 提到，工具批终止且队列无消息时，Pi **会跳过轮间那次压缩检查**——省的是又一次省，但前提是"确实不需要再回模型"。

**对你项目的直接结论**：你 `agent/tools/base.py` 的 `ToolResult` 没有 terminate，而现在这七个工具（读文件、执行命令、改文件…）**没有一个该打 true**——它们全是"供模型继续推理"的中间动作。真正需要 terminate 的是你还没有的那类工具：`structured_output`（比如把 `/review` 的结构化结论直接返回给调用方）、`handoff`（交接给人工）。所以 Day 7 加 `terminate` 字段时，别急着给现有工具打标——**字段加进去、默认全 false、等出现终局型工具才用**，这才是 Pi 的实际形态。

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

## 补充深读（2026-10-27）：L147–192 的四处易错点

前面第 7–10 节把类型讲清了，但**语义**上有四处容易读偏。这四条只有读注释和实际调用点才看得出来。

### 补充 1 · L149–158：`continue` 不是"多发一次"，是"保证至少一次"

`FinishTurn` 的注释里这句最难读，但最要紧：

> On a normal turn, `{ action: "continue" }` **ensures one next provider request**. Tool-result, steering, or follow-up scheduling **can satisfy that request and adds no extra request**; otherwise the loop continues once with the current context.

拆开是两层：

- 循环接下来**本来就要**再发一次请求（因为刚有工具结果要回给模型）
- 这次请求**已经满足**了 `continue` 的要求 → **不会额外多跑一轮**

所以 `continue` 的真实作用是**"不许在这里停下"**。当循环本来就想停（没有工具调用、没有 steering、没有 follow-up）时，`continue` 把它拽回来再跑一次。

**PHP 对照**：像是 `do { } while ($hook->wantsMore())` 里的 `wantsMore()`——它不是"再加一轮"，而是"别退出"。

### 补充 2 · L153：`error` / `aborted` 是硬出口，钩子管不了

同一段注释最后一句：

> Error and aborted responses remain **hard exits**.

即使 `finishTurn` 返回 `{action:"continue"}`，模型报错或用户中止时循环照样结束。**安全阀优先于扩展点**——扩展点可以在正常路径上做任何决定，但不能覆盖"该停了"的判定。

这条原则值得直接搬进你自己的框架：任何 `on_xxx` 钩子都不该有权在错误状态下阻止退出，否则调试时会遇到"明明报错了却还在跑"。

### 补充 3 · L160–170：`AgentLoopTurnUpdate` 是"替换"，不是"合并"

```ts
/** Replacement runtime state used by the agent loop before starting another provider request. */
```

四个字段全可选，但**给了就是整个换掉**。不是往旧 context 里塞几条消息，而是用你返回的那个对象作为新的运行时状态。

和 `afterToolCall` 注释里的 "No deep merge is performed"（L338）是同一取向：**要么别给，要么给全**。半合并在嵌套结构上会产生"我以为我改了、其实没改"的隐性 bug。

**PHP 对照细节**：TS 的 `field?: T` 是"字段可不存在"，PHP 的 `?T $field = null` 是"存在但为 null"。**在"区分有没有给"这件事上 TS 更准**——`"model" in obj` 一句就能判断，PHP 要 `array_key_exists()` 和 `=== null` 分开写。

### 补充 4 · ⚠️ `prepareRequest` vs `prepareNextTurn`：名字极像，三处全不同

这对最容易混，混了就写错钩子。第 9、10 节分别讲了它们，但没并排比过：

| | `prepareRequest`（L186） | `prepareNextTurn`（L278） |
|---|---|---|
| **时机** | **每次**请求前，**含第一次** | `turn_end` **之后**、且循环**将要继续**时 |
| **作用域** | **本次及之后所有请求**（"this and later requests", L269） | **只影响下一轮**（"affect that turn", L275） |
| **能否追加 messages** | ❌ 类型禁止（`Omit` 掉，因已追加完毕） | ✅ `messages[]`，它是唯一能在轮间插消息的钩子 |
| **是否轮询队列** | 不轮询（L269 明说 *does not poll queues*） | 不轮询（steering/follow-up 由循环自己轮询） |

一句话记：**`prepareRequest` 改的是常驻设置，`prepareNextTurn` 递的是下一轮的一次性补丁。**

为什么必须两个：一个"改状态"，一个"递参数"。合成一个的话，你就得靠返回值去猜"这次改的模型是只影响这一轮，还是往后都算"——那是靠猜，不是靠类型表达。

### 补充 5 · 一处风格不统一，别硬找解释

返回值类型三处不一致：

| 类型 | 允许 `void`？ | 允许 `undefined`？ |
|---|---|---|
| `FinishTurn` | ✅ | ✅（`Promise<AgentTurnDecision \| undefined>`） |
| `PrepareRequest` | ✅ | ✅ |
| `prepareNextTurn` | ❌ | ✅ |

`void` 和 `undefined` 在"没意见"这个语义上重复了，`prepareNextTurn` 只留了一种写法。**同一代码库风格不完全统一是常态**——看到差异先想"是不是不同时间写的"，而不是硬找一个设计理由。

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
5. **`finishTurn` 返回 `{action:"continue"}` 时，会不会额外多发一次模型请求？**（2026-10-27 补充）
6. **模型报错（`stopReason === "error"`）时，`finishTurn` 返回 `continue` 还有效吗？**（2026-10-27 补充）
7. **一个批里有三个工具调用，只有第二个返回了 `terminate: true`，这批跑完会不会停？为什么 Pi 要这么设计，而不是"任一 true 就停"？**（2026-10-27 补充）
8. **`beforeToolCall` 返回 `{terminate: true}` 但不给 `block`，会发生什么？**（2026-10-27 补充）
9. **为什么 Pi 的 `read` / `bash` / `edit` 全都不设 `terminate`？如果给 `bash` 打上 `true`，会出现什么现象？**（2026-10-27 补充）
