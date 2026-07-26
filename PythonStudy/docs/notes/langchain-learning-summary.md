# LangChain 学习总结

> 今天学习内容：LangChain 四大核心概念 + Quick Start 示例 + LangChain vs LangGraph 对比

---

## 学习内容

### 1. 四大核心概念

| 概念 | 裸写对应 | LangChain | 价值 |
|------|---------|-----------|------|
| **Chain** | 手动串联 `output1 = llm.invoke() + output2 = llm.invoke()` | `chain = prompt | model` | 中等，简化流程 |
| **Tool** | 手写 JSON Schema | `@tool` 装饰器 | **高**，省去手写繁琐的 JSON Schema |
| **Agent** | 30 行 ReAct loop | `AgentExecutor.invoke()` | **高**，封装核心逻辑（但黑盒） |
| **Memory** | list append | `ChatMessageHistory` | 低，单进程用不到 |

### 2. LCEL 语法（管道流）

```python
from langchain_core.prompts import ChatPromptTemplate
from langchain_openai import ChatOpenAI

model = ChatOpenAI()
prompt = ChatPromptTemplate.from_template("回答：{question}")
chain = prompt | model  # 用 | 串联
result = chain.invoke({"question": "什么是 Agent"})
```

**核心**：用管道符 `|` 串联步骤，自动传递输出。

### 3. LangChain vs LangGraph

| 特性 | LangChain | LangGraph |
|------|-----------|-----------|
| 核心范式 | 链式 pipeline | 状态机/图 |
| 状态管理 | 隐式（消息列表） | 显式（共享 State） |
| 适用场景 | 简单流水线 | 复杂循环（Agent） |
| 可调试性 | 黑盒 | 白盒 |
| 推荐程度 | 学习用，生产慎用 | **推荐**，LangChain 的继任者 |

### 4. 核心结论

- **LangChain 在生产正被边缘化**：抽象重、breaking change 多、调试黑盒
- **主线是裸写**：先裸写 ReAct loop，理解每一步，再对比 LangChain
- **LangGraph 是 LangChain 的继任者**：状态机/图 vs 链式 pipeline

---

## 学习检查点

- [x] 能说出 LangChain 的 4 个核心概念
- [x] 能对比裸写 vs LangChain 的差异
- [x] 能解释为什么 Agent 执行器是核心封装
- [x] 能解释 Tool 装饰器为什么实用
- [x] 了解 LangGraph 与 LangChain 的区别

---

## 下一步

- 对比重写：用 LangChain 重写 Git Agent
- 进入第4周：FastAPI 入门
