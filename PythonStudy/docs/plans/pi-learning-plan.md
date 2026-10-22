# Pi 学习计划（RAG 暂停期的替代主线）

> 目标不是"读懂 Pi 源码"，而是**抽出能迁移到自己 `agent/` 项目、且面试可追问的设计**。
> 背景：RAG 已软暂停（见 `.workbuddy/memory/2026-10-22.md`），本计划占用约 6 个工作日 × 2h，不新增周次。

---

## 零、三条原则

1. **不通读全部 11 个包，但核心包必须全读**。`packages/agent` 只有 **6 个源文件、约 83KB**，一个下午能读完，而且必须读——它是整个 Pi 的控制流所在。真正的负收益是去读 `coding-agent` 的 `main.ts`(35KB)、`package-manager-cli.ts`(37KB)、`config.ts`(23KB) 和 42 篇文档。
2. **不通读文档**。官方 `packages/coding-agent/docs/` 有 42 篇，本计划只读 6 篇，其余按需查。
3. **每学一项必须落到自己的代码或面试卡**。只"看懂"不算完成——按 `docs/notes/learning-checklist.md` 的三步法验收（能写 / 能一句话讲 / 能答追问）。

### 源码阅读分层（2026-10-22 实测文件大小）

| 包 | 源码规模 | 处置 |
|---|---|---|
| `agent` | **6 文件 ≈ 83KB**（`agent-loop.ts` 30KB、`types.ts` 21KB、`agent.ts` 20KB、`proxy.ts` 11KB、其余 2 个小文件） | **全读**，1 天内 |
| `codemode` | 独立小包，零 pi 依赖 | 可全读（0.5 天） |
| `coding-agent` | `src/` 下 8 个目录 + 7 个顶层文件，其中 3 个超过 20KB | 只读 `core/` 下 session 与 system-prompt 相关文件 |
| `ai` | 大，多 provider 适配 | 只读 provider 接口定义与 `types` |
| `durable` | 大，且有 4612 行设计文档 | 读设计文档，不读实现 |
| `tui` / `env` / `chord` / `telemetry` / `evals` / `mcp` | — | 不读 |

> 判断依据：**代码价值密度 = 控制流 / 数据模型，而不是行数**。`agent-loop.ts` 30KB 里有重试、终止、中断、事件顺序的全部真实处理，比 42 篇文档加起来信息量更大；`package-manager-cli.ts` 37KB 只是在装包。

---

## 一、语言门槛（2–3h，一次性）

Python 背景读 TS 的最小必要集，只学到"能读"为止：

| 概念 | 为什么需要 | Pi 中的例子 |
|---|---|---|
| 类型注解与 `interface` | 所有包都是 TS | `SessionEntryBase { id, parentId, timestamp }` |
| **联合类型 + 判别字段**（discriminated union） | 会话条目靠 `type` 字段分流，这是 Pi 数据模型的核心 | `message` / `compaction` / `model_change` / `branch_summary` |
| 泛型基础 | `Connection<T>`、`ExecutionEnv` 这类抽象 | `RemoteExecutionEnv` |
| `async/await` 与 Promise | 全链路流式 | provider 流式返回 |
| 模块导入与 workspace 包 | 理解 11 包的依赖方向 | `@earendil-works/pi-ai` |

**不学**：装饰器、TS 编译配置、前端框架。除非后续要写扩展。

**验收**：能读懂 `session-format.md` 里任意一个 JSON 示例并说出字段含义。

---

## 二、A 类：必须掌握（3 天，能改代码）

### A1. Agent loop 的收敛与干预（0.5 天）

**读**：`docs/latest/how-pi-works`（3.3KB）+ `packages/agent/src/types.ts`（21KB，数据模型）+ `packages/agent/src/agent-loop.ts`（30KB，控制流）

**读法**：先 `types.ts`，把消息、工具调用、事件、终止标志的结构建立起来；再 `agent-loop.ts` 看控制流怎么用这些结构。`agent.ts`（20KB，状态持有与对外 API）放最后看，`proxy.ts` / `stream-fn.ts` 可跳过。

> 文档只写了"流式返回 → 执行工具 → 记录结果"，代码里才有重试策略、终止判定、中断时机、事件顺序的真实处理。这一层差异是文档给不了的。

三个要抓住的差异：

