"""
LangChain 版 Git Agent Demo

对比裸写版，看 LangChain 封装了什么
"""
import os
import sys
import warnings
warnings.filterwarnings("ignore")

from langchain_openai import ChatOpenAI
from langchain_core.tools import tool
from langchain.agents import create_tool_calling_agent, AgentExecutor
from langchain_core.prompts import ChatPromptTemplate

# ── 读 .env ──
env_file = "../.env" if not os.path.exists(".env") else ".env"
if os.path.exists(env_file):
    with open(env_file) as f:
        for line in f:
            if "=" in line and not line.startswith("#"):
                k, v = line.strip().split("=", 1)
                os.environ[k.strip()] = v.strip().strip('"').strip("'")

# ── 配置 ──
os.environ["OPENAI_API_KEY"] = os.environ.get("DEEPSEEK_API_KEY", "")
os.environ["OPENAI_BASE_URL"] = os.environ.get("DEEPSEEK_BASE_URL", "")

model = ChatOpenAI(model=os.environ.get("DEEPSEEK_MODEL", "deepseek-chat"), temperature=0)

# ── 工具定义（@tool 装饰器自动生成 JSON Schema）──
import subprocess

@tool
def get_commits(count: int = 5) -> str:
    """获取最近N条git提交记录"""
    r = subprocess.run(["git", "log", "--oneline", f"-{count}"], capture_output=True, text=True)
    return r.stdout.strip() or "(无提交记录)"

@tool
def get_diff(commit: str) -> str:
    """查看某次提交的摘要信息"""
    r = subprocess.run(["git", "show", "--stat", commit], capture_output=True, text=True)
    return r.stdout.strip() or f"git show 失败: {r.stderr}"

@tool
def read_file(file_path: str) -> str:
    """读取指定文件内容"""
    if "env" in os.path.basename(file_path):
        return "安全限制：不允许读取 .env"
    try:
        return open(file_path, encoding="utf-8").read()[:3000]
    except Exception as e:
        return f"读取失败: {e}"

# ── Agent ──
prompt = ChatPromptTemplate.from_messages([
    ("system", """你是一个 Git 仓库助手。用中文回答。
重要规则：
- 只有需要查看 git 信息时才调用工具
- 打招呼直接文字回答，不要调工具"""),
    ("human", "{input}"),
    ("placeholder", "{agent_scratchpad}")
])

agent = create_tool_calling_agent(model, [get_commits, get_diff, read_file], prompt)
executor = AgentExecutor(agent=agent, tools=[get_commits, get_diff, read_file])

print("=" * 50)
print("【LangChain Git Agent】（输入 quit 退出）")
print("=" * 50)

while True:
    user = input("\nYou: ").strip()
    if user.lower() in ("exit", "quit", "q"):
        print("Bye 👋")
        break
    if not user:
        continue

    try:
        result = executor.invoke({"input": user})
        print(f"Agent: {result['output']}")
    except KeyboardInterrupt:
        print("\nBye 👋")
        break
    except Exception as e:
        print(f"Error: {e}")