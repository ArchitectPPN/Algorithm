# Pi 必读代码导读

> 核查日期：2026-10-22，对应 Pi v1.4.0（`main` 分支，117,029 stars）
> 源码均已实际下载并逐行读过，行号来自当前版本，只用于定位，不作稳定性承诺。

---

## 零、先划边界

Pi 全仓库是 11 个包的 TS monorepo，`packages/coding-agent/src/core` 一个目录就有 916 KB 源码，其中 `agent-session.ts` 单文件 164 KB、`package-manager.ts` 87 KB。

但这些都不是 agent 机制。

真正决定「Pi 怎么当一个 agent」的代码，集中在 `packages/agent/src` 的 6 个文件里，合计约 83 KB / 2,100 行。剩下 95% 是 CLI 应用层、TUI、包管理、遥测——和你理解 agent 设计几乎无关。

判定标准只有一条：**删掉它，Pi 就不是 agent 了**。按这条标准筛，清单如下。

| 层级 | 文件 | 规模 | 判定 | 理由 |
|---|---|---|---|---|
| 一 | `agents/src/types.ts` | 540 行 | **全读** | 数据契约，不理解它后面全是天书 |
| 一 | `agent/src/agent-loop.ts` | 959 行 | **全读** | 控制流本体，全项目最高价值 |
| 一 | `agent/src/agent.ts` | 623 行 | **全读** | 状态与生命周期，loop 的有状态外壳 |
| 二 | `coding-agent/src/core/system-prompt.ts` | 229 行 | 读 2 个函数 | 提示词分段化与增量 patch |
| 二 | `coding-agent/src/core/skills.ts` | 514 行 | 读 2 个函数 | 渐进披露的真实实现 |
| 二 | `coding-agent/src/core/compaction/compaction.ts` | 1,104 行 | 读 5 个函数 | 压缩的阈值、切点、拆轮 |
| 二 | `codemode/src/types.ts` + `declarations.ts` | 约 900 行 | 概念 + 渲染 | 沙箱编排工具调用的接口 |
| 三 | `agent/src/proxy.ts`、`stream-fn.ts`、`index.ts` | 415 行 | 跳过 | SSE 转发 / 全局变量 / 导出 |
| 三 | `tui`、`env`、`chord`、`telemetry`、`evals` | — | 跳过 | 与 agent 机制无关 |
| 三 | `coding-agent` 的 `agent-session.ts`、`session-manager.ts`、`package-manager.ts` | 315 KB | 跳过 | 应用层装配与 CLI 逻辑 |

实际精读量：**第一梯队 2,122 行 + 第二梯队约 1,000 行**。

---

## 一、`types.ts`：定义整个运行时的数据契约

**一句话**：它不是「类型定义文件」，它是 Pi 对「agent 是什么」的形式化回答。三组类型构成骨架。

### 1. `AgentMessage` 与 `convertToLlm` 边界

```ts
export type AgentMessage = Message | CustomAgentMessages[keyof CustomAgentMessages];
```

内部消息与 LLM 消息**不是同一个类型**。`convertToLlm` 是唯一的转换边界（L222），只在每次调用 LLM 前执行。

这个设计的实际收益：你可以往 transcript 里塞 UI 通知、状态提示、自定义 artifact 消息，它们在内部完整存在，但在发给模型前被过滤或投影。Pi 的哲学是「会话记录是事实，模型看到的只是投影」。

### 2. `AgentLoopConfig`：一份「策略注入清单」

这是全文件信息密度最高的地方。除模型本身外，**所有策略都是回调**：

| 回调 | 时机 | 用途 |
|---|---|---|
| `transformContext` | 转换前 | 窗口管理、外部上下文注入 |
| `convertToLlm` | 每次请求前 | 投影到 LLM 消息 |
| `getApiKey` | 每次请求前 | 短期 token（如 OAuth）会过期 |
| `beforeToolCall` | 参数校验后、执行前 | 权限拦截，`{block:true}` 阻止 |
| `afterToolCall` | 执行后、发事件前 | 结果改写 |
| `finishTurn` | 每轮结束 | 返回 `{action:"end"}` 强制收尾 |
| `prepareRequest` | 每次请求前 | 替换 context/model/thinking |
| `getSteeringMessages` | 工具批次结束后 | 中途插话 |
| `getFollowUpMessages` | agent 本该停止时 | 排队后续任务 |

