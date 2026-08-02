"""
Git Agent Service —— 从 git_agent_practice.py 抽取的 ReAct 核心逻辑

纯函数，不依赖 input/print，可被 API/CLI 复用。
"""
import os
import asyncio
import subprocess
import json
import warnings
warnings.filterwarnings("ignore", category=Warning)
import httpx


# ══════════════════════════════════════════════════════════
# 配置加载
# ══════════════════════════════════════════════════════════

def load_config():
    """从 .env 加载配置，返回配置字典"""
    env_file = ".env"
    if not os.path.exists(env_file):
        env_file = os.path.join(os.path.dirname(__file__), "..", "..", ".env")
    if os.path.exists(env_file):
        with open(env_file, encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if "=" in line and not line.startswith("#"):
                    key, value = line.split("=", 1)
                    if key.strip() not in os.environ:
                        os.environ[key.strip()] = value.strip().strip('"').strip("'")

    return {
        "api_key": os.environ.get("DEEPSEEK_API_KEY", ""),
        "base_url": os.environ.get("DEEPSEEK_BASE_URL", ""),
        "model": os.environ.get("DEEPSEEK_MODEL", "deepseek-chat"),
        "context_window": int(os.environ.get("DEEPSEEK_CONTEXT_WINDOW", "128000")),
    }


# ══════════════════════════════════════════════════════════
# 工具定义（JSON Schema + 实现函数）
# ══════════════════════════════════════════════════════════

TOOLS_SCHEMA = [
    {
        "type": "function",
        "function": {
            "name": "get_status",
            "description": "获取当前工作区文件状态（git status）",
            "parameters": {"type": "object", "properties": {}, "required": []}
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
                    "commit": {"type": "string", "description": "commit hash（如 'ad2132e'）或 'HEAD'、'HEAD~1' 等"}
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
                    "file_path": {"type": "string", "description": "要读取的文件路径，如 'list.py' 或 'src/main.py'"}
                },
                "required": ["file_path"]
            }
        }
    }
]


def _tool_get_status(repo_path: str) -> str:
    """执行 git status（同步版本）"""
    result = subprocess.run(
        ["git", "status"], capture_output=True, text=True, cwd=repo_path
    )
    if result.returncode != 0:
        return f"git status 执行失败: {result.stderr}"
    return result.stdout.strip() or "(无输出)"


def _tool_get_commits(repo_path: str, count: int = 5) -> str:
    """获取最近 N 条提交记录（同步版本）"""
    result = subprocess.run(
        ["git", "log", "--oneline", f"-{count}"],
        capture_output=True, text=True, cwd=repo_path
    )
    if result.returncode != 0:
        return f"git log 执行失败: {result.stderr}"
    return result.stdout.strip() or "(无提交记录)"


def _tool_get_diff(repo_path: str, commit: str) -> str:
    """查看提交的 --stat 摘要（同步版本）"""
    result = subprocess.run(
        ["git", "show", "--stat", commit],
        capture_output=True, text=True, cwd=repo_path
    )
    if result.returncode != 0:
        return f"git show --stat 执行失败: {result.stderr}"
    return result.stdout.strip()


def _tool_read_file(repo_path: str, file_path: str) -> str:
    """读取文件内容（同步版本）"""
    if file_path.endswith(".env") or "env" in os.path.basename(file_path):
        return "安全限制：不允许读取 .env 文件"
    full_path = os.path.join(repo_path, file_path)
    with open(full_path, encoding="utf-8") as f:
        content = f.read()
    max_chars = 3000
    if len(content) > max_chars:
        return content[:max_chars] + f"\n\n... (文件过长，已截断，完整文件共 {len(content)} 字符)"
    return content


async def tool_get_status(repo_path: str) -> str:
    """执行 git status（异步包装）"""
    try:
        return await asyncio.to_thread(_tool_get_status, repo_path)
    except Exception as e:
        return f"执行出错: {e}"


async def tool_get_commits(repo_path: str, count: int = 5) -> str:
    """获取最近 N 条提交记录（异步包装）"""
    try:
        return await asyncio.to_thread(_tool_get_commits, repo_path, count)
    except Exception as e:
        return f"执行出错: {e}"


async def tool_get_diff(repo_path: str, commit: str) -> str:
    """查看提交的 --stat 摘要（异步包装）"""
    try:
        return await asyncio.to_thread(_tool_get_diff, repo_path, commit)
    except Exception as e:
        return f"执行出错: {e}"


async def tool_read_file(repo_path: str, file_path: str) -> str:
    """读取文件内容（异步包装）"""
    try:
        return await asyncio.to_thread(_tool_read_file, repo_path, file_path)
    except FileNotFoundError:
        return f"文件不存在: {file_path}"
    except Exception as e:
        return f"读取失败: {e}"


TOOL_FUNCTIONS = {
    "get_status": tool_get_status,
    "get_commits": tool_get_commits,
    "get_diff": tool_get_diff,
    "read_file": tool_read_file,
}


