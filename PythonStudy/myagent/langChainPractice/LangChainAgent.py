"""
LangChain Agent Demo
"""

import os

from langchain_openai import ChatOpenAI
from langchain_core.tools import tool
from langchain.agents import create_agent

# 读取文件（基于脚本所在目录定位 .env，不受运行目录影响）
_script_dir = os.path.dirname(os.path.abspath(__file__))
env_file = os.path.join(_script_dir, "..", "..", ".env")
if not os.path.exists(env_file):
    env_file = os.path.join(_script_dir, ".env")
if os.path.exists(env_file):
    with open(env_file) as f:
        for line in f:
            if "=" in line and not line.startswith("#"):
                k, v = line.strip().split("=", 1)
                os.environ[k.strip()] = v.strip().strip('"').strip("'")

# 设置环境变量
os.environ["OPENAI_API_KEY"] = os.environ.get("DEEPSEEK_API_KEY", "")
os.environ["OPENAI_BASE_URL"] = os.environ.get("DEEPSEEK_BASE_URL", "")

# 创建agent实例
model = ChatOpenAI(model=os.environ.get("DEEPSEEK_MODEL", "deepseek-chat"), temperature=0)

print("=" * 60)
print("LangChain Agent Demo")
print("=" * 60)

# ══════════════════════════════════════════════════════════
# 3. Agent 调用（自动决策 + 工具调用）
# ══════════════════════════════════════════════════════════

# 定义方法
@tool
def calculate(expression: str) -> str:
    """计算数学表达式，如 '2+3'"""
    try:
        return str(eval(expression))
    except:
        return "计算失败"

agent = create_agent(
    model=model,
    tools=[calculate],
    system_prompt="你是一个聪明的助手，能够根据用户的需求调用工具进行计算。"
)

result = agent.invoke({"messages":[("user", "请计算 12 * 8")]} )
last_message = result["messages"][-1]
print(f"Agent: {last_message.content}")