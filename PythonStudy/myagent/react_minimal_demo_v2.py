"""
ReAct 最小 demo：让模型学会调用工具（Function Calling）

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
                os.environ["DEEPSEEK_API_KEY"] = line.split("=", 1)[1].strip().strip('"').strip("'")  # 强制覆盖，不用 setdefault

API_KEY = os.environ.get("DEEPSEEK_API_KEY", "")
URL = "https://api.deepseek.com/chat/completions"
HEADERS = {"Authorization": f"Bearer {API_KEY}", "Content-Type": "application/json"}

if not API_KEY:
    raise SystemExit("DEEPSEEK_API_KEY 未设置，请在 .env 里填写")

# ── 1. 定义工具（JSON Schema）──
# 告诉模型：你有这些工具可以用，每个工具叫什么、干什么、需要什么参数
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

# ── 2.5 容错：执行工具时的安全网 ──
def execute_tool(func_name: str, func_args: dict) -> str:
    """安全执行工具，处理三种异常情况：
    1. 工具不存在 → 返回错误信息给模型，让它重选
    2. 参数不对（缺必填字段/类型错误）→ 返回错误信息给模型
    3. 工具执行本身出错 → 返回错误信息，不崩溃
    """
    # 漏洞1修复：工具不存在
    if func_name not in TOOL_FUNCTIONS:
        return f"错误：没有名为 '{func_name}' 的工具。可用工具：{list(TOOL_FUNCTIONS.keys())}"

    # 漏洞2修复：参数校验
    try:
        result = TOOL_FUNCTIONS[func_name](**func_args)
    except TypeError as e:
        # 参数不匹配（缺必填参数、多了参数等）
        return f"参数错误：{e}。你传的参数是：{func_args}"
    except Exception as e:
        # 漏洞3修复：工具执行本身出错
        return f"工具执行失败：{e}"

    return result
# ── 3. 调 chat API，带工具定义 ──
def call_model(messages):
    """调 chat API（就是你之前用的那个，只是多了 tools 参数"""
    body = {
        "model": "deepseek-chat",
        "messages": messages,
        "tools": TOOLS,               # ← 这是关键！把工具告诉模型
        "temperature": 0,
    }
    resp = requests.post(URL, headers=HEADERS, json=body)
    resp.raise_for_status()
    return resp.json()
# ── 4. 主流程：一次"模型决定调工具 → 执行 → 再发回"的完整循环 ──
messages = [
    {"role": "user", "content": "帮我算一下：3乘以8加72除以9，结果是多少？"}
]

print("=" * 50)
print("【第一轮：用户提问，看模型怎么决定】")
print("=" * 50)
print(f"用户: {messages[0]['content']}")

response = call_model(messages)
msg = response["choices"][0]["message"]

# 打印模型完整响应
print(f"\n--- 模型原始响应（关键看有没有 tool_calls）---")
print(json.dumps(msg, ensure_ascii=False, indent=2))

# ── 看模型是回答文字，还是想调工具 ──
if msg.get("tool_calls"):
    # 模型说：我不要直接回答，我要调工具！
    tool_call = msg["tool_calls"][0]
    func_name = tool_call["function"]["name"]

    # 漏洞2修复：arguments 解析容错
    try:
        func_args = json.loads(tool_call["function"]["arguments"])
    except json.JSONDecodeError:
        func_args = {}
        print(f"  ⚠️ 模型返回的 arguments 不是合法 JSON，已降级为空参数")

    print(f"\n{'='*50}")
    print(f"【模型决定调工具】→ {func_name}")
    print(f"【参数】→ {func_args}")

    # 真正执行工具（带容错，不再直接 TOOL_FUNCTIONS[func_name](**func_args)）
    result = execute_tool(func_name, func_args)
    print(f"【工具执行结果】→ {result}")

    # 把工具调用和结果都加进对话历史，再发给模型
    messages.append(msg)  # 模型的 tool_calls 回复
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
    # 漏洞3修复：模型没调工具，直接回答了文字
    # 可能是合理的（问题不需要工具），也可能是模型"偷懒"该调没调
    print(f"\n⚠️ 模型没调工具，直接回答: {msg['content']}")
    print("  如果答案不准，可能需要在 prompt 里强调'必须使用工具'")

print(f"\n{'='*50}")
print("关键理解：模型不是我们让它调工具，是它自己决定的")
print(f"{'='*50}")
print("""
发生了什么：
1. 我们在请求里放了 tools（工具定义），告诉模型"你可以用这个计算器"
2. 模型读到了用户说"算一下"，它自己决定：我自己算容易出错，应该用那个计算器工具
3. 于是它返回 tool_calls（而不是文字），把表达式 "3*8+72/9" 填进了参数
4. 我们执行计算器，把结果发回给模型
5. 模型拿到结果，用自然语言告诉用户答案

← 这就是 ReAct 循环的最小原型
   下一课：加上循环，让模型能连续调多个工具
""")