- 循环**没有终止工具、没有步数上限**，靠"助手回复不含 tool call"自然停止
- 工具可返回 `terminate: true` 跳过后续请求；一批工具**全部** terminate 才提前结束
- 双通道：`steering`（Enter，当前工具批次后注入并打断剩余工具）vs `follow-up`（Alt+Enter，等完成后再进入）

**对照自己的代码**：`agent/core/agent_loop.py`（有 `max_rounds=30` 上限，Ctrl+C 中断）

**追问自测**：
1. 为什么不需要 `task_complete` 工具？什么情况下这个设计会失控？
2. 你的 `max_rounds` 是安全网还是补丁？Pi 靠什么替代它？

### A2. 上下文装配与渐进披露（1 天）

**读**：`packages/coding-agent/src/core/system-prompt.ts`（千 token 怎么拼）、`docs/skills.md`、`docs/compaction.md`（24KB，只读触发与保留规则两节）

四个机制：
- system prompt = 基础指令 + `AGENTS.md`（逐级向上查找）+ 工具定义 + skill **描述**
- **skill 全文按需加载**：常驻上下文只有一行描述，触发才载入完整指令与 schema
- compaction 触发：`contextTokens > contextWindow - reserveTokens`（默认 reserve 16384）
- 压缩插入一条 `compaction` entry，原始 entry 永不删除

**对照自己的代码**：`agent/core/context_manager.py`（70% 阈值、字符数/3 估算）、`agent/skills/loader.py`（整篇注入）

**产出**：改造 skill 加载为渐进披露，测出改造前后每轮 system 部分的 token 占比。

**追问自测**：
1. 渐进披露和 prompt cache 的关系是什么？为什么"按需加载"反而能提高缓存命中？
2. `reserveTokens` 为什么必须单独留，不能直接用一个百分比？

### A3. 会话树（1 天，含 PoC）

**读**：`docs/session-format.md`（15.7KB，核心中的核心）+ `packages/coding-agent/src/core/` 下的 session 实现——只读 `buildContextEntries` / `buildSessionContext` 相关函数，验证三件事：多个 compaction 时怎么取最新、`context_edit` 怎么叠加、retain-none 时 `firstKeptEntryId` 存什么。其余文件跳过。

数据结构（可直接照抄到自己项目）：

```jsonc
{"type":"session","version":3,"id":"...","timestamp":"...","cwd":"..."}
{"type":"message","id":"a1b2c3d4","parentId":null,"timestamp":"...","message":{...}}
{"type":"compaction","id":"...","parentId":"a1b2c3d4","summary":"...","firstKeptEntryId":"c3d4e5f6","tokensBefore":50000}
```

要点：
- 每条 entry 有 `id` + `parentId`，`parentId: null` 为根 → **树**
- 当前 leaf 即"现在的位置"；从旧节点新建子条目就产生分支
- 上下文重建：从 leaf 回溯到根，取**最新一条** compaction，保留 `firstKeptEntryId` 之后的条目
- `context_edit` 是 append-only 的上下文修改，可指向任意 `targetId`，`null` 表示从上下文移除
- 扩展状态走 `custom`（不进上下文）/ `custom_message`（进上下文）

**对照自己的代码**：`chat_memory.json` 是线性数组，无 id/parentId，无法回溯

**产出**：PoC——把记忆结构改成树，支持"回到第 N 轮重新提问并保留旧分支"。这是本计划最值得写进简历的一项。

**追问自测**：
1. 树结构相比"每次 fork 一个新文件"的优势是什么？
2. 压缩后原始记录还在文件里，怎么保证模型看不到它们？

### A4. 扩展机制 vs 内置能力（0.5 天）

**读**：`docs/extensions.md`（29KB，只读钩子清单 + 一个完整示例，不逐字读）

核心问题不是"怎么写扩展"，而是**为什么把权限、子 agent、plan mode 都外置**：

- 内置 = 框架替用户做决定，代价是上下文与行为不可控
- 外置 = 默认不带，用户按需装配，代价是开箱体验差

**对照自己的代码**：`agent/permissions/engine.py`（内置三级引擎）、`agent/tools/task.py`（内置子 agent）

**追问自测**：
1. 权限外置后，安全性靠什么保证？
2. 什么规模的团队适合内置、什么规模适合外置？

---

## 三、B 类：需要理解（1.5 天，只讲不写）

### B1. Codemode 的上下文经济学（0.5 天）

**读**：`packages/codemode/README.md` + `docs/codemode.md`

一句话机制：**嵌套工具调用永不进入 LLM 上下文，只有脚本的 `text()` 输出与返回值进入。**

