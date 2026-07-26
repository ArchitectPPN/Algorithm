"""Agent CLI 入口。

启动流程：
1. 加载配置（config.json / settings.json / mcp.json / CLAUDE.md）
2. 初始化 Provider / 工具 / 权限引擎 / 技能加载器
3. 启动主循环

会话管理：
- 每次启动新会话，生成时间戳 session_id
- 退出时保存到 config/chat_history/{session_id}.json
- 启动加 --resume 列出最近会话让用户选择恢复
- 运行中可用 /session <编号> 直接恢复会话（不经过大模型）
"""

import datetime
import json
import os
import sys
import threading

# Windows 下强制 stdout/stderr 用 UTF-8，避免 rich 渲染表格/list 符号时 GBK 编码失败
if sys.platform == "win32":
    try:
        sys.stdout.reconfigure(encoding="utf-8")
        sys.stderr.reconfigure(encoding="utf-8")
    except Exception:
        pass

# 把项目根目录加入 sys.path，便于 from core.xxx import
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, BASE_DIR)

from rich.console import Console

from core.agent_loop import AgentLoop
from core.message import Message, ToolCall
from core.message_queue import MessageQueue
from core.session_manager import (
    generate_session_id,
    list_sessions,
    load_session,
    save_session,
    get_session_preview,
    pick_session_interactive,
)
from permissions.engine import PermissionEngine
from providers.openai_compat import OpenAICompatProvider
from skills.loader import SkillLoader
from tools.base import BaseTool
from tools.read import ReadTool
from tools.write import WriteTool
from tools.edit import EditTool
from tools.grep import GrepTool
from tools.glob import GlobTool
from tools.bash import BashTool
from tools.task import TaskTool
from ui.cli import CLI

console = Console()

# ==================== 配置加载 ====================
CONFIG_DIR = os.path.join(BASE_DIR, "config")
CONFIG_FILE = os.path.join(CONFIG_DIR, "config.json")
SETTINGS_FILE = os.path.join(CONFIG_DIR, "settings.json")
MCP_FILE = os.path.join(CONFIG_DIR, "mcp.json")
CLAUDE_MD_FILE = os.path.join(CONFIG_DIR, "CLAUDE.md")

DEFAULT_CONFIG = {
    "api": {
        "base_url": "https://api-inference.modelscope.cn/v1",
        "api_key": "",
    },
    "provider": "openai_compat",  # openai_compat / anthropic
    "models": ["ZhipuAI/GLM-5", "deepseek-ai/DeepSeek-V3.2", "Qwen/Qwen3-Coder-480B-A35B-Instruct"],
    "default_model_index": 0,
    "max_rounds": 30,
    "command_timeout": 60,
    "frequency_penalty": 0.3,  # 重复惩罚（0.0-2.0），缓解模型退化重复
    "system_prompt": "你是一个乐于助人的 AI 助手，可以帮助用户解决各种问题。当需要读写文件、搜索代码、执行命令时，请主动使用工具。",
    "system_os": "Windows (PowerShell)",
    "skills_dir": "skills",
    "anthropic_api_key": "",  # Anthropic Provider 时使用
    "anthropic_models": ["claude-sonnet-4-6", "claude-haiku-4-5-20251001"],
}


def load_config() -> dict:
    os.makedirs(CONFIG_DIR, exist_ok=True)
    if not os.path.exists(CONFIG_FILE):
        with open(CONFIG_FILE, "w", encoding="utf-8") as f:
            json.dump(DEFAULT_CONFIG, f, ensure_ascii=False, indent=2)
        console.print(f"[yellow]⚠️ 已生成默认配置: {CONFIG_FILE}[/yellow]")
        console.print("[yellow]请编辑 config.json 填入 api_key 后重新运行[/yellow]")
        input("填写完成后按回车继续...")
    with open(CONFIG_FILE, "r", encoding="utf-8") as f:
        cfg = json.load(f)
    # 合并默认值
    for k, v in DEFAULT_CONFIG.items():
        cfg.setdefault(k, v)
    return cfg