def _parse_tool_args(tc: dict) -> dict:
    """解析 tool_call 的 arguments，容错"""
    try:
        return json.loads(tc["function"]["arguments"])
    except json.JSONDecodeError:
        return {}


async def execute_tool(func_name: str, func_args: dict, repo_path: str) -> str:
    """执行工具，带容错"""
    if func_name not in TOOL_FUNCTIONS:
        return f"没有名为 {func_name} 的工具，可用工具: {list(TOOL_FUNCTIONS.keys())}"
    try:
        result = await TOOL_FUNCTIONS[func_name](repo_path=repo_path, **func_args)
    except TypeError as e:
        return f"参数错误: {e}。你传的参数是: {func_args}"
    except Exception as e:
        return f"工具执行失败: {e}"
    return result


# ══════════════════════════════════════════════════════════
# LLM 调用
# ══════════════════════════════════════════════════════════

async def call_llm(messages: list, config: dict) -> dict:
    """调用 LLM API（异步，用于工具调用决策）"""
    url = config["base_url"] + "/chat/completions"
    headers = {
        "Authorization": f"Bearer {config['api_key']}",
        "Content-Type": "application/json"
    }
    body = {
        "model": config["model"],
        "messages": messages,
        "tools": TOOLS_SCHEMA,
        "temperature": 0,
    }

    async with httpx.AsyncClient(timeout=30) as client:
        for attempt in range(3):
            try:
                resp = await client.post(url, headers=headers, json=body)
                resp.raise_for_status()
                return resp.json()
            except httpx.TimeoutException:
                if attempt == 2:
                    raise
            except httpx.HTTPStatusError as e:
                if e.response.status_code == 401:
                    return {"error": "authentication_failed", "message": "API Key 无效"}
                if e.response.status_code == 429 and attempt < 2:
                    await asyncio.sleep(2 ** attempt)
                    continue
                if attempt == 2:
                    return {"error": "api_error", "message": str(e)}
            except Exception as e:
                if attempt == 2:
                    return {"error": "network_error", "message": str(e)}
    return {"error": "unknown", "message": "重试次数用尽"}


# ══════════════════════════════════════════════════════════
# ReAct 循环（核心逻辑）
# ══════════════════════════════════════════════════════════

SYSTEM_PROMPT = """你是一个 Git 仓库助手。你可以帮助用户查看 git 提交记录、提交详情、文件内容等。

重要规则：
- 只有当用户的问题需要查看 git 信息或文件内容时，才调用工具。
- 如果用户只是打招呼（如"hello"、"你好"）或问通用问题，直接文字回答，不要调用任何工具。
- 不要主动调用工具展示信息，除非用户明确要求。"""

MAX_LOOPS = 10


async def run_agent(question: str, repo_path: str = ".", max_loops: int = MAX_LOOPS) -> dict:
    """
    执行一次 Agent 对话，返回结构化结果。

    参数:
        question: 用户问题
        repo_path: 仓库路径
        max_loops: 最大 ReAct 循环次数

    返回:
        {
            "answer": str,           # 最终回答
            "tool_calls": [...],     # 工具调用记录
            "loops": int,            # 实际循环次数
            "error": str | None,     # 错误信息（如果有）
        }
    """
    config = load_config()
    if not config["api_key"]:
        return {"answer": "", "tool_calls": [], "loops": 0, "error": "API Key 未配置"}

    messages = [
        {"role": "system", "content": SYSTEM_PROMPT},
        {"role": "user", "content": question},
    ]

    tool_calls_log = []

    for loop in range(1, max_loops + 1):
        resp = await call_llm(messages, config)

        if resp.get("error"):
            return {
                "answer": "",
                "tool_calls": tool_calls_log,
                "loops": loop,
                "error": resp.get("message", "API 错误"),
            }

        msg = resp["choices"][0]["message"]

        # 没有工具调用 → 返回最终回答
        if not msg.get("tool_calls"):
            answer = msg.get("content", "") or ""
            return {
                "answer": answer,
                "tool_calls": tool_calls_log,
                "loops": loop,
                "error": None,
            }

        # 有工具调用 → 并发执行
        messages.append(msg)

        # 1. 解析参数 + 创建任务（列表推导式）
        parsed = [(tc["id"], tc["function"]["name"], _parse_tool_args(tc))
                  for tc in msg["tool_calls"]]
        tasks = [execute_tool(func_name, args, repo_path)
                 for _, func_name, args in parsed]

        # 2. 并发执行所有工具
        results = await asyncio.gather(*tasks)

        # 3. 记录结果
        for (tool_id, func_name, func_args), result in zip(parsed, results):
            tool_calls_log.append({
                "tool": func_name,
                "args": func_args,
                "result_preview": result[:200],
            })
            messages.append({
                "role": "tool",
                "tool_call_id": tool_id,
                "content": result,
            })

    # 超过最大循环
    return {
        "answer": "",
        "tool_calls": tool_calls_log,
        "loops": max_loops,
        "error": f"超过最大循环次数 ({max_loops})",
    }