核心包因此**不认识任何 provider、任何存储、任何权限模型**。这是它能被 OpenClaw 之类项目当 SDK 嵌入的根本原因。

### 3. `terminate` 的三态语义

`AgentToolResult.terminate`（L445）不是「立即停止」，而是「**建议**本批次后停止」，且注释明确写了聚合规则：`every`——批次内所有结果都为 true 才终止。这条规则的实现在 `agent-loop.ts:700`。

### 4. `StreamFn` 的契约（L27-32）

「不得抛出，不得返回 rejected promise；失败必须编码进流，以 `stopReason: "error" | "aborted"` 结束。」

这是 Pi 全局错误处理策略的源头：**错误是数据，不是异常**。

### 读的时候盯什么

- `AgentToolCall` 那行 `Extract<AssistantMessage["content"][number], { type: "toolCall" }>`：判别联合的典型用法，也是你唯一需要专门补的 TS 特性
- `AgentState.systemPrompt` 为什么是 `readonly`：改提示词必须**追加 system message**，不能赋值。因为提示词是 transcript 的一部分，不是外部配置
- `ToolExecutionMode` 的注释（L39-47）：`parallel` 的准确语义是「预备串行、执行并发、结果按源序发出」

---

## 二、`agent-loop.ts`：控制流本体，全项目最该读的文件

**一句话**：一个双层 `while` 循环加十个可插拔钩子，就是完整 agent。

### 骨架

```
runLoop (L172)
├─ 外层 while(true)            ← follow-up 消息在这里续命
│  ├─ 内层 while(hasMoreToolCalls || pendingMessages.length)
│  │  ├─ prepareNextTurn → prepareRequest
│  │  ├─ streamAssistantResponse          (L390)
│  │  ├─ stopReason 检查（error/aborted 硬退出）
│  │  ├─ executeToolCalls                 (L518)
│  │  ├─ finishTurn → turn_end
│  │  └─ getSteeringMessages
│  └─ getFollowUpMessages → 有则 continue
└─ agent_end
```

### 六个必须看懂的机制

**1. 退出条件不是计数器**

```ts
hasMoreToolCalls = !executedToolBatch.terminate;
```

全文件没有 `max_rounds`、没有 `max_steps`、没有终止工具。收敛靠两件事：模型不再产生 tool call，或所有工具结果都带 `terminate: true`。

对着你自己的 `max_rounds` 想一遍：你的上限解决的是什么问题？如果答案是「防止死循环」，Pi 认为那是提示词和工具设计的问题，不该用硬上限掩盖。

**2. `terminate` 的悲观聚合（L700）**

```ts
return finalizedCalls.length > 0 && finalizedCalls.every((f) => f.result.terminate === true);
```

一个批次里三个工具，两个说停一个说继续 → **继续**。理由写在设计里：并行的工具互不知情，任何一个认为「还有事要做」都应被尊重。

**3. 两种插话通道的时机差**

- `steering`（Enter）：在**当前工具批次全部执行完之后**注入，且明确「不跳过当前 assistant 消息的工具调用」。它是纠偏，不是中断。
- `follow-up`（Alt+Enter）：只在 agent **本该停止时**才查询。它是排队，不是插话。

代码里这个差异体现在：`getSteeringMessages` 在内层循环末尾调用，`getFollowUpMessages` 在外层循环末尾调用。

**4. 截断时整批工具全部作废（L488）**

```ts
message.stopReason === "length"
  ? await failToolCallsFromTruncatedMessage(toolCalls, emit)
  : await executeToolCalls(...)
```

注释给了理由：流式解析器带 salvage 能力，被 output token 截断的参数**可能仍然能解析通过、却语义不完整**。执行这种调用的破坏性远大于让它失败重来。所以一个不留，全部报错，让模型重发。

这是「宁可失败，不可半对」的典型工程判断，面试很好讲。

