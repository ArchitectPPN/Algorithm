"""
LangChain 快速上手示例（简化版）

演示四个核心概念：Chain、Agent、Tool
"""

import os
from langchain_openai import ChatOpenAI
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.tools import tool
from langchain.agents import create_tool_calling_agent, AgentExecutor

# 设置 API Key
os.environ["OPENAI_API_KEY"] = "3c44a6c98e4bdc7645bf6d4111ad5bcf:N2I2MjQ4MGQwMTMzNTYwYjczMjhkYTIx"
os.environ["OPENAI_BASE_URL"] = "https://maas-coding-api.cn-huabei-1.xf-yun.com/v2"

print("=" * 60)
print("LangChain 快速上手示例")
print("=" * 60)

# ── 1. Chain（链）示例 ──
print("\n【1. Chain 示例】")
print("-" * 40)

model = ChatOpenAI(model="xopdeepseekv4pro")
prompt = ChatPromptTemplate.from_template("用5个字描述{topic}")

# 用管道符串联
chain = prompt | model

# 调用
response = chain.invoke({"topic": "AI"})
print(f"Prompt: 用5个字描述AI")
print(f"Chain 响应: {response.content}")

# ── 2. Tool（工具）示例 ──
print("\n【2. Tool 示例】")
print("-" * 40)

@tool
def calculate(operation: str, a: int, b: int) -> int:
    """计算两个数的运算结果

    Args:
        operation: 运算类型 (add/sub/mul/div)
        a: 第一个数
        b: 第二个数
    """
    if operation == "add":
        return a + b
    elif operation == "sub":
        return a - b
    elif operation == "mul":
        return a * b
    elif operation == "div":
        return int(a / b)
    return 0

print("Tool 定义: calculate(operation, a, b)")
print("功能: 计算两个数的运算结果")
print("装饰器: @tool 自动从函数签名生成 JSON Schema")

# ── 3. Agent（代理）示例 ──
print("\n【3. Agent 示例】")
print("-" * 40)

# 创建 Agent
prompt_template = ChatPromptTemplate.from_messages([
    ("system", "你是一个数学助手。用户问你计算问题时，使用 calculate 工具。"),
    ("human", "{input}"),
    ("placeholder", "{agent_scratchpad}")
])

agent = create_tool_calling_agent(model, [calculate], prompt_template)
executor = AgentExecutor(agent=agent, tools=[calculate], verbose=True)

# 调用 Agent
print("提问: 5 加 3 等于多少？")
response = executor.invoke({"input": "5 加 3 等于多少？"})
print(f"\nAgent 响应: {response['output']}")

# ── 4. 对比总结 ──
print("\n" + "=" * 60)
print("对比总结")
print("=" * 60)

print("""
| 概念 | 裸写 | LangChain | 优势 |
|------|------|-----------|------|
| Chain | output1 = llm.invoke() + output2 = llm.invoke() | chain = prompt | model | 自动传递输出 |
| Tool | 手写 JSON Schema | @tool 装饰器 | 自动生成 schema |
| Agent | 30 行 ReAct loop | AgentExecutor.invoke() | 封装循环逻辑 |
""")

print("核心认知：")
print("- Chain: 简化流程串联")
print("- Tool: 省去手写 JSON Schema")
print("- Agent: 封装 ReAct 循环（最核心）")

print("\n" + "=" * 60)
print("LangChain 快速上手完成！")
print("=" * 60)
