# Day 3 · 元日（2026-10-25 周日）· 1.5h

> **今日主题**：全局心智图 + agent-loop.ts 入口与事件流
> **明日预告**：Day 4 = `agent-loop.ts` L172–300（runLoop 骨架：双层循环 / steering 双轮询）+ 默写骨架验收
> **源码**：`D:\AiAgent\pi`（v1.4.0-22，行号锚点已于元日校验）

---

## 任务 1：how-pi-works.md 全读（25min）

**路径**：`C:\Users\niujunqing\.npm-global\node_modules\@earendil-works\pi-coding-agent\docs\how-pi-works.md`（49 行）

**看完应知道**：一次 prompt 从用户输入到 `agent_end` 流经哪些组件（CLI → Agent → agent-loop → provider → 工具），以及你 Day 1-2 读的 `types.ts` 那些类型在这条链上的位置。

---

## 任务 2：agent-loop.ts L1–171（60min）

**文件**：`D:\AiAgent\pi\packages\agent\src\agent-loop.ts`（959 行，Day 3-5 读完）

| 行区间 | 方法/位置 | 过关问题 |
|--------|----------|---------|
| L1–46 | imports / `AgentEventSink` / `DEFAULT_AGENT_IDENTITY` | `emit` 类型 `Promise<void> \| void`——事件消费方可以是同步的吗？ |
| L47–79 | `agentLoop` | 这个薄封装只做参数归一 |
| L80–110 | `agentLoopContinue` | 续跑版和初跑版差在哪 |
| L111–171 | `runAgentLoop`(111) / `runAgentLoopContinue`(137) / `createAgentStream`(162) | 四个入口的层级关系（谁包谁）；**事件是怎么从 `emit` 流到消费方的** |

---

## 验收（5min）：三问贴答案给 AI 核对

1. 四个入口函数谁包谁？
2. 事件从 `emit` 到消费方的链路是什么？
3. `emit` 类型 `Promise<void> | void` 意味着什么？

---

## 带着读的问题（连接 9 月裸写版）

Pi 的 `agentLoop` 是你 9 月裸写 ReAct loop（`git_agent_practice.py`）的生产级实现。今天这段先留意：**入口封装和事件流**这两层，你的裸写版完全没有——明天读 `runLoop` 会看到缺得更多（双层循环、steering）。

---

## 完成清单

- [ ] 任务 1 读完
- [ ] 任务 2 读完
- [ ] 三问答案已贴给 AI 核对
- [ ] AI 核对后：更新本文件勾选 + 生成 `day04.md`
