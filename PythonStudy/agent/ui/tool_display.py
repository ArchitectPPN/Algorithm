"""工具调用实时展示。"""

import threading
import time
from typing import Any, Dict, Optional

from rich.console import Console
from rich.live import Live
from rich.panel import Panel
from rich.spinner import Spinner
from rich.text import Text


console = Console()


def display_tool_start(tool_name: str, params: Dict[str, Any], risk_level: str) -> float:
    """展示工具调用开始。返回开始时间戳。"""
    risk_icon = {"read_only": "📖", "write": "✏️", "destructive": "⚠️"}.get(risk_level, "⚡")

    # 简化参数展示
    param_str = _format_params(tool_name, params)

    console.print(
        f"  [bold cyan]⚡ {tool_name}[/bold cyan] [dim]({risk_level})[/dim] {param_str}",
        style="dim",
    )
    return time.time()


def execute_with_spinner(tool_name: str, tool, params: Dict[str, Any], ctx, is_subagent: bool = False):
    """执行工具，带 spinner 指示。子 agent 静默执行不显示。"""
    if is_subagent:
        return tool.execute(params, ctx)

    result_holder: list = [None]
    exception_holder: list = [None]

    spinner = Spinner("dots", text=f"[dim]执行 {tool_name} 中...[/dim]")
    live = Live(spinner, console=console, refresh_per_second=10, transient=False)
    live.start()

    def run():
        try:
            result_holder[0] = tool.execute(params, ctx)
        except Exception as e:
            exception_holder[0] = e

    t = threading.Thread(target=run, daemon=True)
    t.start()

    # 等待线程结束，spinner 持续显示
    while t.is_alive():
        t.join(timeout=0.1)

    live.stop()

    if exception_holder[0]:
        raise exception_holder[0]
    return result_holder[0]



def display_tool_result(success: bool, output: str, error: Optional[str], start_time: float):
    """展示工具调用结果。"""
    elapsed = time.time() - start_time
    if success:
        # 截断输出预览
        preview = output.strip().split("\n")[0][:100] if output else ""
        if len(output.strip().split("\n")) > 1 or len(output) > 100:
            preview += " ..."
        console.print(
            f"  [green]✓ 完成[/green] [dim]({elapsed:.2f}s)[/dim] {preview}",
            style="dim",
        )
    else:
        console.print(
            f"  [red]✗ 失败[/red] [dim]({elapsed:.2f}s)[/dim] {error or ''}",
            style="dim",
        )


def display_tool_output(output: str, max_lines: int = 50):
    """展示工具完整输出（折叠展示前 N 行）。"""
    lines = output.strip().split("\n")
    if len(lines) <= max_lines:
        console.print(Panel(output, border_style="dim", expand=False))
    else:
        head = "\n".join(lines[:max_lines])
        console.print(Panel(head + f"\n\n... ({len(lines) - max_lines} 行未展示)", border_style="dim", expand=False))


def _format_params(tool_name: str, params: Dict[str, Any]) -> str:
    """简化参数展示。"""
    if tool_name in ("Read", "Write", "Edit"):
        path = params.get("path", "")
        return f"[dim]path=[/dim]{path}"
    elif tool_name == "Bash":
        cmd = params.get("command", "")
        if len(cmd) > 80:
            cmd = cmd[:80] + "..."
        return f"[dim]cmd=[/dim]{cmd}"
    elif tool_name == "Grep":
        return f"[dim]pattern=[/dim]{params.get('pattern', '')}"
    elif tool_name == "Glob":
        return f"[dim]pattern=[/dim]{params.get('pattern', '')}"
    elif tool_name == "Task":
        return f"[dim]desc=[/dim]{params.get('description', '')} [dim]type=[/dim]{params.get('subagent_type', 'general')}"
    return ""
