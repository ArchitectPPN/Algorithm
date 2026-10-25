---
name: pi-learning-progress
description: Pi 源码学习进度——2026-10-25 元日启动（执行 Day 3：agent-loop.ts 骨架），下次 Day 4-5
metadata: 
  node_type: memory
  type: project
  modified: 2026-10-25T00:00:00.000Z
---

Pi 源码学习（RAG 软暂停期主线），目标：吃下内核 + 四项结构改造 + 可演示作品与可量化数据。
计划：`docs/pi/learning-plan.md`（v3）；行号级导读：`docs/pi/reading-checklist.md`（打开代码时手边放）。

**🗓 元日 2026-10-25 = Day 01 真正起点**（学习者明确：未开始过；10-23 的"Day 1/2 产出"提交是 AI 预研笔记，不计入进度）。
**节奏：每天 1.5h**（用户确认上限，原 2h 压缩；总天数预计拉长到约 30 天，验收标准不变）。
**铁律：Day 完成的唯一标准 = 学习者本人的产出**（自答/骨架/笔记），AI 代做的不算——Week7 教训，Day 01 就拦住。

**已完成**：
- 无（元日即 Day 01 起点；AI 预研笔记 `docs/pi/notes/核心机制.md` 降级为参考资料）

**元日任务（2026-10-25，1.5h）**：任务单 `docs/pi/days/day01.md`（**每日任务单独立成文件 pi-days/dayNN.md，AI 收尾时生成次日**）
- `types.ts` **L1–192**（前半）：L33-63 供应商抽象（StreamFn/执行模式枚举）→ L66-146 钩子与决策（terminate 归属/判别联合）→ L147-192 一轮边界（finishTurn 返回 end）
- 验收：过关问题答案贴给 AI，对照源码逐条核对

**下次：Day 4**（1.5h）——`agent-loop.ts` L172-300（runLoop 骨架：双层循环 / steering 双轮询 L184+L210 / turn 生命周期 / L254 错误分支 / finishTurn 终止权）
验收：默写 agent-loop 骨架 + 对照 9 月裸写 ReAct loop 列 Pi 版多出的机制（事件流/双层循环/steering）。

**Day 5+（预估）**：L305-958（follow-up、streamAssistantResponse 投影、工具执行串/并行三段式、单工具生命周期），按 1.5h/天继续重切。

**环境**：源码 `D:\AiAgent\pi`（v1.4.0-22-g936e5f40a，main 干净）；官方文档 44 篇在 `C:\Users\niujunqing\.npm-global\node_modules\@earendil-works\pi-coding-agent\docs\`；CLI `pi` 1.4.0 可用；Node v22.22.2。行号锚点元日已校验吻合。

**模式**：学习者读、AI 导读与验收（元日延续"学习者写 AI 审"的阅读线等价模式）。
相关：[[rag-week7-progress]]（RAG 软暂停中，Day46 代码未提交待收尾）
