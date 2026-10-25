---
name: pi-learning-progress
description: Pi 源码学习进度——2026-10-25 元日启动（执行 Day 3：agent-loop.ts 骨架），下次 Day 4-5
metadata: 
  node_type: memory
  type: project
  modified: 2026-10-25T00:00:00.000Z
---

Pi 源码学习（RAG 软暂停期主线），目标：吃下内核 + 四项结构改造 + 可演示作品与可量化数据。
计划：`docs/plans/pi-learning-plan.md`（v3）；行号级导读：`docs/plans/pi-reading-checklist.md`（打开代码时手边放）。

**🗓 元日 2026-10-25**：正式执行起算，执行 **Day 3**（此前 Day 1-2 产出为预备成果）。
**节奏：每天 1.5h**（用户确认上限，原 2h 压缩；总天数预计拉长到约 30 天，验收标准不变）。

**已完成**：
- 预备：Day 1-2 产出 ✅（10-23 提交）——types.ts（539 行）数据契约笔记、事件全集与状态归约边界

**元日任务（2026-10-25，1.5h）**：任务单 `docs/plans/pi-days/day03.md`（**每日任务单独立成文件，pi-days/dayNN.md，每天一个，AI 收尾时生成次日**）
- `how-pi-works.md`（49 行全读，约 25min）——全局组件链路
- `agent-loop.ts` **L1-171**（约 60min）——四入口层级（agentLoop L47 / agentLoopContinue L80 / runAgentLoop L111 / runAgentLoopContinue L137 / createAgentStream L162）+ 事件流出链路
- 验收（轻量）：口头回答三问——四入口谁包谁、事件怎么从 emit 流到消费方、emit 为什么允许同步异步

**下次：Day 4**（1.5h）——`agent-loop.ts` L172-300（runLoop 骨架：双层循环 / steering 双轮询 L184+L210 / turn 生命周期 / L254 错误分支 / finishTurn 终止权）
验收：默写 agent-loop 骨架 + 对照 9 月裸写 ReAct loop 列 Pi 版多出的机制（事件流/双层循环/steering）。

**Day 5+（预估）**：L305-958（follow-up、streamAssistantResponse 投影、工具执行串/并行三段式、单工具生命周期），按 1.5h/天继续重切。

**环境**：源码 `D:\AiAgent\pi`（v1.4.0-22-g936e5f40a，main 干净）；官方文档 44 篇在 `C:\Users\niujunqing\.npm-global\node_modules\@earendil-works\pi-coding-agent\docs\`；CLI `pi` 1.4.0 可用；Node v22.22.2。行号锚点元日已校验吻合。

**模式**：学习者读、AI 导读与验收（元日延续"学习者写 AI 审"的阅读线等价模式）。
相关：[[rag-week7-progress]]（RAG 软暂停中，Day46 代码未提交待收尾）
