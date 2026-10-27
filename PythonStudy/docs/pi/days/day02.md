# Day 02（2026-10-28 周三 · 1.5h）

> **今日主题**：`types.ts` L193–280——`AgentLoopConfig` 前半（必填两样 + 三个可插手时刻）
> **源码**：`D:\AiAgent\pi\packages\agent\src\types.ts`（539 行）
> **承接**：Day 01 已于 10-27 收口（L1–192 全部走完，含补充深读）。今天从 L193 起。
>
> 📖 **解读文档**：[`day02-walkthrough.md`](day02-walkthrough.md)——逐段 TS 原文 + PHP 对照 + 默认行为表，文末 5 道自测。

---

## 任务 1：L193–222 `model` + `convertToLlm`（35min）

| 行区间 | 内容 | 过关问题 |
|--------|------|---------|
| L193–194 | 接口声明 + `model` | **整个接口只有两个必填字段，另一个是谁？为什么偏偏是这两个？** |
| L196–222 | `convertToLlm` | **投影发生在哪一步？"must not throw" 为什么不是"报个错而已"？** |
| 注释示例 | `flatMap` 的 1→0 / 1→1 | 投影与"类型转换"的差别在哪里 |

## 任务 2：L224–254 `transformContext` + `getApiKey`（35min）

| 行区间 | 内容 | 过关问题 |
|--------|------|---------|
| L224–244 | `transformContext` | **为什么可选却强制 `Promise`？为什么必须在 `convertToLlm` 之前？** |
| L246–254 | `getApiKey` | **为什么每次请求都要重新取，而不是启动时取一次？** |
| 偷看 L397–410 | 四步装配实证 | `transformContext → convertToLlm → normalizeContext → getApiKey` 的顺序能否默写 |

> 这两节是最能直接搬到 `agent/` 项目里的：`transformContext` 对应 `context_manager.py` 的挂载点，`getApiKey` 对应长跑任务的凭据刷新。

## 任务 3：L256–280 三个钩子 + 默认行为表（20min）

| 行区间 | 内容 | 过关问题 |
|--------|------|---------|
| L256–264 | `finishTurn` | `{action:"end"}` 为什么是"真停"（不轮询队列） |
| L266–271 | `prepareRequest` | *including the first* / *this and later requests* / *does not poll queues* 三句各约束什么 |
| L273–280 | `prepareNextTurn` | 它比 `prepareRequest` 多的那个能力是什么 |
| — | 产出 | 填写 walkthrough 第 9 节那张 7 行默认行为表（**不看原文填**） |

---

## 完成清单

- [ ] 任务 1：L193–222
- [ ] 任务 2：L224–254（含 L397–410 顺序默写）
- [ ] 任务 3：L256–280 + 7 行默认行为表
- [ ] 自测 5 问贴回来核对
- [ ] AI 收尾：更新勾选 + 生成 `day03.md`（L282–348：`getSteeringMessages` / `getFollowUpMessages` / `toolExecution` / `beforeToolCall` / `afterToolCall`）