**5. 并行执行的三段式（L596）**

```
阶段一：prepare 串行   ← 参数校验、beforeToolCall 都可能否决
阶段二：execute 并发   ← Promise.all
阶段三：结果按 assistant 源序发出
```

但注意 `tool_execution_end` 是**按完成顺序**发的（`finalizedCalls.push` 后立即 emit），而 `tool_result` 消息是**按源序**发的。两个消费者看到两种顺序：UI 要实时，模型要确定性。

**6. 工具集变更是增量声明（L342 `declareToolChanges`）**

工具增减时不重新生成 prompt，而是插入一条 `content: ""` 的 system message，只带 `toolsAdded` / `toolsRemoved` 两个字段。

这就是提示词增量更新的最小实现——和 `system-prompt.ts` 的 section diff 是同一思路的两个层次。

### 还要注意的细节

- `abort` 检查点有四处：prepare 前、`beforeToolCall` 后、每个顺序工具尾部、并行任务入队时。中断不是「设个 flag 就完事」
- 工具结果本身也是一条消息，也要走 `message_start` / `message_end`（L956）。事件流里没有「特殊通道」
- `executePreparedToolCall`（L831）里 `updateEvents` 是累积后 `await Promise.all` 的，且 `acceptingUpdates` 在工具 settle 后立刻置 false——防止工具在结束后还往事件流里推数据

---

## 三、`agent.ts`：把无状态循环包成有状态对象

**一句话**：`agent-loop.ts` 是纯函数式的（进出都是 context），`agent.ts` 负责持有状态、管生命周期、发事件。两者职责切得很干净。

### 四个职责

**1. 状态持有**

`MutableAgentState`（L71）加 getter/setter 做防御性拷贝。`set messages(next) { messages = next.slice(); }`——外部拿到数组后随便改也不会污染内部。

**2. 生命周期**

```ts
runWithLifecycle (L517)
  activeRun = { promise, resolve, abortController }
  isStreaming = true
  try { executor(signal) }
  catch → handleRunFailure
  finally → finishRun (resolve promise)
```

`isStreaming` 的结束点被刻意延后：不是 `agent_end` 发出时，而是**所有 `agent_end` 监听器都结算完**。注释（L271-273）专门强调了这点。这解决了「UI 已收到结束事件但还在渲染，此时用户又发消息」的竞态。

**3. 事件归约（L575 `processEvents`）**

关键认知：**loop 不直接改 `state.messages`**。它只发事件，由 `processEvents` 折叠成状态，然后再按订阅顺序 `await` 每个 listener。

这意味着：
- 事件是状态转移的**唯一入口**，可重放、可录制
- listener 是**串行 await** 的，一个慢 UI 会拖慢整个 run。这是显式设计，不是疏漏

**4. 两个队列（L149 `PendingMessageQueue`）**

`"all"` 与 `"one-at-a-time"` 两种排出模式。默认 `one-at-a-time`——一次只放一条插话，避免用户连打三句话把 agent 的节奏打乱。

### 值得单独看的：错误也是消息（L542）

```ts
handleRunFailure → 合成 { role:"assistant", stopReason:"error"|"aborted", errorMessage }
  → message_start → message_end → turn_end → agent_end
```

异常不往上抛，而是**降级成一条正常的助手消息走完整事件序列**。上层 UI 完全不需要 try/catch，只需判断 `message.errorMessage`。

对比你自己项目里错误怎么处理的：如果 CLI 里有一堆 `except` 分支在打印不同错误，这里可以直接替换掉。

---

## 四、`system-prompt.ts`：提示词是分段的数据结构，不是一段字符串

**一句话**：229 行，解决「提示词怎么既稳定又能增量更新」。

### 核心结构

```ts
export type SystemPromptSections = Record<string, string>;   // L56
```

除 `preamble` 外，每个 section 都被包成 XML 标签写进 `SystemMessage.sections`：

```
<preamble>你是...
<tools>- read: ...
<rules>- Be concise...
<docs>...
<project_context><project_instructions path="...">
<skills>...
<cwd>/path/to/project
```

