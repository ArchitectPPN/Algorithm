# Q5：LangChain 帮你做了什么 vs LLM 本身做的

**分类**：工程认知　**难度**：⭐⭐

## 考察点

考察对 LangChain 封装层次的本质理解——面试高频卡点。需要回答清楚 LangChain 的 4 个核心概念、各自封装了什么、哪些真有用哪些过度封装。

## 答题思路

### 一、LangChain 的 6 个核心封装

| 概念 | 裸写对应 | LangChain 封装 | 价值 |
|------|---------|---------------|------|
| ChatModel | requests.post | `llm.invoke()` | ⭐⭐⭐ 多厂商统一 |
| Prompt Template | f-string | `ChatPromptTemplate.from_template()` | ⭐ 过度封装 |
| Tool | 手写 JSON Schema | `@tool` 装饰器 | ⭐⭐⭐ 自动生成 schema |
| Memory | list append | `ChatMessageHistory` | ⭐ 单进程用不到 |
| Agent | 30 行 ReAct loop | `AgentExecutor.invoke()` | ⭐⭐ 核心封装但黑盒 |
| Chain/LCEL | 手动串联 | `prompt | model` | ⭐⭐ 复杂流程有用 |

### 二、最有价值的封装

**1. Tool 自动 Schema 生成**
```python
@tool
def get_commits(count: int = 5) -> str:
    """获取最近N条git提交记录"""
    # 自动从函数签名和 docstring 生成 JSON Schema
```
省去手写 40 行 JSON Schema。

**2. Agent ReAct 循环**
```python
executor = AgentExecutor(agent=agent, tools=tools)
result = executor.invoke({"input": "看看最近提交"})
```
30 行 ReAct loop → 1 行调用。但黑盒，调试困难。

### 三、过度封装的部分

- **Prompt Template**：f-string 就够了，不需要模板引擎
- **Memory**：单进程 list append 足够，LangChain 的 ChatMessageHistory 对简单场景是多余的

### 四、LangChain vs LangGraph

| | LangChain | LangGraph |
|---|-----------|-----------|
| 核心范式 | 链式 pipeline | 状态机/图 |
| 状态管理 | 隐式（消息列表） | 显式（共享 State） |
| 可调试性 | 黑盒 | 白盒 |
| 推荐程度 | 学习用 | **生产推荐** |

## 答题模板

> LangChain 封装了 6 层：模型调用、提示词模板、工具定义、记忆、Agent 循环、链式流程。
>
> 其中 **Agent 循环和工具自动 schema** 是真有价值的部分——前者封装了 ReAct loop，后者免去手写 JSON Schema。
>
> 其他几层（Prompt Template、Memory）对简单应用是过度封装，f-string 和 list append 就够了。
>
> 我学习时裸写了 ReAct loop，所以清楚框架每一步在做什么，不会停留在调包层。
>
> LangGraph 是 LangChain 的继任者，用状态机/图替代链式 pipeline，更白盒、更适合生产。

## 加分项

- 能区分哪些封装有价值、哪些过度封装
- 提到生产正被边缘化（抽象重、breaking change 多、调试黑盒）
- 对比 LangGraph（状态机 vs pipeline）

## 追问预案

**Q: 为什么 LangChain 生产慎用？**
A: 抽象层太多导致调试困难；API 不稳定 breaking change 多；黑盒执行看不到内部状态

**Q: 那为什么还要学 LangChain？**
A: 理解框架封装了什么，面试常问；LangGraph 基于 LangChain 核心概念（Chain/Agent/Tool）；快速原型验证可以用