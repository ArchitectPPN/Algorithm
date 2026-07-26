# LangChain 学习计划

> 目标：用半天了解 LangChain，作为裸写 ReAct loop 的对比参考。
> 核心认知：**主线是裸写，LangChain 仅了解级**。

---

## 学习目标（按优先级）

| 目标 | 内容 | 预估时间 |
|------|------|---------|
| 1️⃣ 了解级核心概念 | 掌握 Chain/Agent/Tool/Memory 四大核心概念，能对比裸写与 LangChain 的差异 | 1 小时 |
| 2️⃣ 会用 LangChain | 跑通官方 Quick Start，能用 LangChain 构建简单 Agent | 1 小时 |
| 3️⃣ 了解 LangGraph | 知道它和 LangChain 的区别（链式 pipeline vs 状态机/图） | 30 分钟 |
| 4️⃣ 对比重写 | 用 LangChain 重写 Git Agent，深入理解框架封装了哪些步骤 | 1 小时 |

**总时间**：约 3.5 小时（半天）

---

## 学习路径

```
1. 先了解核心概念
   ↓
2. 跑通 Quick Start（动手实践）
   ↓
3. 了解 LangGraph（对比理解）
   ↓
4. 对比重写（实战）
```

---

## 官方资源

- **官网**：https://python.langchain.com/
- **核心模块**：
  - LangChain Core - 核心组件（Chain、Agent、Tool、Memory）
  - LangChain Community - 社区集成（各种 LLM Provider、Vector Store）
  - LangGraph - 基于图的 Agent 编排
  - LangServe - 部署 LangChain 应用

---

## 四大核心概念（核心）

| 概念 | 裸写对应 | LangChain 封装 | 价值 |
|------|---------|---------------|------|
| Chain | 手动串联 `prompt1 | model` | `|` 管道符 | 中等 |
| Agent | 30 行 ReAct loop | `AgentExecutor.invoke()` | **高**（核心封装） |
| Tool | 手写 JSON Schema | `@tool` 装饰器 | **高**（省事） |
| Memory | list append | `ChatMessageHistory` | 低（单进程用不到） |

---

## 关键认知

1. **LangChain 在生产正被边缘化**：抽象重、breaking change 多、调试黑盒
2. **主线是裸写**：先裸写 ReAct loop，理解每一步，再对比 LangChain
3. **LangGraph 是 LangChain 的继任者**：状态机/图 vs 链式 pipeline

---

## 学习检查点

- [ ] 能说出 LangChain 的 4 个核心概念
- [ ] 能对比裸写 vs LangChain 的差异
- [ ] 能解释 Agent 执行器是核心封装
- [ ] 能解释 Tool 装饰器为什么实用
- [ ] 能解释为什么 LangChain 生产慎用
