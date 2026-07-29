import os
import warnings
warnings.filterwarnings("ignore")

from langchain_openai import ChatOpenAI
from langchain.agents import create_tool_calling_agent, AgentExecutor
from langchain_core.tools import tool
from langchain_core.prompts import ChatPromptTemplate

# 读取配置
env_file = "../../.env" if not os.path.exists(".env") else ".env"
if os.path.exists(env_file):
    with open(env_file) as f:
        for line in f:
            if "=" in line and not line.startswith("#"):
                k, v = line.strip().split("=", 1)
                os.environ[k.strip()] = v.strip().strip('"').strip("'")

os.environ["OPENAI_API_KEY"] = os.environ.get("DEEPSEEK_API_KEY", "")
os.environ["OPENAI_BASE_URL"] = os.environ.get("DEEPSEEK_BASE_URL", "")

# 1. 模型
model = ChatOpenAI(model="xopglm51", temperature=0)

# 2. 工具
@tool
def search(query: str) -> str:
    """Search for information"""
    return f"Search Result: {query}"

# 3. Agent + Executor
prompt = ChatPromptTemplate.from_messages([
    ("system", "You are a helpful assistant."),
    ("human", "{input}"),
    ("placeholder", "{agent_scratchpad}")
])

agent = create_tool_calling_agent(model, [search], prompt)
executor = AgentExecutor(agent=agent, tools=[search])

result = executor.invoke({"input": "What's the weather in San Francisco?"})
print(result['output'])