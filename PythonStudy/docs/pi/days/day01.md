# Day 01 · 元日（2026-10-25 周日）+ 续日（10-27 周二）· 1.5h + 25min

> **今日主题**：`types.ts` 前半（L1–192）——供应商抽象、工具执行模式、钩子与决策类型
> **明日预告**：Day 02 = `types.ts` L193–280（`AgentLoopConfig` 前半：必填两样 + 三个可插手时刻）
> **源码**：`D:\AiAgent\pi\packages\agent\src\types.ts`（539 行）
>
> 📖 **不会 TS？先开解读文档**：[`day01-walkthrough.md`](day01-walkthrough.md)——逐段 TS 原文 + **PHP 对照代码** + 业务含义，文末附 6 道自测。
> 读法：左边源码、右边解读；先看原文自己猜，再看 PHP 对照验证。
> ⚠️ 10-27 续日只补 **任务 4（L147–192）**，不进入 Day 02。对应 walkthrough 新增的「补充深读」一节。

> ⚠️ 说明：10-23 的两篇"Day 1/Day 2 产出"提交是 **AI 预研笔记**，学习者未读、不计入进度。今天起才算数。
> `docs/pi/notes/核心机制.md` 可作参考资料——**先自己答，再看那份笔记**，它是 AI 写的，以你读到的为准。

---

## 任务 1：L1–32 扫读（10min）

文件头和 imports——看依赖哪些模块，扫一眼就行。

## 任务 2：L33–63 供应商抽象与执行模式（25min）

| 行区间 | 类型 | 过关问题 |
|--------|------|---------|
| L33–46 | `StreamFn` | **换供应商要改几处代码？**（注意它是注入式类型，不是类） |
| L47–63 | `ToolExecutionMode` / `QueueMode` / `AgentToolCall` | `parallel`/`sequential`、`all`/`one-at-a-time` 两组枚举各管什么；**为什么 `AgentToolCall` 不单独定义**（`Extract<>` 从 `AssistantMessage` 抠出来） |

## 任务 3：L66–146 钩子与决策（40min）

| 行区间 | 类型 | 过关问题 |
|--------|------|---------|
| L66–106 | `BeforeToolCallResult` / `AfterToolCallResult` | **`terminate` 出现在这里**（不是消息字段）——它属于哪一层；`AfterToolCallResult` 注释里的"不做深合并" |
| L107–146 | `BeforeToolCallContext` / `AfterToolCallContext` / `AgentTurnContext` / `AgentTurnDecision` | 钩子能拿到什么、能返回什么；**想在工具执行前拦截，用哪个钩子**；`AgentTurnDecision` 是 `continue`/`end` 的判别联合 |

## 任务 4：L147–192 一轮的边界控制（15min）

| 行区间 | 类型 | 过关问题 |
|--------|------|---------|
| L147–192 | `FinishTurn` / `AgentLoopTurnUpdate` / `PrepareRequest*` / `PrepareNextTurnContext` | **`finishTurn` 返回 `end` 会怎样**；一轮结束后能否改下一轮的模型与提示词 |

---

## 验收（5min）：过关问题答案贴给 AI 核对

四个任务各挑一问，答案直接贴——AI 对照源码逐条核对，对了打勾、错了指路。

---

## 完成清单（2026-10-27 更新）

- [x] 任务 1：L1–32 扫读 ✅ + 讨论深化：`import type` 编译期消失 → "图纸/零件"模型（图纸不可运行、协议类型外包 pi-ai）
- [x] 任务 2：L33–63 ✅ StreamFn 过关（"换供应商 = **新增**实现 + 装配点改一行，开闭原则函数式版"）；enum/Extract 已讲，`AgentToolCall` 不单独定义的自答待补
- [x] 任务 3：L66–146 主体 ✅ 讨论深度溢出到 Day 4 内容：block=批内单个否决（错误结果占位保协议 N 对 N）、terminate=逐个投票全票才停（悲观聚合）、batch/turn/loop 三层术语、顺序性是全局配置非调用属性、并行三段式（prepare 串行→并发→按源序组装）、批末一次性回模型（协议硬约束）
- [ ] 任务 4：L147–192 —— **10-27 今日补做**（不再顺延到 day02）。解读见 `day01-walkthrough.md` **「补充深读」**一节，四处易错点：
  - 补充 1：`continue` 是"保证至少一次"，不是"多发一次"
  - 补充 2：`error` / `aborted` 是硬出口，钩子管不了
  - 补充 3：`AgentLoopTurnUpdate` 是 Replacement，不是合并
  - 补充 4：`prepareRequest` vs `prepareNextTurn` 六维对照（时机 / 作用域 / messages / 队列）
- [ ] 自测 6 问（walkthrough 文末，10-27 新补第 5、6 问）→ 答完贴回核对

**今日结论沉淀**：图纸/零件区分、block 单个 / terminate 投票、"事件快、消息稳"两种顺序、协议 N 对 N
