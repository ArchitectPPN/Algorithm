---
name: pi-learning-progress
description: Pi 源码学习进度——Day 01 完成 L1-146（含溢出讨论），下次 day02.md：L147-192 收尾 + AgentLoopConfig 前半
metadata: 
  node_type: memory
  type: project
  modified: 2026-10-25T00:00:00.000Z
---

Pi 源码学习（RAG 软暂停期主线），目标：吃下内核 + 四项结构改造 + 可演示作品与可量化数据。
计划：`docs/pi/learning-plan.md`（v3）；行号级导读：`docs/pi/reading-checklist.md`（打开代码时手边放）。

**🗓 元日 2026-10-25 = Day 01 真正起点**（学习者明确未开始过；10-23 的"Day 1/2 产出"提交是 AI 预研笔记，不计入进度）。
**节奏：每天 1.5h**（用户确认上限；总天数预计拉长到约 30 天，验收标准不变）。
**铁律：Day 完成的唯一标准 = 学习者本人的产出**（自答/骨架/笔记），AI 代做的不算。
**模式**：学习者读、AI 导读与验收。用户不会 TS——**每段源码先出 PHP 对照解读文档（dayNN-walkthrough.md），AI 开工时先生成**；每日任务单独立文件 `docs/pi/days/dayNN.md`，AI 收尾时更新勾选 + 生成次日。

**Day 01（2026-10-25）已完成 L1–146 + 溢出讨论**（任务单 `docs/pi/days/day01.md` 已勾选）：
- ✅ L1-32：import type 编译期消失 → "图纸（不可运行）/零件（可运行）"模型、协议类型外包 pi-ai
- ✅ L33-63：StreamFn 过关（换供应商 = **新增**实现 + 装配点一行；签名编译期强制）；enum = PHP backed enum 等价
- ✅ L66-146 主体：block = 批内**单个**否决（错误结果占位保协议 N 对 N）；terminate = 逐个投票**全票**才停（悲观聚合，非"最后一个说了算"）；batch/turn/loop 三层术语
- 溢出讨论（本属 Day 4-5 内容，提前建立直觉）：顺序性是全局配置（toolExecution）非调用属性、协议无依赖字段、模型用**分轮**表达依赖；并行三段式（prepare 串行 → 并发 → 按源序组装）；事件按完成序（UI）/消息按源序（LLM）双顺序；**批末一次性回模型**（N 对 N 协议硬约束）
- ❌ L147-192 未做、自测四问未做 → 顺延 day02.md

**下次（day02.md，2026-10-26）**：
1. L147-192 收尾（FinishTurn / PrepareRequest / Omit<>）——解读已备（day01-walkthrough.md 第 8、9 节）
2. 自测四问核对（Day 01 正式验收）
3. L193-274 `AgentLoopConfig` 前半——**AI 开工先出 day02-walkthrough.md**；L193-348 是十回调最密段，读法：逐个问"不给这个回调时默认行为是什么"
4. Day 02 核心产出：十回调默认行为表（前半）
之后：L274-539（消息模型/AgentState/工具契约/事件全集）→ agent-loop.ts（L1-958 按天重切）

**环境**：源码 `D:\AiAgent\pi`（v1.4.0-22-g936e5f40a，main 干净）；官方文档 44 篇在 `C:\Users\niujunqing\.npm-global\node_modules\@earendil-works\pi-coding-agent\docs\`；CLI `pi` 1.4.0 可用；Node v22.22.2。行号锚点已校验吻合。
相关：[[rag-week7-progress]]（RAG 软暂停中，Day46 代码未提交待收尾）
