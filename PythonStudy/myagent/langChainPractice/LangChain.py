"""
LangChain 调用
"""

import os
from langchain_core.prompts import ChatPromptTemplate
from langchain_openai import ChatOpenAI

# 读取配置文件
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

model = ChatOpenAI(model=os.environ.get("DEEPSEEK_MODEL", "deepseek-chat"), temperature=0)

# 创建agent实例
prompt = ChatPromptTemplate.from_template("用 {style} 的风格介绍 {topic}")
chain = prompt | model  # 管道符串联：先填模板，再调模型

print("开始 Chain 调用...")
result = chain.invoke({"style": "幽默", "topic": "Python"})
print("=" * 60)
print("LangChain Chain 调用 Demo")
print("=" * 60)
print(f"Chain: {result.content}")
print("结束 Chain 调用...")