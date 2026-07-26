"""
ReAct demo v3：完整的 ReAct 循环（while 版）

演进：
- v1：最小原型，两轮直线调用，无容错
- v2：加容错（工具不存在/参数错误/模型不调工具），但仍只一轮
- v3：加 while 循环 + 多 tool_calls 支持 + MAX_LOOPS 保护

核心：while 模型还在返回 tool_calls → 执行工具 → 结果回灌 → 再调模型 → 直到模型不再调工具

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

# ── 2.5 容错：执行工具时的安全网 ──
def execute_tool(func_name: str, func_args: dict) -> str:
    """安全执行工具，处理三种异常：
    1. 工具不存在 → 返回错误信息给模型，让它重选
    2. 参数不对 → 返回错误信息给模型
    3. 工具执行出错 → 返回错误信息，不崩溃
    """
    if func_name not in TOOL_FUNCTIONS:
        return f"错误：没有名为 '{func_name}' 的工具。可用工具：{list(TOOL_FUNCTIONS.keys())}"

    try:
        result = TOOL_FUNCTIONS[func_name](**func_args)
    except TypeError as e:
        return f"参数错误：{e}。你传的参数是：{func_args}"
    except Exception as e:
        return f"工具执行失败：{e}"

    return result

# ── 3. 调 chat API ──
def call_model(messages):
    """调 chat API，带工具定义"""
    body = {
        "model": "deepseek-chat",
        "messages": messages,
        "tools": TOOLS,
        "temperature": 0,
    }
    resp = requests.post(URL, headers=HEADERS, json=body)
    resp.raise_for_status()
    return resp.json()

# ── 4. 主流程：ReAct 循环 ──
MAX_LOOPS = 10  # 循环保护：最多 10 轮，防止死循环

messages = [
    {"role": "user", "content": "帮我算一下：3乘以8加72除以9，结果是多少？"}
]

print("=" * 50)
print("【ReAct 循环开始】")
print("=" * 50)
print(f"用户: {messages[0]['content']}")

loop_count = 0

while loop_count < MAX_LOOPS:
    loop_count += 1
    print(f"\n{'─'*50}")
    print(f"【第 {loop_count} 轮：调 chat API】")
    print(f"{'─'*50}")

    response = call_model(messages)
    msg = response["choices"][0]["message"]

    # ── 判断：模型是调工具，还是给最终回答？──
    if not msg.get("tool_calls"):
        # 模型不再调工具 → 给出最终文字回答 → 循环结束
        print(f"\n✅ 模型给出最终回答（不再调工具）:")
        print(f"   {msg.get('content', '(无文字)')}")
        break  # ← 跳出 while 循环

    # ── 模型要调工具 → 逐个执行 ──
    # 模型一次可能返回多个 tool_calls，都要执行
    messages.append(msg)  # 先把模型的 tool_calls 回复加进历史

    for tool_call in msg["tool_calls"]:
        func_name = tool_call["function"]["name"]

        # arguments 解析容错
        try:
            func_args = json.loads(tool_call["function"]["arguments"])
        except json.JSONDecodeError:
            func_args = {}
            print(f"  ⚠️ arguments 不是合法 JSON，已降级为空参数")

        print(f"  🔧 调工具: {func_name}({func_args})")

        # 执行工具（带容错）
        result = execute_tool(func_name, func_args)
        print(f"  📋 结果: {result}")

        # 工具结果加进对话历史
        messages.append({
            "role": "tool",
            "tool_call_id": tool_call["id"],
            "content": result
        })

    # 这一轮的工具都执行完了，回到 while 开头，再调一次模型
    # 模型会看到所有工具结果，决定：继续调工具？还是给出最终回答？

else:
    # while 正常结束没触发 break → 跑满了 MAX_LOOPS 还没停
    print(f"\n⚠️ 已达最大循环次数 ({MAX_LOOPS})，强制终止")
    print(f"   模型可能在反复调工具却无法完成任务")

print(f"\n{'='*50}")
print(f"循环总轮数: {loop_count}")
print(f"{'='*50}")
print("""
ReAct 循环核心：
  while 模型还在返回 tool_calls:
      执行工具（带容错）
      结果回灌上下文
      再调模型
      → 模型不再调工具 → 输出最终回答 → break

  终止条件：模型某轮不返回 tool_calls
  安全保护：MAX_LOOPS 防死循环
  容错机制：工具不存在/参数错误 → 返回错误信息给模型 → 模型自己决定重试或放弃
""")
