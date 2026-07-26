"""CLI 交互层：输入、流式渲染、Markdown、命令处理。"""

import json
import os
import re
import sys
from typing import Callable, Dict, List, Optional, Tuple

from rich.console import Console
from rich.markdown import Markdown
from rich.live import Live
from rich.text import Text

from core.message import Message
from skills.loader import SkillLoader


console = Console()


class CLI:
    def __init__(self, skill_loader: Optional[SkillLoader], base_dir: str = ""):
        self.skill_loader = skill_loader
        self.base_dir = base_dir  # 项目根目录，会话管理需要
        self._session_resume_callback = None  # 恢复会话的回调，由 agent_main 设置

    def welcome(self, model_name: str, work_dir: str):
        from rich.panel import Panel
        from rich.text import Text

        content = Text()
        content.append("Claude Code 风格 CLI 编码助手\n", style="white")
        content.append("模型: ", style="white")
        content.append(model_name, style="yellow bold")
        content.append("\n工作目录: ", style="white")
        content.append(work_dir, style="yellow bold")

        console.print()
        console.print(Panel(
            content,
            title="[bold cyan]AI Agent[/bold cyan]",
            title_align="left",
            border_style="cyan",
            padding=(0, 1),
            expand=False,
        ))
        console.print()
        console.print("[dim]exit 退出 | clear 清空 | model 切换模型 | sessions 历史 | /session <编号> 恢复会话 | /skill 触发技能 | Ctrl+C 中断[/dim]")
        console.print()

    def read_input(self) -> Optional[str]:
        """读取用户输入。返回 None 表示退出。"""
        try:
            console.print("[bold green]👤 你:[/bold green] ", end="")
            text = input()
            return text
        except (EOFError, KeyboardInterrupt):
            console.print()
            return None

    def stream_render(self, text_stream) -> str:
        """流式渲染模型文本输出。返回完整文本。"""
        full_text = []
        live = Live("", console=console, refresh_per_second=10, vertical_overflow="visible")
        live.start()
        try:
            for chunk in text_stream:
                if chunk:
                    full_text.append(chunk)
                    # 流式时用纯文本显示，结束后再用 Markdown 渲染
                    live.update(Text("".join(full_text)))
        finally:
            live.stop()

        result = "".join(full_text)
        # 流式结束后用 Markdown 重新渲染
        console.print()
        try:
            console.print(Markdown(result))
        except Exception:
            console.print(result)
        return result

    def print(self, msg: str = "", style: str = ""):
        console.print(msg, style=style)

    def info(self, msg: str):
        console.print(f"  [blue]ℹ️[/blue] {msg}", style="dim")

    def success(self, msg: str):
        console.print(f"  [green]✅[/green] {msg}")

    def warn(self, msg: str):
        console.print(f"  [yellow]⚠️[/yellow] {msg}")

    def error(self, msg: str):
        console.print(f"  [red]❌[/red] {msg}")

    def ask_permission(self, tool_name: str, params: dict, risk_level: str, reason: str) -> tuple:
        """权限弹窗。返回 (choice, feedback)。

        choice:
          'y' 本次允许 / 'Y' 始终允许 / 'e' 给反馈 / 'n' 拒绝
        feedback: 仅 choice == 'e' 时为字符串，其他为 None
        """
        console.print()
        console.print(f"  [yellow]⚠️ 权限确认[/yellow] [bold]{tool_name}[/bold] [dim]({risk_level})[/dim]")
        console.print(f"  [dim]原因: {reason}[/dim]")
        if tool_name == "Bash":
            console.print(f"  [dim]命令: [white]{params.get('command', '')}[/white][/dim]")
        elif "path" in params:
            console.print(f"  [dim]路径: [white]{params.get('path', '')}[/white][/dim]")
        console.print()

        options = [
            "[y] 本次允许",
            "[Y] 始终允许",
            "[e] 给反馈（拒绝并告诉模型怎么改）",
            "[n] 拒绝",
        ]
        idx = self.select_option(options, default_idx=0)

        if idx == 0:
            return "y", None
        if idx == 1:
            return "Y", None
        if idx == 2:
            feedback = self._read_feedback()
            return "e", feedback
        return "n", None

    def select_option(self, options: List[str], default_idx: int = 0) -> int:
        """交互式选择选项。用上下箭头切换，回车确认。

        返回选中索引，-1 表示取消（ESC / q / Ctrl+C）。
        """
        from ui.keyboard import read_key

        current = default_idx
        self._print_options(options, current)

        while True:
            try:
                key = read_key()
            except (EOFError, KeyboardInterrupt):
                print()
                return -1

            if key == "up":
                current = (current - 1) % len(options)
                self._reprint_options(options, current)
            elif key == "down":
                current = (current + 1) % len(options)
                self._reprint_options(options, current)
            elif key in ("\r", "\n"):
                print()
                return current
            elif key in ("q", "\x1b"):
                print()
                return -1
            elif key == "\x03":
                print()
                raise KeyboardInterrupt
            # 其他键忽略

    def _print_options(self, options: List[str], current: int):
        for i, opt in enumerate(options):
            if i == current:
                sys.stdout.write(f"  \033[36m\033[1m❯ {opt}\033[0m\n")
            else:
                sys.stdout.write(f"  \033[2m  {opt}\033[0m\n")
        sys.stdout.flush()

    def _reprint_options(self, options: List[str], current: int):
        n = len(options)
        sys.stdout.write(f"\033[{n}A")
        for i, opt in enumerate(options):
            sys.stdout.write("\r\033[K")
            if i == current:
                sys.stdout.write(f"  \033[36m\033[1m❯ {opt}\033[0m\n")
            else:
                sys.stdout.write(f"  \033[2m  {opt}\033[0m\n")
        sys.stdout.flush()

    def _read_feedback(self) -> str:
        """读多行反馈，空行结束。"""
        console.print("  [dim]请输入反馈（空行结束，会传给模型让它调整）：[/dim]")
        lines = []
        while True:
            try:
                line = input("  > ")
            except (EOFError, KeyboardInterrupt):
                break
            if line == "":
                break
            lines.append(line)
        return "\n".join(lines)

    def handle_command(self, text: str, agent_loop) -> bool:
        """处理内置命令。返回 True 表示已处理，False 表示这不是命令。

        特殊返回值 'resume' 表示需要恢复会话（由调用方处理）。
        """
        text = text.strip()
        if text in ("exit", "quit"):
            return True  # 由调用方处理退出
        if text == "clear":
            agent_loop.clear_memory()
            self.success("当前会话已清空（磁盘文件保留）")
            return True
        if text == "model":
            self._show_models(agent_loop)
            return True
        if text.startswith("model "):
            self._switch_model(text[6:].strip(), agent_loop)
            return True
        if text == "skills":
            self._show_skills()
            return True
        if text == "skills reload":
            if self.skill_loader:
                count = self.skill_loader.reload()
                self.success(f"已重新加载 {count} 个技能")
            return True
        if text in ("sessions", "session", "/sessions", "/session"):
            self._show_sessions()
            return True
        # /session <编号> 或 /resume <编号>：直接恢复指定编号的会话
        if text.startswith(("/session ", "/resume ")):
            idx_str = text.split(None, 1)[1].strip()
            return self._resume_session_by_index(idx_str, agent_loop)
        if text == "help":
            self._show_help()
            return True
        return False

    def _show_models(self, agent_loop):
        models = agent_loop.provider.list_models()
        current = agent_loop.provider.get_current_model()
        console.print("\n  [bold]可用模型:[/bold]")
        for i, m in enumerate(models):
            mark = "← 当前" if m == current else ""
            console.print(f"    [yellow][{i}][/yellow] {m} {mark}")
        console.print(f"\n  [dim]切换: model <编号>[/dim]\n")

    def _switch_model(self, idx_str: str, agent_loop):
        try:
            idx = int(idx_str)
            agent_loop.provider.set_current_model(idx)
            self.success(f"已切换到: {agent_loop.provider.get_current_model()}")
        except (ValueError, IndexError):
            self.error("无效的模型编号")

    def _show_skills(self):
        if not self.skill_loader or not self.skill_loader.list_skills():
            self.info("暂无可用技能")
            return
        console.print("\n  [bold]可用技能:[/bold]")
        for skill in self.skill_loader.list_skills():
            console.print(f"    [cyan]/{skill.name}[/cyan] - {skill.description}")
        console.print()

    def _show_sessions(self):
        """列出最近的历史会话，并支持直接选择恢复"""
        from core.session_manager import list_sessions, get_session_preview, get_session_msg_count, load_session

        sessions = list_sessions(self.base_dir, 15)
        if not sessions:
            self.info("暂无历史会话")
            return

        console.print("\n  [bold]最近会话（按时间倒序）:[/bold]")
        for i, f in enumerate(sessions):
            sid = f[:-5]
            preview = get_session_preview(self.base_dir, f)
            msg_count = get_session_msg_count(self.base_dir, f)
            console.print(f"    [yellow][{i}][/yellow] {sid}  [dim]({msg_count}条) | {preview}[/dim]")
        console.print()
        console.print("[dim]恢复会话: /session <编号> 或 /resume <编号>，如 /session 0[/dim]")
        console.print()

    def _resume_session_by_index(self, idx_str: str, agent_loop) -> bool:
        """根据编号恢复会话，直接在 CLI 层面操作，不经过大模型"""
        from core.session_manager import list_sessions, load_session, get_session_preview

        sessions = list_sessions(self.base_dir, 15)
        if not sessions:
            self.info("暂无历史会话")
            return True

        try:
            idx = int(idx_str)
        except ValueError:
            self.error(f"无效编号: {idx_str}（请输入数字，如 /session 0）")
            return True

        if idx < 0 or idx >= len(sessions):
            self.error(f"编号超出范围: {idx}（可用 0-{len(sessions)-1}）")
            return True

        session_file = sessions[idx]
        session_id = session_file[:-5]
        preview = get_session_preview(self.base_dir, session_file)

        # 加载会话
        history = load_session(self.base_dir, session_file)
        if not history:
            self.error("该会话为空或加载失败")
            return True

        # 恢复到 agent_loop
        agent_loop.load_history(history)

        # 通知回调（让 agent_main 更新 session_id）
        if self._session_resume_callback:
            self._session_resume_callback(session_id)

        self.success(f"已恢复会话: {session_id}（{len(history)} 条消息）")
        self.info(f"预览: {preview}")
        return True

    def _show_help(self):
        console.print("\n  [bold]命令:[/bold]")
        console.print("    [cyan]exit/quit[/cyan]  保存并退出")
        console.print("    [cyan]clear[/cyan]     清空当前会话（不删磁盘）")
        console.print("    [cyan]model[/cyan]     查看/切换模型 (model <编号>)")
        console.print("    [cyan]skills[/cyan]    列出技能 (skills reload 重载)")
        console.print("    [cyan]sessions[/cyan]  列出历史会话")
        console.print("    [cyan]/session <编号>[/cyan]  恢复指定会话（如 /session 0）")
        console.print("    [cyan]/resume <编号>[/cyan]  同上，恢复指定会话")
        console.print("    [cyan]/queue[/cyan]    查看消息队列（模型思考时可继续输入，进队列等待）")
        console.print("                  /queue cancel <编号> 取消某条")
        console.print("                  /queue clear 清空队列")
        console.print("    [cyan]help[/cyan]      显示帮助")
        console.print()