### 真正的巧思在 `diffSystemPromptSections`（L217）

```ts
export function diffSystemPromptSections(previous, current) {
  // 变了 → patch[name] = 新值
  // 没了 → patch[name] = null
}
```

提示词变更只发**差异**，不是全量重发。配合缓存，稳定 section 命中缓存，变化 section 单独计费。

这条和 `agent-loop.ts` 的 `declareToolChanges` 是一对：**提示词的每一块都可以被单独替换**。

### 两个细节

- **默认工具只有四个**：`["read", "bash", "edit", "write"]`（L64）。不是能力不足，是刻意控制工具选择的熵
- **技能段的前提**：只有当 `read` 或 `bash` 可用时才注入 skills（L175-182）。因为技能正文要靠读文件获取，没有读工具就等于给了一张拿不到的地址

---

## 五、`skills.ts`：渐进披露不是概念，是这 30 行

**一句话**：514 行里，真正决定 token 成本的是 `formatSkillsForPrompt`（L358）。

### 全部秘密在这里

```ts
for (const skill of visibleSkills) {
  lines.push("  <skill>");
  lines.push(`    <name>${skill.name}</name>`);
  lines.push(`    <description>${skill.description}</description>`);
  lines.push(`    <location>${skill.filePath}</location>`);
  lines.push("  </skill>");
}
```

**只有三个字段：name、description、location。技能正文一个字都不进提示词。**

模型看到的是「有一批技能，各自是什么，文件在哪」；匹配到任务时，它自己用 `read` 去读 `location`。正文的 token 成本从「常驻」变成「按需」。

量化一下：一个 400 行的 SKILL.md 约 3,000–5,000 token。十个技能常驻就是 3–5 万 token，接近整个窗口的一半；改成渐进披露后常驻成本是每个技能约 40 token。

### 其余部分是工程细节，但要读懂三条

**发现规则**（L160-171）：目录含 `SKILL.md` 即视为技能根，**不再向下递归**；否则加载根目录的 `.md`，并递归子目录找 `SKILL.md`。

**冲突处理**（L437-455）：同名技能**先到者赢**，后到者记 `collision` 诊断，不覆盖也不报错。顺序是 user 级在前、project 级在后——所以用户级技能优先于项目级。这个顺序值得和你自己的 loader 对比，大多数实现是反的。

**`disable-model-invocation`**（L341、L359）：标了 `true` 的技能完全不进提示词，只能通过 `/skill:name` 显式调用。这是「工具不该被模型自主发现」的场景。

---

## 六、`compaction.ts`：1,104 行，只需读 5 个函数

**一句话**：上下文压缩的完整决策链——什么时候压、从哪里切、切在轮中间怎么办、摘要写什么。

| 函数 | 行号 | 干什么 |
|---|---|---|
| `shouldCompact` | 267 | 阈值判定 |
| `estimateContextTokens` | 196 | 估算当前用量 |
| `findCutPoint` | 446 | 决定从哪条消息开始保留 |
| `prepareCompaction` | 859 | 组装摘要输入，处理拆轮 |
| `compact` | 952 | 调模型生成摘要，拼接结果 |

### 1. 阈值是公式，不是百分比

```ts
export const DEFAULT_COMPACTION_SETTINGS = {
  enabled: true,
  reserveTokens: 16384,
  keepRecentTokens: 20000,
};
// L269
contextTokens > contextWindow - settings.reserveTokens
```

`reserveTokens` 是给「下一次响应留的余量」。比「用到 70% 就压」好在两点：不同模型的窗口差异被吸收，且余量明确是为输出服务。

### 2. 估算不重算全部（L196）

找一个锚点：**最后一条有效 assistant 消息的 usage**（跳过 aborted / error / 全零），只对它之后的消息做字符估算。

`calculateContextTokens` 优先用 `usage.totalTokens`，缺失才求和。这个模式叫「信任计量器，只在计量器之后做估算」，是做 token 统计的正确姿势。

### 3. 切点绝不落在工具结果上（L446）

从最新往回累加，累计到 `keepRecentTokens` 就切，但：