def load_claude_md() -> str:
    """加载 CLAUDE.md 项目级指令。"""
    # 优先 config/CLAUDE.md，其次工作目录 CLAUDE.md
    candidates = [CLAUDE_MD_FILE, os.path.join(os.getcwd(), "CLAUDE.md")]
    for path in candidates:
        if os.path.exists(path):
            try:
                with open(path, "r", encoding="utf-8") as f:
                    return f.read()
            except Exception:
                pass
    return ""


def build_provider(cfg: dict):
    provider_type = cfg.get("provider", "openai_compat")
    freq_penalty = cfg.get("frequency_penalty", 0.3)
    if provider_type == "anthropic":
        from providers.anthropic import AnthropicProvider
        return AnthropicProvider(
            api_key=cfg.get("anthropic_api_key", ""),
            models=cfg.get("anthropic_models", ["claude-sonnet-4-6"]),
            default_model_index=0,
        )
    else:
        return OpenAICompatProvider(
            base_url=cfg["api"]["base_url"],
            api_key=cfg["api"]["api_key"],
            models=cfg["models"],
            default_model_index=cfg.get("default_model_index", 0),
            frequency_penalty=freq_penalty,
        )


def build_tools() -> dict:
    """构造工具字典。"""
    return {
        "Read": ReadTool(),
        "Write": WriteTool(),
        "Edit": EditTool(),
        "Grep": GrepTool(),
        "Glob": GlobTool(),
        "Bash": BashTool(),
        "Task": TaskTool(),
    }


def build_system_prompt(cfg: dict, claude_md: str) -> str:
    parts = [cfg.get("system_prompt", "")]
    os_info = cfg.get("system_os", "")
    if os_info:
        parts.append(f"\n\n[环境信息]\n操作系统: {os_info}\n工作目录: {os.getcwd()}")
    if claude_md:
        parts.append(f"\n\n[项目指令 (CLAUDE.md)]\n{claude_md}")
    parts.append(
        "\n\n[工具使用]\n你可以使用 Read/Write/Edit/Grep/Glob/Bash/Task 等工具完成编码任务。"
        "需要查看文件用 Read，修改代码用 Edit，搜索用 Grep/Glob，执行命令用 Bash。"
        "复杂任务可派生子 agent 用 Task 工具。"
    )
    return "".join(parts)


