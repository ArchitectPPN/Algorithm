"""
LangChain 调用
直接调用
"""

import os
from langchain_openai import ChatOpenAI

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
print("LangChain 直接调用 Demo")
print("=" * 60)

model_response = model.invoke("用一句话介绍 AI")
print(f"LangChain: {model_response.content}")