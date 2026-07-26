"""Bash 工具：执行 shell 命令。"""

import os
import subprocess
import tempfile
from typing import Any, Dict

from tools.base import BaseTool, ToolContext, ToolResult


class BashTool(BaseTool):
    name = "Bash"
    description = "执行 shell 命令并返回输出。Windows 下用 PowerShell，Unix 用 bash。支持超时、后台运行。危险命令会被权限引擎拦截。"
    parameters = {
        "type": "object",
        "properties": {
            "command": {"type": "string", "description": "要执行的命令"},
            "timeout": {"type": "integer", "description": "超时秒数，默认 120"},
            "run_in_background": {"type": "boolean", "description": "是否后台运行，默认 false"},
        },
        "required": ["command"],
    }
    risk_level = "destructive"

    # 后台进程注册表：{pid: {"process": Popen, "stdout_file": path, "stderr_file": path}}
    _background_procs: Dict[int, Dict[str, Any]] = {}

    def execute(self, params: Dict[str, Any], context: ToolContext) -> ToolResult:
        command = params.get("command", "").strip()
        if not command:
            return ToolResult(success=False, error="command 参数必填")

        timeout = params.get("timeout", 120)
        run_in_background = params.get("run_in_background", False)

        if run_in_background:
            return self._run_background(command, context)

        # 选择 shell
        shell_args = self._build_shell_args(command)

        try:
            result = subprocess.run(
                shell_args,
                capture_output=True,
                text=True,
                timeout=timeout,
                encoding="utf-8",
                errors="replace",
                cwd=context.work_dir,
            )
        except subprocess.TimeoutExpired:
            return ToolResult(success=False, error=f"命令超时（{timeout}s）", output="")
        except Exception as e:
            return ToolResult(success=False, error=f"执行失败: {e}")

        stdout = result.stdout or ""
        stderr = result.stderr or ""
        return_code = result.returncode

        # 限制输出长度
        max_len = 30000
        if len(stdout) > max_len:
            stdout = stdout[:max_len] + "\n\n[stdout 已截断]"
        if len(stderr) > max_len:
            stderr = stderr[:max_len] + "\n\n[stderr 已截断]"

        output_parts = [f"退出码: {return_code}"]
        if stdout:
            output_parts.append(f"\n--- stdout ---\n{stdout}")
        if stderr:
            output_parts.append(f"\n--- stderr ---\n{stderr}")

        return ToolResult(
            success=return_code == 0,
            output="\n".join(output_parts),
            error=None if return_code == 0 else f"命令失败（退出码 {return_code}）",
        )

    def _build_shell_args(self, command: str) -> list:
        """根据操作系统构建 shell 命令参数。"""
        if os.name == "nt":
            return [
                "powershell.exe",
                "-NoProfile",
                "-NonInteractive",
                "-Command",
                command,
            ]
        return ["/bin/bash", "-c", command]

    def _run_background(self, command: str, context: ToolContext) -> ToolResult:
        """后台运行：启动进程，输出写入临时文件，返回 PID 和查看方式。"""
        shell_args = self._build_shell_args(command)

        # 创建临时文件保存输出
        tmp_dir = tempfile.gettempdir()
        stdout_file = os.path.join(tmp_dir, f"agent_bg_{os.getpid()}_stdout.log")
        stderr_file = os.path.join(tmp_dir, f"agent_bg_{os.getpid()}_stderr.log")

        try:
            with open(stdout_file, "w", encoding="utf-8", errors="replace") as out_f, \
                 open(stderr_file, "w", encoding="utf-8", errors="replace") as err_f:
                proc = subprocess.Popen(
                    shell_args,
                    stdout=out_f,
                    stderr=err_f,
                    cwd=context.work_dir,
                )
        except Exception as e:
            return ToolResult(success=False, error=f"启动失败: {e}")

        # 注册后台进程
        self._background_procs[proc.pid] = {
            "process": proc,
            "stdout_file": stdout_file,
            "stderr_file": stderr_file,
            "command": command,
        }

        # 清理已结束的旧进程
        self._cleanup_finished_procs()

        return ToolResult(
            success=True,
            output=(
                f"后台进程已启动，PID: {proc.pid}\n"
                f"stdout 日志: {stdout_file}\n"
                f"stderr 日志: {stderr_file}\n"
                f"提示：可用 Bash 工具执行 `type {stdout_file}`（Windows）或 `cat {stdout_file}`（Unix）查看输出"
            ),
        )

    @classmethod
    def _cleanup_finished_procs(cls):
        """清理已结束的后台进程，释放资源。"""
        finished_pids = []
        for pid, info in cls._background_procs.items():
            proc = info["process"]
            if proc.poll() is not None:
                # 进程已结束
                finished_pids.append(pid)
        for pid in finished_pids:
            del cls._background_procs[pid]