def main():
    cfg = load_config()

    # 检查 API key
    if cfg.get("provider", "openai_compat") == "openai_compat" and not cfg.get("api", {}).get("api_key"):
        console.print("[red]❌ api_key 未配置，请编辑 config/config.json[/red]")
        sys.exit(1)

    # 初始化各组件
    provider = build_provider(cfg)
    permission_engine = PermissionEngine(SETTINGS_FILE)
    skills_dir = os.path.join(BASE_DIR, cfg.get("skills_dir", "skills"))
    skill_loader = SkillLoader(skills_dir)
    tools = build_tools()
    claude_md = load_claude_md()
    system_prompt = build_system_prompt(cfg, claude_md)

    cli = CLI(skill_loader=skill_loader, base_dir=BASE_DIR)

    # 初始化主循环
    work_dir = os.getcwd()
    agent_loop = AgentLoop(
        provider=provider,
        permission_engine=permission_engine,
        skill_loader=skill_loader,
        tools=tools,
        work_dir=work_dir,
        max_rounds=cfg.get("max_rounds", 30),
        cli=cli,
    )
    agent_loop.set_system_prompt(system_prompt)

    # 生成新会话 ID
    session_id = generate_session_id()

    # 设置会话恢复回调：/session <编号> 恢复时更新 session_id
    def on_session_resume(chosen_id: str):
        nonlocal session_id
        session_id = chosen_id
    cli._session_resume_callback = on_session_resume

    # --resume：列出最近会话让用户选择
    if "--resume" in sys.argv or "-r" in sys.argv:
        result = pick_session_interactive(BASE_DIR, 10)
        if result:
            chosen_id, history = result
            session_id = chosen_id
            agent_loop.load_history(history)
            cli.info(f"已恢复会话: {session_id}（{len(history)} 条消息）")

    # 欢迎界面
    cli.welcome(provider.get_current_model(), work_dir)

    # 消息队列：输入线程往里塞，主线程取出来依次处理
    # 模型思考时用户输入会进队列，当前轮完成后自动处理下一条
    input_queue = MessageQueue()

    def input_loop():
        """输入线程：持续读输入塞进队列。读到 None（EOF）塞哨兵退出。

        /queue 命令在此拦截直接处理，不塞队列，让模型思考时也能查看/取消队列。
        """
        while True:
            try:
                user_input = cli.read_input()
            except (EOFError, KeyboardInterrupt):
                input_queue.put(None)
                return
            except Exception:
                input_queue.put(None)
                return

            if user_input is None:
                input_queue.put(None)
                return

            stripped = user_input.strip()

            # 拦截队列管理命令
            if stripped == "/queue":
                _show_queue_status(console, input_queue)
                continue
            if stripped.startswith("/queue "):
                _handle_queue_command(stripped, console, cli, input_queue)
                continue

            # 普通消息塞队列
            input_queue.put(user_input)

    input_thread = threading.Thread(target=input_loop, daemon=True)
    input_thread.start()

    # 主处理循环：从队列取消息依次处理
    try:
        while True:
            user_input = input_queue.get()
            if user_input is None:  # EOF
                break

            user_input = user_input.strip()
            if not user_input:
                continue

            # 处理内置命令
            if cli.handle_command(user_input, agent_loop):
                if user_input in ("exit", "quit"):
                    break
                # clear：清空当前会话内存，不删磁盘文件
                if user_input == "clear":
                    cli.success("当前会话已清空（磁盘文件保留）")
                continue

            # 正常对话
            try:
                agent_loop.run(user_input)
            except KeyboardInterrupt:
                cli.warn("已中断当前操作，队列中剩余消息继续处理")
            except Exception as e:
                cli.error(f"处理失败: {e}")

    finally:
        # 退出时保存到 chat_history/{session_id}.json
        history = agent_loop.get_history()
        if history:
            saved_path = save_session(BASE_DIR, history, session_id)
            if saved_path:
                cli.success(f"对话已保存: chat_history/{session_id}.json，再见！")
            else:
                cli.success("再见！")
        else:
            cli.success("再见！")


def _show_queue_status(console, q: MessageQueue):
    """显示队列状态。"""
    items = q.list_items()
    console.print()
    if not items:
        console.print("  [dim]队列为空（无等待处理的消息）[/dim]")
        console.print()
        return
    console.print(f"  [bold]消息队列（{len(items)} 条等待处理）:[/bold]")
    for i, msg in enumerate(items):
        preview = (msg or "").strip().replace("\n", " ")
        if len(preview) > 60:
            preview = preview[:60] + "..."
        console.print(f"    [yellow][{i}][/yellow] {preview}")
    console.print()
    console.print("[dim]取消: /queue cancel <编号> | 清空: /queue clear[/dim]")
    console.print()


def _handle_queue_command(cmd: str, console, cli: CLI, q: MessageQueue):
    """处理 /queue 子命令。"""
    parts = cmd.split(None, 2)
    if len(parts) < 2:
        return
    sub = parts[1]
    if sub == "cancel":
        if len(parts) < 3:
            cli.error("用法: /queue cancel <编号>")
            return
        try:
            idx = int(parts[2])
        except ValueError:
            cli.error("编号必须是数字，如 /queue cancel 0")
            return
        if q.cancel(idx):
            cli.success(f"已取消队列中第 {idx} 条消息")
            _show_queue_status(console, q)
        else:
            size = q.size()
            if size == 0:
                cli.error(f"队列为空，无法取消")
            else:
                cli.error(f"无效编号：{idx}（当前队列 {size} 条，编号 0-{size-1}）")
    elif sub == "clear":
        count = q.clear()
        cli.success(f"已清空队列（{count} 条消息）")
    else:
        cli.error(f"未知子命令：{sub}（可用：cancel <编号> / clear）")


if __name__ == "__main__":
    main()
