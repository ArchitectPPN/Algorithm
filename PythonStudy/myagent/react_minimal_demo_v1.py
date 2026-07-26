"""
ReAct 最小 demo v1：让模型学会调用工具（Function Calling）
- 最初版本，无容错逻辑
- 两轮调用：用户提问 → 模型调工具 → 执行 → 结果回灌 → 最终回答

概念：
- 之前你调 chat API：输入文字，输出文字
- 加上 tools 参数：模型可能不再回文字，而是说"我要调 X 工具，参数是 Y"
- 你把工具执行结果再发给模型，它继续决策 → 这就是 Agent 的核心循环

依赖：requests
用法：在项目根目录 .env 里填 DEEPSEEK_API_KEY
"""
import os, requests, json, math

# ── 读 .env ──
env_file = ".env"
if os.path.exists(env_file):
    with open(env_file, encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if line.startswith("DEEPSEEK_API_KEY=") and "=" in line:
                os.environ["DEEPSEEK_API_KEY"] = line.split("=", 1)[1].strip().strip('"').strip("'")

API_KEY = os.environ.get("DEEPSEEK_API_KEY", "")
URL = "https://api.deepseek.com/chat/completions"
HEADERS = {"Authorization": f"Bearer {API_KEY}", "Content-Type": "application/json"}

if not API_KEY:
    raise SystemExit("DEEPSEEK_API_KEY 未设置，请在 .env 里填写")

# ── 1. 定义工具（JSON Schema）──
TOOLS = [
    {
        "type": "function",
        "function": {
            "name": "calculate",
            "description": "执行数学计算。支持加减乘除、乘方、开方等。",
            "parameters": {
                "type": "object",
                "properties": {
                    "expression": {
                        "type": "string",
                        "description": "数学表达式，如 '3+5*2'"
                    }
                },
                "required": ["expression"]
            }
        }
    }
]

# ── 2. 工具的真实实现 ──
def do_calculate(expression: str) -> str:
    """模型说"调 calculate"，我们就真正执行这个函数"""
    try:
        result = eval(expression, {"__builtins__": None}, math.__dict__)
        return f"计算结果：{expression} = {result}"
    except Exception as e:
        return f"计算失败：{e}"

TOOL_FUNCTIONS = {"calculate": do_calculate}

# ── 3. 调 chat API，带工具定义 ──
def call_model(messages):
    """调 chat API（就是你之前用的那个，只是多了 tools 参数"""
    body = {
        "model": "deepseek-chat",
        "messages": messages,
        "tools": TOOLS,
        "temperature": 0,
    }
    resp = requests.post(URL, headers=HEADERS, json=body)
    resp.raise_for_status()
    return resp.json()

# ── 4. 主流程 ──
messages = [
    {"role": "user", "content": "帮我算一下：3乘以8加72除以9，结果是多少？"}
]

print("=" * 50)
print("【第一轮：用户提问，看模型怎么决定】")
print("=" * 50)
print(f"用户: {messages[0]['content']}")

response = call_model(messages)
msg = response["choices"][0]["message"]

print(f"\n--- 模型原始响应（关键看有没有 tool_calls）---")
print(json.dumps(msg, ensure_ascii=False, indent=2))

if msg.get("tool_calls"):
    tool_call = msg["tool_calls"][0]
    func_name = tool_call["function"]["name"]
    func_args = json.loads(tool_call["function"]["arguments"])

    print(f"\n{'='*50}")
    print(f"【模型决定调工具】→ {func_name}")
    print(f"【参数】→ {func_args}")

    result = TOOL_FUNCTIONS[func_name](**func_args)
    print(f"【工具执行结果】→ {result}")

    messages.append(msg)
    messages.append({
        "role": "tool",
        "tool_call_id": tool_call["id"],
        "content": result
    })

    print(f"\n{'='*50}")
    print(f"【第二轮：把结果发回模型，让它给出最终回答】")
    print(f"{'='*50}")

    response2 = call_model(messages)
    final_msg = response2["choices"][0]["message"]
    print(f"\n模型最终回答: {final_msg.get('content', '(无文字)')}")
else:
    print(f"\n模型没调工具，直接回答: {msg['content']}")
