"""
LangChain 三种调用方式 Demo

对比学习：直接调、Chain 调、Agent 调
"""
import os
import warnings
warnings.filterwarnings("ignore")

from langchain_openai import ChatOpenAI
from langchain_core.tools import tool
from langchain_core.prompts import ChatPromptTemplate
from langchain.agents import create_tool_calling_agent, AgentExecutor

# ── 读 .env ──
env_file = "../../.env" if not os.path.exists(".env") else ".env"
if os.path.exists(env_file):
    with open(env_file) as f:
        for line in f:
            if "=" in line and not line.startswith("#"):
                k, v = line.strip().split("=", 1)
                os.environ[k.strip()] = v.strip().strip('"').strip("'")

os.environ["OPENAI_API_KEY"] = os.environ.get("DEEPSEEK_API_KEY", "")
os.environ["OPENAI_BASE_URL"] = os.environ.get("DEEPSEEK_BASE_URL", "")

# 共享的模型实例
model = ChatOpenAI(model=os.environ.get("DEEPSEEK_MODEL", "deepseek-chat"), temperature=0)

print("=" * 60)
print("LangChain 三种调用方式 Demo")
print("=" * 60)

# ══════════════════════════════════════════════════════════
# 1. 直接调用（等同于裸写的 requests.post）
# ══════════════════════════════════════════════════════════
print("\n【1. 直接调用】")
print("-" * 40)

# LangChain 版：一行
response = model.invoke("用一句话介绍 AI")
print(f"LangChain: {response.content}")

# 裸写版等价于：
# resp = requests.post(url, headers, json={"model": "...", "messages": [{"role": "user", "content": "用一句话介绍 AI"}]})
# print(resp.json()["choices"][0]["message"]["content"])

# ══════════════════════════════════════════════════════════
# 2. Chain 调用（固定流程串联）
# ══════════════════════════════════════════════════════════
print("\n【2. Chain 调用】")
print("-" * 40)

prompt = ChatPromptTemplate.from_template("用 {style} 的风格介绍 {topic}")
chain = prompt | model  # 管道符串联：先填模板，再调模型

result = chain.invoke({"style": "幽默", "topic": "Python"})
print(f"Chain: {result.content}")

# 裸写版等价于：
# prompt_text = f"用 幽默 的风格介绍 Python"
# resp = requests.post(url, headers, json={"messages": [{"role": "user", "content": prompt_text}]})

# ══════════════════════════════════════════════════════════
# 3. Agent 调用（自动决策 + 工具调用）
# ══════════════════════════════════════════════════════════
print("\n【3. Agent 调用】")
print("-" * 40)

@tool
def calculate(expression: str) -> str:
    """计算数学表达式，如 '2+3'"""
    try:
        return str(eval(expression))
    except:
        return "计算失败"

agent_prompt = ChatPromptTemplate.from_messages([
    ("system", "你是一个数学助手。用户问计算问题时使用 calculate 工具。"),
    ("human", "{input}"),
    ("placeholder", "{agent_scratchpad}")
])

agent = create_tool_calling_agent(model, [calculate], agent_prompt)
executor = AgentExecutor(agent=agent, tools=[calculate])

result = executor.invoke({"input": "3 乘以 15 等于多少？"})
print(f"Agent: {result['output']}")

# 裸写版等价于 30 行 ReAct loop：
# while True:
#     resp = call_model(messages)
#     if no tool_calls: break
#     execute_tool(...)
#     messages.append(tool_result)

print("\n" + "=" * 60)
print("三种方式总结")
print("=" * 60)
print("""
直接调用: model.invoke() → 等同于 requests.post
Chain 调用: prompt | model → 固定流程串联
Agent 调用: executor.invoke() → 自动决策+工具调用

本质都是封装的 requests.post()，只是封装层次不同。
""")