# LangChain Quick Start 笔记

> 目标：半天内跑通官方 Quick Start，记住四个核心概念，对比裸写 ReAct loop。

---

## 一、四个核心概念

### 1. Chain（链）

**裸写对应**：手动串联多个步骤
```python
# 裸写
output1 = llm.invoke(prompt1)
output2 = llm.invoke(prompt2 + output1)
```

**LangChain Chain**：
```python
from langchain_core.prompts import ChatPromptTemplate
from langchain_openai import ChatOpenAI

model = ChatOpenAI()
prompt = ChatPromptTemplate.from_template("说一个关于{topic}的笑话")
chain = prompt | model
chain.invoke({"topic": "AI"})
```

**封装了什么**：用管道符 `|` 串联步骤，自动传递输出。

---

### 2. Agent（代理）

**裸写对应**：30 行 ReAct loop
```python
while True:
    response = llm.invoke(messages)
    if "tool_call" in response:
        tool_result = execute_tool(response["tool_call"])
        messages.append(tool_result)
    else:
        break
```

**LangChain Agent**：
```python
from langchain.agents import create_tool_calling_agent, AgentExecutor

tools = [...]  # 工具列表
agent = create_tool_calling_agent(model, tools, prompt)
executor = AgentExecutor(agent=agent, tools=tools)
executor.invoke({"input": "看看最近提交"})
```

**封装了什么**：整个 ReAct 循环（调模型→解析工具→执行→回灌→终止条件）。

> **评析**：最核心的封装，但也是最黑盒的。裸写 30 行能看清全貌，这里一行 `executor.invoke()` 看不见内部。

---

### 3. Tool（工具）

**裸写对应**：手写 JSON Schema + 函数
```python
TOOLS = [{
    "type": "function",
    "function": {
        "name": "get_commits",
        "description": "...",
        "parameters": {"type": "object", "properties": {...}}
    }
}]
def do_get_commits(count=5): ...
```

**LangChain Tool**：
```python
from langchain_core.tools import tool

@tool
def get_commits(count: int = 5) -> str:
    """获取最近N条git提交记录"""
    return result
```

**封装了什么**：从函数签名 + docstring 自动生成 JSON Schema。

> **评析**：非常实用的封装，省去手写繁琐的 JSON Schema。

---

### 4. Memory（记忆）

**裸写对应**：list append
```python
messages = []
messages.append({"role": "user", "content": user_input})
messages.append({"role": "assistant", "content": response})
```

**LangChain Memory**：
```python
from langchain_community.chat_message_histories import ChatMessageHistory

history = ChatMessageHistory()
history.add_user_message(user_input)
history.add_ai_message(response)
```

**封装了什么**：消息存储、读取、自动追加。

> **评析**：对单进程应用价值不大，list append 就够了。

---

## 二、LCEL 语法（管道流）

```python
from langchain_core.prompts import ChatPromptTemplate
from langchain_openai import ChatOpenAI

model = ChatOpenAI()
prompt = ChatPromptTemplate.from_template("回答：{question}")

# LCEL：用管道符串联
chain = prompt | model

# 调用
result = chain.invoke({"question": "什么是 Agent"})
```

**核心**：`prompt | model` 用 `|` 串联，自动传递输出。

---

## 三、对比总结

| 概念 | 裸写 | LangChain | 价值 |
|------|------|-----------|------|
| Chain | 手动串联 | `prompt | model` | 中等，简化流程 |
| Agent | 30 行 ReAct loop | `AgentExecutor.invoke()` | **高**，封装核心逻辑 |
| Tool | 手写 JSON Schema | `@tool` 装饰器 | **高**，省事 |
| Memory | list append | `ChatMessageHistory` | 低，单进程用不到 |

---

## 四、学会了吗？

- [ ] 能说出 LangChain 的 4 个核心概念
- [ ] 能对比裸写 vs LangChain 的差异
- [ ] 能解释为什么 Agent 执行器是核心封装
- [ ] 能解释 Tool 装饰器为什么实用

---

## 五、LangChain vs LangGraph

| 特性 | LangChain | LangGraph |
|------|-----------|-----------|
| **核心范式** | 链式 pipeline（线性流程） | 状态机/图（Node + Edge） |
| **状态管理** | 隐式（在消息列表中） | 显式（共享 State） |
| **适用场景** | 简单流水线（Prompt → Model → Output） | 复杂循环（Agent、多步骤工作流） |
| **可调试性** | 黑盒（AgentExecutor 内部看不到） | 白盒（每个 Node 明确，可中断恢复） |
| **推荐程度** | 学习用，生产慎用 | **推荐**，LangChain 的继任者 |

**核心认知**：
- LangChain 的 AgentExecutor 封装了 ReAct 循环，但黑盒重
- LangGraph 用图（StateGraph）表达 Agent，每个 Node 明确，可调试
- **主线建议**：学习用 LangChain 理解概念，生产用 LangGraph 或裸写
