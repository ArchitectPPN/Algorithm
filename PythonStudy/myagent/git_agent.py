"""
Git Agent：裸写 ReAct loop + git 工具

工具：
1. get_commits  → git log --oneline -N（看最近提交列表）
2. get_diff     → git show --stat <commit>（看某次提交改了哪些文件）
3. read_file    → Python open()（读文件内容）

依赖：requests
用法：在项目根目录 .env 里填 DEEPSEEK_API_KEY
"""
import os
import requests
import json
import subprocess

DASH = "-" * 50

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
            "name": "get_commits",
            "description": "获取最近N条git提交记录，每条包含commit hash和提交说明。",
            "parameters": {
                "type": "object",
                "properties": {
                    "count": {
                        "type": "integer",
                        "description": "要查看的提交数量，默认5"
                    }
                },
                "required": []
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "get_diff",
            "description": "查看某次提交的摘要信息：谁提交的、什么时候、改了哪些文件、每个文件增删几行。",
            "parameters": {
                "type": "object",
                "properties": {
                    "commit": {
                        "type": "string",
                        "description": "commit hash（如 'ad2132e'）或 'HEAD'、'HEAD~1' 等"
                    }
                },
                "required": ["commit"]
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "read_file",
            "description": "读取指定文件的完整内容。",
            "parameters": {
                "type": "object",
                "properties": {
                    "file_path": {
                        "type": "string",
                        "description": "要读取的文件路径，如 'list.py' 或 'src/main.py'"
                    }
                },
                "required": ["file_path"]
            }
        }
    }
]

# ── 2. 工具的真实实现 ──
def do_get_commits(count: int = 5) -> str:
    """获取最近N条提交记录"""
    try:
        result = subprocess.run(
            ["git", "log", "--oneline", f"-{count}"],
            capture_output=True, text=True, cwd=os.getcwd()
        )
        if result.returncode != 0:
            return f"git log 执行失败：{result.stderr}"
        return result.stdout.strip() or "(无提交记录)"
    except Exception as e:
        return f"执行出错：{e}"

def do_get_diff(commit: str) -> str:
    """查看某次提交的摘要信息（--stat，不要diff代码内容）"""
    try:
        result = subprocess.run(
            ["git", "show", "--stat", commit],
            capture_output=True, text=True, cwd=os.getcwd()
        )
        if result.returncode != 0:
            return f"git show --stat 执行失败：{result.stderr}"
        return result.stdout.strip()
    except Exception as e:
        return f"执行出错：{e}"

def do_read_file(file_path: str) -> str:
    """读取文件内容"""
    # 安全检查：不允许读 .env（含密钥）
    if file_path.endswith(".env") or "env" in os.path.basename(file_path):
        return "安全限制：不允许读取 .env 文件（可能包含密钥）"
    try:
        with open(file_path, encoding="utf-8") as f:
            content = f.read()
        # 限制返回长度，避免撑爆上下文
        max_chars = 3000
        if len(content) > max_chars:
            return content[:max_chars] + f"\n\n... (文件过长，已截断，完整文件共 {len(content)} 字符)"
        return content
    except FileNotFoundError:
        return f"文件不存在：{file_path}"
    except Exception as e:
        return f"读取失败：{e}"

TOOL_FUNCTIONS = {
    "get_commits": do_get_commits,
    "get_diff": do_get_diff,
    "read_file": do_read_file,
}

# ── 2.5 容错 ──
def execute_tool(func_name: str, func_args: dict) -> str:
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
    body = {
        "model": "deepseek-chat",
        "messages": messages,
        "tools": TOOLS,
        "temperature": 0,
    }
    resp = requests.post(URL, headers=HEADERS, json=body)
    resp.raise_for_status()
    return resp.json()

# ── 4. 主流程：多轮对话循环 ──
# 外层 while True：反复读用户输入，带上下文继续聊
# 内层 while：ReAct 循环（处理单个问题，可能调多个工具）
# 关键：messages 跨外层轮次保留，模型能看到之前聊过什么

MAX_LOOPS = 10  # 单个问题的 ReAct 循环上限

messages = []  # 跨轮保留的对话历史

# system prompt：约束模型行为，告诉它什么时候该用工具
SYSTEM_PROMPT = """你是一个 Git 仓库助手。你可以帮助用户查看 git 提交记录、提交详情、文件内容等。

重要规则：
- 只有当用户的问题需要查看 git 信息或文件内容时，才调用工具。
- 如果用户只是打招呼（如"hello"、"你好"）或问通用问题，直接文字回答，不要调用任何工具。
- 不要主动调用工具展示信息，除非用户明确要求。"""

messages.append({"role": "system", "content": SYSTEM_PROMPT})

print("=" * 50)
print("【Git Agent 启动】（输入 quit 或 exit 退出）")
print("=" * 50)

while True:
    # ── 外层：读用户输入 ──
    print()  # 空行分隔
    user_input = input("你: ").strip()
    if not user_input:
        continue  # 空输入跳过
    if user_input.lower() in ("quit", "exit", "q"):
        print("再见 👋")
        break

    messages.append({"role": "user", "content": user_input})

    # ── 内层：ReAct 循环（处理这个问题）──
    loop_count = 0
    while loop_count < MAX_LOOPS:
        loop_count += 1
        print(f"\n{DASH}")
        print(f"【第 {loop_count} 轮】")
        print(DASH)

        response = call_model(messages)
        msg = response["choices"][0]["message"]

        if not msg.get("tool_calls"):
            # 没有工具调用 → 最终回答 → 结束这轮 ReAct，回到外层等下一个问题
            print(f"\n✅ Agent: {msg.get('content', '(无文字)')}")
            messages.append(msg)  # 最终回答也记进历史，下一轮能看到
            break

        messages.append(msg)  # 模型的工具调用记进历史

        for tool_call in msg["tool_calls"]:
            func_name = tool_call["function"]["name"]
            try:
                func_args = json.loads(tool_call["function"]["arguments"])
            except json.JSONDecodeError:
                func_args = {}
                print(f"  arguments 不是合法 JSON，已降级为空参数")

            print(f"  调工具: {func_name}({func_args})")
            result = execute_tool(func_name, func_args)
            preview = result[:200] + ("..." if len(result) > 200 else "")
            print(f"  结果: {preview}")

            messages.append({
                "role": "tool",
                "tool_call_id": tool_call["id"],
                "content": result
            })
    else:
        # 内层 while 跑满 MAX_LOOPS 没正常结束
        print(f"\n⚠️ 单问题已达最大循环次数 ({MAX_LOOPS})，强制终止本轮")