- 只在 **user 或 assistant** 消息处切
- 切在带 tool call 的 assistant 消息上时，它的 tool result 在后面，会一起保留
- 切点之后还要**向前吞并**不影响上下文的元数据条目（L482-489）

如果切在 assistant 消息上（即切在一轮中间），`isSplitTurn = true`，进入下面的特殊处理。

### 4. 拆轮是这个文件最被低估的部分（L980-1019）

切在一轮中间会丢语义：turn prefix（这一轮的前半段）既不属于「历史」，也不该和「保留的最近部分」混在一起。

Pi 的做法是**生成两段摘要再拼接**：

```
[历史摘要]
---
**Turn Context (split turn):**
[这一轮前半段的摘要]
```

绝大多数实现直接切、不管轮边界，结果是模型看到「一个没有开头的任务」。这个边界条件值得单独记。

### 5. 摘要模板是固定结构（L507）

```
## Goal
## Constraints & Preferences
## Progress (Done / In Progress / Blocked)
## Key Decisions
## Next Steps
## Critical Context
```

末尾一句要求：「保留精确文件路径、函数名、错误信息」。

另外还会 `extractFileOperations` 从消息里扒出读过/改过哪些文件（L60），格式化后附在摘要末尾（L1043-1044）。这解决压缩后「忘了动过哪些文件」的问题。

---

## 七、`codemode`：原理要懂，实现只需看两个文件

**一句话**：模型写一段 JS，在 QuickJS/WASM 沙箱里通过 `tools.<name>(args)` 调用工具，**只有脚本的返回值进上下文，中间调用结果全不进**。

### 为什么这是「换实现而不是换原则」

Pi 原本明确不做 MCP。需求挡不住时，它没有把工具塞回提示词，而是让模型写脚本。读十个文件这件事：

| 范式 | 上下文成本 |
|---|---|
| 十次 tool call | 十份文件内容全部进入，且逐轮累积 |
| 一段脚本 | 只有 `return 结论` 进入，中间结果在沙箱里 |

### 只需看两个文件

**`types.ts`**（10,691 B）：接口契约。关键是这三个类型

- `CodemodeTool`：`execute(args, context) => Promise<unknown>`，返回值必须 JSON 可序列化
- `CodemodeResult`：`{ ok, value, output, calls, storeWrites }`——注意 `calls` 记录了每个工具调用的耗时与状态，但**结果不在里面**
- `CodemodeRun.deliver(seq, now, id, outcome)`（L242）：结果按序号投递，VM 严格按序应用，乱序到达要等缺失的号。这是为**确定性重放**服务的，配合 `durable` 包做崩溃恢复

限额也是接口的一部分：`timeoutMs` 默认 300s、`memoryLimitBytes`、`MAX_OUTPUT_CHARS`、`MAX_OUTPUT_ITEMS`、store 单值与总量上限。

**`declarations.ts`**（14,574 B）：把 JSON Schema 渲染成 **TypeScript 声明**给模型看。

```ts
declare const tools: {
  /** 工具描述变成 doc comment */
  read_file(path: string, offset?: number): Promise<{ text: string }>;
};
```

模型看到的不是 `{"type":"object","properties":{...}}`，而是类型签名。同样的信息，token 更少、歧义更小。`DEFAULT_INPUT_SCHEMA_MAX_CHARS = 16000` 是单工具渲染上限，超了退化成 `unknown`。

这个「schema → 类型签名」的转换，是可以直接搬到任何 agent 上的小技巧。

---

## 八、明确不必读

| 文件 | 规模 | 为什么跳过 |
|---|---|---|
| `agent/src/proxy.ts` | 393 行 | 只是把服务端 SSE 转成事件流，无设计含量 |
| `agent/src/stream-fn.ts` | 21 行 | 一个模块级变量加两个函数 |
| `agent/src/index.ts` | 170 B | 纯导出 |
| `coding-agent/src/core/agent-session.ts` | 164 KB | CLI 应用装配，机制都在 agent 包里 |
| `coding-agent/src/core/session-manager.ts` | 63 KB | 会话文件读写的工程细节，格式看 `docs/session-format.md` 更快 |
| `coding-agent/src/core/package-manager.ts` | 87 KB | npm 供应链管理，与 agent 无关 |
| `tui` / `env` / `chord` / `telemetry` / `evals` | — | 表现层与运维层 |

