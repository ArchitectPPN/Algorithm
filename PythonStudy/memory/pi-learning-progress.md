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

**已完成**：
- 预备：Day 1-2 产出 ✅（10-23 提交）——types.ts（539 行）数据契约笔记、事件全集与状态归约边界
- 元日任务（2026-10-25）：`how-pi-works.md`（49 行全读）+ `agent-loop.ts` L1-300
  （入口三封装 → EventStream 事件流 → runLoop 初始化 → 双层循环 → steering 双轮询 → turn 生命周期 → L254 错误/中止分支）
  验收：默写 agent-loop 骨架 + 对照 9 月裸写 ReAct loop（git_agent_practice.py / rag_agent_practice.py）列出 Pi 版多出的机制（事件流/双层循环/steering）

**下次：Day 4-5**——`agent-loop.ts` L305-958
（内层退出/follow-up、工具变更 declareToolChanges、streamAssistantResponse L390-487 投影调用点、截断作废 L488-517、工具执行串/并行三段式 L518-671、悲观聚合 L700-717、单工具生命周期 L801-921）
验收：串行 vs 并行三段式的取舍、23 处 emit 里唯一不 await 的是哪个、为什么。

**环境**：源码 `D:\AiAgent\pi`（v1.4.0-22-g936e5f40a，main 干净）；官方文档 44 篇在 `C:\Users\niujunqing\.npm-global\node_modules\@earendil-works\pi-coding-agent\docs\`；CLI `pi` 1.4.0 可用；Node v22.22.2。行号锚点元日已校验吻合。

**模式**：学习者读、AI 导读与验收（元日延续"学习者写 AI 审"的阅读线等价模式）。
相关：[[rag-week7-progress]]（RAG 软暂停中，Day46 代码未提交待收尾）
