# LangChain 了解级笔记

> 学习目标：用半天了解 LangChain，回答面试高频卡点——**"LangChain 帮你做了什么 vs LLM 本身做的"**。
> 学习方式：先梳理概念，再用 LangChain 重写 Git Agent 对比裸写版本。

---

## 一、LangChain 是什么

一句话：**LangChain 是 LLM 应用的脚手架框架**，把 LLM 应用里反复出现的模式（调模型、接工具、存历史、串流程）封装成现成组件，让你少写胶水代码。

类比：
- 裸写 = 原生 PHP；LangChain = Laravel
- Laravel 帮你封装了路由、ORM、中间件；LangChain 帮你封装了 LLM 调用、工具调用、记忆管理、链式流程

**生产现状**：LangChain 在生产正被边缘化——抽象重、breaking change 多、调试黑盒。学习价值在于**理解封装了什么**，而非直接上生产。主线仍裸写 + LangGraph。

---

## 二、LangChain 的 6 个核心概念

对应用层 Agent 开发，只需要搞清楚这 6 个。每个概念都对照裸写版本看。

### 1. ChatModel（模型封装）

**裸写**：
```python
resp = requests.post(url, headers=headers, json={"model": "...", "messages": [...]})
message = resp.json()["choices"][0]["message"]
```

**LangChain**：
```python
from langchain_openai import ChatOpenAI
llm = ChatOpenAI(model="gpt-4o-mini", api_key="...")
response = llm.invoke("你好")  # 直接返回，不用管 HTTP
```

**封装了什么**：HTTP 请求、响应解析、错误处理、API 差异。

> **评析**：⭐⭐⭐ 有用，但我们的 `LLMAdapter` 已经实现了同样的事（统一 OpenAI/Anthropic）。LangChain 的 ChatModel 本质就是个适配器。

### 2. Prompt Template（提示词模板）

**裸写**：
```python
prompt = f"请分析以下代码：{code}\n关注：{focus}"
```

**LangChain**：
```python
from langchain_core.prompts import ChatPromptTemplate
prompt = ChatPromptTemplate.from_template("请分析以下代码：{code}\n关注：{focus}")
final = prompt.invoke({"code": code, "focus": focus})
```

**封装了什么**：变量插值、多语言模板、模板复用。

> **评析**：⭐ 过度封装。f-string 就够了，这个封装价值不大。

### 3. Tool（工具定义）

**裸写**（Git Agent 里的写法）：
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

**LangChain**：
```python
from langchain_core.tools import tool

@tool
def get_commits(count: int = 5) -> str:
    """获取最近N条git提交记录"""
    # 实现逻辑
    return result
```

**封装了什么**：从 Python 函数签名 + docstring **自动生成 JSON Schema**，不用手写 TOOLS 字典。

> **评析**：⭐⭐⭐ 有价值。手写 JSON Schema 繁琐易错，装饰器自动生成省事。这是 LangChain 真正省力的地方之一。

### 4. Memory（记忆/对话历史）

**裸写**（Git Agent 里的写法）：
```python
messages = []
messages.append({"role": "user", "content": user_input})
messages.append(response.message)
```

**LangChain**：
```python
from langchain_community.chat_message_histories import ChatMessageHistory
history = ChatMessageHistory()
history.add_user_message(user_input)
history.add_ai_message(response.content)
```

**封装了什么**：消息存储、读取、自动追加。

> **评析**：⭐ 单进程应用几乎没用，list append 就行。对跨进程/分布式存储（Redis、数据库）才有意义。

### 5. Agent + AgentExecutor（Agent 循环）

**裸写**（Git Agent 里的 ReAct loop）：
```python
while loop_count < MAX_LOOPS:
    response = call_model(messages)
    msg = response.message
    if not msg.get("tool_calls"):
        print(msg["content"])
        break
    messages.append(msg)
    for tool_call in msg["tool_calls"]:
        result = execute_tool(...)
        messages.append({"role": "tool", ...})
```

**LangChain**：
```python
from langchain.agents import create_tool_calling_agent, AgentExecutor

agent = create_tool_calling_agent(llm, tools, prompt)
executor = AgentExecutor(agent=agent, tools=tools)
result = executor.invoke({"input": "看看最近提交"})
```

**封装了什么**：整个 ReAct 循环（调模型 → 解析工具调用 → 执行 → 回灌 → 再调 → 终止条件）。

> **评析**：⭐⭐ 最有价值，但也最黑盒。裸写的 ReAct loop 30 行能看清全貌，LangChain 一行 `executor.invoke()` 看不见内部发生了什么——这正是学习时裸写的意义。

### 6. Chain / LCEL（链式流程）

**裸写**：手动串联
```python
output1 = call_model(prompt1)
output2 = call_model(prompt2 + output1)
```

**LangChain LCEL**：
```python
chain = prompt1 | llm | parser | prompt2 | llm
result = chain.invoke({"input": "..."})
```

**封装了什么**：用管道符 `|` 串联步骤，支持流式、批处理、异步。

> **评析**：⭐⭐ 简单流程过度设计，复杂多步流程（RAG、map-reduce）有价值。

---

## 三、对比总表

| 概念 | 裸写对应 | LangChain 封装价值 | 评价 |
|------|---------|-------------------|------|
| ChatModel | requests.post | ⭐⭐⭐ 省心，多厂商统一 | 有用，但 LLMAdapter 已实现 |
| Prompt Template | f-string | ⭐ 过度封装 | 几乎没用 |
| Tool | 手写 JSON Schema + 函数 | ⭐⭐⭐ 自动生成 schema | 有用，省去手写 schema |
| Memory | list append | ⭐ 单进程没用 | 几乎没用 |
| Agent/Executor | 30 行 ReAct loop | ⭐⭐ 最有价值但也最黑盒 | 学习用裸写，生产可考虑 |
| Chain/LCEL | 手动串联 | ⭐⭐ 复杂流程有用 | 简单场景过度设计 |

---

## 四、核心结论

**面试时这样答**：

> LangChain 封装了 6 层：模型调用、提示词模板、工具定义、记忆、Agent 循环、链式流程。其中 **Agent 循环和工具自动 schema** 是真有价值的部分——前者封装了 ReAct loop，后者免去手写 JSON Schema。其他几层（Prompt Template、Memory）对简单应用是过度封装，f-string 和 list append 就够了。我学习时裸写了 ReAct loop，所以清楚框架每一步在做什么，不会停留在调包层。

**生产决策**：
- 学习阶段：裸写，理解每一步
- 简单 Agent：裸写 + LLMAdapter，足够
- 复杂多步流程（RAG、map-reduce）：考虑 LangChain LCEL 或 LangGraph
- LangChain 的 AgentExecutor：黑盒重，调试难，生产慎用

---

## 五、待补充

- [ ] 用 LangChain 重写 Git Agent，代码对比
- [ ] LangChain vs LangGraph 的区别（第7周末入门）