- 模型写一段受限 JS，在 QuickJS(WASM) VM 里循环/过滤/聚合多次工具调用
- 沙箱无 `fetch`/`process`/`require`/定时器，唯一能力是注入的工具
- 与 MCP 互补而非替代：MCP 管"暴露能力"，Codemode 管"编排能力"

**验收**：能解释"读 10 个文件再汇总"在两个范式下上下文占用差多少。

### B2. Durable 全量状态外置（0.5 天）

**读**：`packages/durable` 设计文档（只读问题定义与 v2 决策两节，不读实现）

- 命题：外部效应**无法同时做到持久与恰好一次**（Provider 已扣费、文件已改、Hook 已发）
- v2 决策：把程序计数器**直接存下来**，用全量 `OperationStateRecord`，明确拒绝增量链与补丁重放
- `Lane`：一个会话挂多条执行通道，共享对话树，harness 仍是单写者

**验收**：用一句话说清"为什么全量存反而比增量链简单"。

### B3. 分层与可复用（0.5 天）

**读**：`packages/` 下各包 `package.json` 的 `dependencies`（只看依赖方向，不看代码）

**验收**：说出哪几层能单独用（`ai` / `tui` / `codemode`），以及为什么 `ai` 不能反向依赖 `agent`。

---

## 四、C 类：知道存在即可（不投入时间）

| 内容 | 一句话 |
|---|---|
| `tui` 差分渲染 | CSI 2026 同步输出 + 三策略渲染，Python 侧有 rich，不必自研 |
| `env` | Rust daemon 走 SSH，把工具执行放远端，凭据留本地 |
| `chord` | 类型化插件宿主，热重载 + 复制状态 + JSON-link RPC |
| `telemetry` | 厂商中立遥测契约 + 一致性测试 |
| 供应链加固 | 精确锁版本、`min-release-age=2`、lifecycle 白名单 |
| MCP + Codemode 整合 | MCP 已内置，Codemode 负责编排，二者互补 |

---

## 五、六天路径

| Day | 内容 | 时长 | 当日产出 |
|---|---|---|---|
| 1 | A1 · `how-pi-works` + **`types.ts` + `agent-loop.ts`** | 3h | 数据模型笔记 + loop 对照表 |
| 2 | A2 上下文装配 + skill 渐进披露 | 2h | token 占比测算 |
| 3 | A2 改造 skill 加载 | 2h | 代码改动 + 前后对比数据 |
| 4 | A3 会话格式 | 2h | 树结构设计稿（字段级） |
| 5 | A3 树 PoC | 2h | 可回溯分支的 `chat_memory` 新版 |
| 6 | A4 扩展机制 + B1/B2/B3 速览 | 2h | 3 张面试卡 |

**顺序理由**：A1 → A2 是"读+改"，A3 是"重构"，A4 与 B 类是"判断力"。先动手再谈思想，避免看完就忘。

---

## 六、产出物清单（可验收）

- [x] `docs/notes/pi-architecture-analysis.md`（已完成）
- [ ] `agent/core/memory_tree.py` 或等价改造：树形会话 + 分支回溯
- [ ] skill 渐进披露改造 + token 对比数据（进简历）
- [ ] `docs/interview/` 新增 3 张卡：loop 收敛条件 / 上下文经济学 / 状态持久化取舍
- [ ] `docs/notes/pi-vs-myagent.md`：完整对照表（现有分析文档第七节是雏形）

---

## 七、与主线的挂接

| 项 | 归属 |
|---|---|
| 对应原计划 | 第 3 周（ReAct）深化 + 第 9 周（可观测性）替代 |
| 是否新增周次 | 否，占用 6 个工作日 |
| RAG 状态 | 软暂停，Day47–48 收尾未做，恢复条件见进度记忆 |
| 面试价值 | A1/A2/A3 命中"Agent 原理"与"系统设计"两模块；B1/B2 是差异化谈资 |

---

## 八、明确不学

1. 不学写 TS 应用或扩展（除非要做 Pi package）
2. 不通读 42 篇官方文档
3. 不读 `coding-agent` 的三个大文件（`main.ts` 35KB、`package-manager-cli.ts` 37KB、`config.ts` 23KB）
4. 不读 `tui` / `env` / `chord` / `telemetry` / `evals` 的实现
5. 不 clone 全仓库（用 GitHub 单文件页按需读）
6. 不实现 Durable 状态机（规模不匹配）
7. 不自研 TUI（Python 生态已有成熟方案）
