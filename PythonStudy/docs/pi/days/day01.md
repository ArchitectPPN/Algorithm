# Day 01 · 元日（2026-10-25 周日）· 1.5h

> **今日主题**：`types.ts` 前半（L1–192）——供应商抽象、工具执行模式、钩子与决策类型
> **明日预告**：Day 02 = `types.ts` L193–539（`AgentLoopConfig` 10 回调 + 消息模型 + 工具契约 + 事件全集）
> **源码**：`D:\AiAgent\pi\packages\agent\src\types.ts`（539 行）
>
> 📖 **不会 TS？先开解读文档**：[`day01-walkthrough.md`](day01-walkthrough.md)——逐段 TS 原文 + **PHP 对照代码** + 业务含义，文末附 4 道自测。
> 读法：左边源码、右边解读；先看原文自己猜，再看 PHP 对照验证。

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

## 完成清单

- [ ] 任务 1-4 读完，过关问题已作答
- [ ] AI 核对通过
- [ ] AI 核对后：更新本文件勾选 + 生成 `day02.md`（L193–539：`AgentLoopConfig` 十回调是全文件最密段）
