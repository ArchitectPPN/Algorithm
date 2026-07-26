"""
Git Agent 自动测试脚本

测试 5 个场景：
1. 打招呼 - 不应该调工具
2. 获取提交列表 - 应该调 get_commits
3. 查看提交详情 - 应该调 get_diff
4. 读文件 - 应该调 read_file
5. 复杂问题 - 组合使用多个工具
"""

import os
import sys
import json

# 设置 API Key
os.environ['DEEPSEEK_API_KEY'] = 'sk-f034964389cb4ae4a79d2ff0ed320dbc'

# 导入必需的模块
sys.path.insert(0, '/Users/ppn/Code/Algorithm/PythonStudy/myagent')
import requests

# 从 git_agent 复制必要的代码
URL = "https://api.deepseek.com/chat/completions"
HEADERS = {"Authorization": f"Bearer {os.environ['DEEPSEEK_API_KEY']}", "Content-Type": "application/json"}

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
                    "count": {"type": "integer", "description": "要查看的提交数量，默认5"}
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
                    "commit": {"type": "string", "description": "commit hash或 'HEAD'、'HEAD~1' 等"}
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
                    "file_path": {"type": "string", "description": "要读取的文件路径"}
                },
                "required": ["file_path"]
            }
        }
    }
]

# ── 2. 工具的真实实现 ──
def do_get_commits(count=5):
    import subprocess
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

def do_get_diff(commit):
    import subprocess
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

def do_read_file(file_path):
    # 安全检查
    if file_path.endswith(".env") or "env" in os.path.basename(file_path):
        return "安全限制：不允许读取 .env 文件"
    try:
        with open(file_path, encoding="utf-8") as f:
            content = f.read()
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
def execute_tool(func_name, func_args):
    if func_name not in TOOL_FUNCTIONS:
        return f"错误：没有名为 '{func_name}' 的工具"
    try:
        return TOOL_FUNCTIONS[func_name](**func_args)
    except TypeError as e:
        return f"参数错误：{e}"
    except Exception as e:
        return f"工具执行失败：{e}"

# ── 3. 调 chat API ──
def call_model(messages, model="deepseek-v4-flash"):
    body = {
        "model": model,
        "messages": messages,
        "tools": TOOLS,
        "temperature": 0,
    }
    resp = requests.post(URL, headers=HEADERS, json=body)
    resp.raise_for_status()
    return resp.json()

SYSTEM_PROMPT = """你是一个 Git 仓库助手。你可以帮助用户查看 git 提交记录、提交详情、文件内容等。

重要规则：
- 只有当用户的问题需要查看 git 信息或文件内容时，才调用工具。
- 如果用户只是打招呼（如"hello"、"你好"）或问通用问题，直接文字回答，不要调用任何工具。
- 不要主动调用工具展示信息，除非用户明确要求。"""

DASH = "-" * 50

def run_agent(messages, max_loops=5):
    """运行单次 ReAct 循环，返回最终回答和调用的工具列表"""
    result_messages = messages.copy()
    called_tools = []

    for loop in range(max_loops):
        response = call_model(result_messages)
        msg = response["choices"][0]["message"]

        if not msg.get("tool_calls"):
            # 最终回答
            return msg.get("content", ""), called_tools

        # 工具调用
        called_tools.append(msg["tool_calls"][0]["function"]["name"])
        result_messages.append(msg)

        for tool_call in msg["tool_calls"]:
            func_name = tool_call["function"]["name"]
            args = json.loads(tool_call["function"]["arguments"])
            result = execute_tool(func_name, args)

            result_messages.append({
                "role": "tool",
                "tool_call_id": tool_call["id"],
                "content": result[:200]  # 只返回前200字符
            })

    return "超过最大循环次数", called_tools


def test_scenario(name, user_prompt, expected_tools_pattern):
    """测试一个场景"""
    print(f"\n{DASH}")
    print(f"【测试：{name}】")
    print(f"Prompt: {user_prompt}")
    print(DASH)

    messages = [
        {"role": "system", "content": SYSTEM_PROMPT},
        {"role": "user", "content": user_prompt}
    ]

    try:
        response, called_tools = run_agent(messages)

        print(f"\n模型回答:")
        print(f"  {response[:200]}")
        print(f"\n调用工具: {called_tools}")

        # 验证
        if expected_tools_pattern == "none":
            passed = len(called_tools) == 0
        else:
            passed = len(called_tools) > 0 and called_tools[0] == expected_tools_pattern

        print(f"\n{'✅ 通过' if passed else '❌ 失败'}")
        return passed

    except Exception as e:
        print(f"\n❌ 错误: {e}")
        return False


def main():
    print("=" * 50)
    print("Git Agent 测试")
    print("=" * 50)

    results = []

    # 测试 1: 打招呼
    results.append(("打招呼", test_scenario(
        "打招呼",
        "你好",
        "none"  # 不应该调工具
    )))

    # 测试 2: 获取提交列表
    results.append(("获取提交列表", test_scenario(
        "获取提交列表",
        "看看最近的提交",
        "get_commits"
    )))

    # 测试 3: 查看提交详情
    results.append(("查看提交详情", test_scenario(
        "查看提交详情",
        "看看上一次提交改了什么",
        "get_diff"
    )))

    # 测试 4: 读文件
    results.append(("读文件", test_scenario(
        "读文件",
        "看一下 git_agent.py 有什么",
        "read_file"
    )))

    # 测试 5: 复杂问题
    results.append(("复杂问题", test_scenario(
        "复杂问题",
        "看看最近三次提交，然后告诉我上次提交改了哪些文件",
        "get_commits"  # 第一轮应该调 get_commits
    )))

    # 总结
    print(f"\n{'=' * 50}")
    print("测试总结")
    print(f"{'=' * 50}")
    for name, passed in results:
        print(f"{'✅' if passed else '❌'} {name}")

    total_passed = sum(1 for _, p in results if p)
    print(f"\n通过: {total_passed}/{len(results)}")

if __name__ == "__main__":
    main()
