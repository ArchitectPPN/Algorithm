"""
Agent 封装 Git 工具

工具列表：
- git status
- git branch
- git diff
- read_file
"""
import os
import subprocess
import json
import threading
import time
import sys

# 忽略 urllib3 SSL 警告（macOS LibreSSL 兼容性）
import warnings
warnings.filterwarnings("ignore", category=Warning)

import requests

DASH = "-" * 50

# 读取 .env - 支持从 myagent/ 或项目根目录运行
env_file = ".env"
if not os.path.exists(env_file):
    env_file = "../.env"
if os.path.exists(env_file):
    with open(env_file, encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if "=" in line and not line.startswith("#"):
                key, value = line.split("=", 1)
                os.environ[key.strip()] = value.strip().strip('"').strip("'")

# 使用 .env 中的配置
API_KEY = os.environ.get("DEEPSEEK_API_KEY", "")
URL = os.environ.get("DEEPSEEK_BASE_URL", "") + "/chat/completions"
MODEL_NAME = os.environ.get("DEEPSEEK_MODEL", "deepseek-chat")
CONTEXT_WINDOW = int(os.environ.get("DEEPSEEK_CONTEXT_WINDOW", "128000"))
COMPRESSION_THRESHOLD = float(os.environ.get("COMPRESSION_THRESHOLD", "70"))

# xunfei API 使用 Bearer Auth 格式
HEADERS = {
    "Authorization": f"Bearer {API_KEY}",
    "Content-Type": "application/json"
}

if not API_KEY:
    raise SystemExit("DEEPSEEK_API_KEY 未设置，请在 .env 中配置")

# ── Loading 动画 ──
_spinner_running = False
_spinner_chars = "⠋⠙⠹⠸⠼⠴⠦⠧⠇⠏"

def _spinner():
    """旋转动画线程"""
    i = 0
    while _spinner_running:
        sys.stdout.write(f"\r  {_spinner_chars[i]} 思考中...")
        sys.stdout.flush()
        i = (i + 1) % len(_spinner_chars)
        time.sleep(0.1)

def start_loading():
    """开始 loading 动画"""
    global _spinner_running
    _spinner_running = True
    t = threading.Thread(target=_spinner, daemon=True)
    t.start()
    return t

def stop_loading():
    """停止 loading 动画"""
    global _spinner_running
    _spinner_running = False
    sys.stdout.write("\r" + " " * 20 + "\r")
    sys.stdout.flush()

# ── 打印当前配置（用于调试）──
print(f"API URL: {URL}")
print(f"Model: {MODEL_NAME}")
print(f"API Key: {API_KEY[:10]}...{API_KEY[-10:]}")

# 工具定义
TOOLS = [
    {
        "type": "function",
        "function": {
            "name": "get_status",
            "description" : "获取当前工作区文件状态",
            "parameters" : {
                "type": "object",
                "properties": {},
                "required": []
            }
        }
    },
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

# 工具实现

def do_get_status() -> str:
    """获取当前工作区文件状态"""
    try:
        result = subprocess.run(
            ["git", "status"],
            capture_output=True, text=True, cwd=os.getcwd()
        )
        if result.returncode != 0:
            return f"git status 执行失败: {result.stderr}"
        return result.stdout.strip() or "(无输出)"
    except Exception as e:
        return f"exec error: {e}"

def do_get_commits(count: int = 5) -> str:
    """获取最近N条的提交记录"""
    try:
        result = subprocess.run(
            ["git", "log", "--oneline", f"-{count}"],
            capture_output=True, text=True, cwd=os.getcwd()
        )
        if result.returncode != 0:
            return f"git log 执行失败：{result.stderr}"
        return result.stdout.strip() or "(无提交记录)"
    except Exception as e:
        return f"exec error: {e}"

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

TOOL_FUNCTIONS= {
    "get_status": do_get_status,
    "get_commits": do_get_commits,
    "get_diff": do_get_diff,
    "read_file": do_read_file,
}

# 容错
def execute_tool(func_name: str, func_args: dict) -> str:
    if func_name not in TOOL_FUNCTIONS:
        return f"没有名为：{func_name} 的function，可用工具为： {list(TOOL_FUNCTIONS.keys())}"
    try:
        result = TOOL_FUNCTIONS[func_name](**func_args)
    except TypeError as e:
        return f"参数错误：{e}。你传的参数是：{func_args}"
    except Exception as e:
        return f"工具执行失败：{e}"
    return result

# 调用 chat API（非流式，用于工具调用）
def call_model(messages, max_retries=3):
    """调用 chat API，带重试机制"""
    for attempt in range(max_retries):
        try:
            body = {
                "model": os.environ.get("DEEPSEEK_MODEL", "deepseek-chat"),
                "messages": messages,
                "tools": TOOLS,
                "temperature": 0,
            }
            resp = requests.post(URL, headers=HEADERS, json=body, timeout=30)
            resp.raise_for_status()
            return resp.json()
        except requests.exceptions.Timeout:
            print(f"  ⚠️ 请求超时，重试 {attempt + 1}/{max_retries}")
            if attempt < max_retries - 1:
                continue
            raise
        except requests.exceptions.HTTPError as e:
            if e.response.status_code == 429:
                print(f"  ⚠️ 限流，等待后重试 {attempt + 1}/{max_retries}")
                if attempt < max_retries - 1:
                    import time
                    time.sleep(2 ** attempt)
                    continue
            elif e.response.status_code == 401:
                print(f"  ❌ 认证失败，请检查 API Key")
                return {"error": "authentication_failed", "message": "API Key 无效"}
            elif e.response.status_code == 400:
                error_text = e.response.text
                print(f"  ⚠️ 400 错误: {error_text[:200]}")
                if attempt < max_retries - 1:
                    continue
            raise
        except Exception as e:
            print(f"  ⚠️ 请求失败: {e}")
            if attempt < max_retries - 1:
                continue
            raise

    raise Exception("请求重试次数用尽")

# 流式调用 chat API（用于最终回答，逐字显示）
def call_model_stream(messages):
    """流式调用 chat API，逐字打印回答"""
    import sys
    body = {
        "model": os.environ.get("DEEPSEEK_MODEL", "deepseek-chat"),
        "messages": messages,
        "tools": TOOLS,
        "temperature": 0,
        "stream": True,
    }
    resp = requests.post(URL, headers=HEADERS, json=body, stream=True)
    resp.raise_for_status()

    full_content = ""
    for line in resp.iter_lines():
        if line:
            line = line.decode('utf-8')
            if line.startswith("data: "):
                data_str = line[6:]
                if data_str == "[DONE]":
                    break
                try:
                    data = json.loads(data_str)
                    delta = data.get("choices", [{}])[0].get("delta", {})
                    content = delta.get("content", "")
                    if content:
                        full_content += content
                        sys.stdout.write(content)
                        sys.stdout.flush()
                except json.JSONDecodeError:
                    continue
    print()  # 换行
    return full_content

# 主循环

MAX_LOOPS = 10

messages = []

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

try:
    while True:
        userMsg = input("You: ").strip()

        if not userMsg:
            continue
        if userMsg.lower() in ("exit", "quit", "q"):
            print("See You Later")
            break

        messages.append({"role": "user", "content": userMsg})

        loop_count = 0
        while loop_count < MAX_LOOPS:
            loop_count += 1
            print(f"\n{DASH}")
            print(f"【第 {loop_count} 轮】")
            print(DASH)

            # 显示旋转 loading 动画
            start_loading()
            resp = call_model(messages)
            stop_loading()

            # 处理 API 错误
            if resp.get("error"):
                print(f"  API 错误: {resp.get('message')}")
                print("  请检查 API Key 或模型配置")
                break

            msg = resp["choices"][0]["message"]

            if not msg.get("tool_calls"):
                # 没有工具调用 → 最终回答 → 流式输出
                print("\n✅ 最终回答:")
                print("   ", end="")
                content = call_model_stream(messages)
                messages.append({"role": "assistant", "content": content})
                break

            # 有工具调用 → 执行工具
            # 记录模型的 tool_calls 意图
            tool_uses = []
            for tool_call in msg["tool_calls"]:
                func_name = tool_call["function"]["name"]
                try:
                    func_args = json.loads(tool_call["function"]["arguments"])
                except json.JSONDecodeError:
                    func_args = {}
                    print(f" arguments 不是合法 JSON，已降级为空参数")

                print(f"  调工具: {func_name}({func_args})")
                result = execute_tool(func_name, func_args)
                preview = result[:200] + ("..." if len(result) > 200 else "")
                print(f"  结果: {preview}")

                tool_uses.append((tool_call["id"], func_name, result))

            # Create assistant message with tool_calls for history
            messages.append(msg)
            # Add tool results
            for tool_id, func_name, result in tool_uses:
                messages.append({
                    "role": "tool",
                    "tool_call_id": tool_id,
                    "content": result
                })
        else:
            print(f"\n已达最大循环次数 ({MAX_LOOPS})，强制终止")

        print(f"\n{'='*50}")
        print(f"循环总轮数: {loop_count}")
except KeyboardInterrupt:
    print("\n\n👋 再见！")