一个判断技巧：**`packages/agent` 里没有一行 provider 相关代码**。凡是要知道「用哪个模型」「连哪个 API」的，都是应用层，可以直接跳过。

---

## 九、阅读顺序与产出

不要按目录顺序读，按「数据 → 控制 → 状态 → 装配」：

| 站 | 文件 | 行数 | 产出 |
|---|---|---|---|
| 1 | `types.ts` | 540 | 手画一张消息与事件的关系图，能说出 `convertToLlm` 存在的理由 |
| 2 | `agent-loop.ts` L111-330 | 220 | 不看代码默写 `runLoop` 的骨架和两个退出条件 |
| 3 | `agent-loop.ts` L390-960 | 570 | 补充四条笔记：截断、并行、终止聚合、工具集增量 |
| 4 | `agent.ts` | 623 | 说清「loop 只发事件、`processEvents` 才改状态」这条边界 |
| 5 | `system-prompt.ts` + `skills.ts` | 743 | 测出你自己项目改渐进披露前后的 token 差 |
| 6 | `compaction.ts` 的 5 个函数 | 约 300 | 画出「历史摘要 + 拆轮前缀」的拼接结构 |
| 7 | `codemode` 两个文件 | 约 900 | 一张「十次调用 vs 一段脚本」的上下文成本对照表 |

第 1–4 站是**必经**，约 2,100 行；第 5–7 站是**选读**，看你要改哪一块。

时间上，第 2 站真正读懂（能默写骨架、能回答为什么没有 `max_rounds`）是唯一需要反复看的地方，其余都是单向线性阅读。

---

## 十、八道自测题

能不看代码答出来，才算读完。

1. Pi 没有步数上限，那它靠什么保证一定会停？如果模型连续 20 轮都在调工具，会发生什么？
2. 一个 assistant 消息里三个并行工具，其中两个结果带 `terminate: true`，一个不带。批次结束后会发生什么？为什么是这个方向？
3. `stopReason === "length"` 时，为什么不能「执行那些参数还能解析的工具调用」？
4. 用户在 agent 跑工具时按 Enter，那条消息会在什么时候进入上下文？当前批次的工具会被跳过吗？
5. `tool_execution_end` 和 `tool_result` 消息，哪个按完成顺序、哪个按源序？为什么要分开？
6. `isStreaming` 在哪一刻变成 false？为什么不是 `agent_end` 发出时？
7. 技能正文的 token 成本，在渐进披露下是常驻还是按需？提示词里的 `location` 字段是给谁看的？
8. 压缩切点为什么不能落在 tool result 上？切在 assistant 消息上时，多出来的那段摘要叫什么？

---

## 附：与你项目的逐文件对照

| Pi | 行数 | 你的对应文件 | 行数 | 差距在哪 |
|---|---|---|---|---|
| `agent-loop.ts` | 959 | `agent/core/agent_loop.py` | 338 | 你有 `max_rounds` 硬上限；没有 steering/follow-up 双通道；没有终止聚合 |
| `types.ts` | 540 | `agent/core/message.py` | 75 | 你的消息类型缺 `terminate`、`isError`、`durationMs` 三态 |
| `agent.ts` | 623 | `agent/core/session_manager.py` | 176 | 你没有「事件归约成状态」这一层，状态是直接改的 |
| `skills.ts` | 514 | `agent/skills/loader.py` | 114 | 需确认是整篇注入还是只注入描述 |
| `compaction.ts` | 1104 | `agent/core/context_manager.py` | 104 | 缺拆轮处理、缺 fileOps 提取 |
| `message_queue.py` | — | `agent/core/message_queue.py` | 51 | 已有队列，可对照 `all` / `one-at-a-time` 两种排出模式 |

按这个表，改造优先级是：**消息三态 → 事件归约 → 渐进披露 → 拆轮压缩**。前两项是结构性的，后两项是可量化的收益。
