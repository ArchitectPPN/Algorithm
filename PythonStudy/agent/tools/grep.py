"""Grep 工具：基于 ripgrep 的内容搜索。"""

import os
import shutil
import subprocess
from typing import Any, Dict

from tools.base import BaseTool, ToolContext, ToolResult


class GrepTool(BaseTool):
    name = "Grep"
    description = "在文件内容中搜索正则。基于 ripgrep（rg）。支持 glob/type 过滤。默认返回匹配文件列表，output_mode=content 返回匹配行。"
    parameters = {
        "type": "object",
        "properties": {
            "pattern": {"type": "string", "description": "正则表达式"},
            "path": {"type": "string", "description": "搜索目录，默认当前工作目录"},
            "glob": {"type": "string", "description": "文件名 glob 过滤，如 '*.py'"},
            "type": {"type": "string", "description": "文件类型过滤，如 'py', 'js', 'go'"},
            "output_mode": {"type": "string", "enum": ["files_with_matches", "content", "count"], "description": "输出模式，默认 files_with_matches"},
            "-i": {"type": "boolean", "description": "忽略大小写，默认 false"},
            "-n": {"type": "boolean", "description": "显示行号（content 模式），默认 true"},
            "head_limit": {"type": "integer", "description": "限制输出条数，默认 250"},
        },
        "required": ["pattern"],
    }
    risk_level = "read_only"

    def execute(self, params: Dict[str, Any], context: ToolContext) -> ToolResult:
        pattern = params.get("pattern", "")
        if not pattern:
            return ToolResult(success=False, error="pattern 参数必填")

        search_path = params.get("path") or context.work_dir
        abs_path = search_path if os.path.isabs(search_path) else os.path.join(context.work_dir, search_path)
        if not os.path.exists(abs_path):
            return ToolResult(success=False, error=f"路径不存在: {abs_path}")

        # 优先用 ripgrep，没有则回退到内置
        rg_path = shutil.which("rg") or self._find_rg()
        if rg_path:
            return self._grep_with_rg(params, abs_path, rg_path)
        return self._grep_fallback(params, abs_path)

    def _find_rg(self) -> str:
        # Windows 常见安装位置
        candidates = [
            r"C:\Program Files\Git\bin\rg.exe",
            r"C:\Program Files\Git\usr\bin\rg.exe",
            os.path.expanduser(r"~\scoop\shims\rg.exe"),
        ]
        for c in candidates:
            if os.path.exists(c):
                return c
        return ""

    def _grep_with_rg(self, params: Dict[str, Any], abs_path: str, rg_path: str) -> ToolResult:
        cmd = [rg_path]
        if params.get("i"):
            cmd.append("-i")
        if params.get("n", True):
            cmd.append("-n")

        output_mode = params.get("output_mode", "files_with_matches")
        if output_mode == "files_with_matches":
            cmd.append("-l")
        elif output_mode == "count":
            cmd.append("-c")

        glob_pat = params.get("glob")
        if glob_pat:
            cmd.extend(["-g", glob_pat])
        type_pat = params.get("type")
        if type_pat:
            cmd.extend(["-t", type_pat])

        head_limit = params.get("head_limit", 250)
        cmd.extend(["-m", str(head_limit)])

        cmd.extend(["--", params["pattern"], abs_path])

        try:
            result = subprocess.run(cmd, capture_output=True, text=True, timeout=30, encoding="utf-8", errors="replace")
        except Exception as e:
            return ToolResult(success=False, error=f"ripgrep 执行失败: {e}")

        output = result.stdout or ""
        if result.returncode not in (0, 1):  # 1 表示无匹配
            return ToolResult(success=False, error=f"ripgrep 错误: {result.stderr}")

        if not output:
            return ToolResult(success=True, output="无匹配")

        # 限制输出长度
        if len(output) > 50000:
            output = output[:50000] + "\n\n[输出已截断]"

        return ToolResult(success=True, output=output.rstrip())

    def _grep_fallback(self, params: Dict[str, Any], abs_path: str) -> ToolResult:
        """无 ripgrep 时的简单回退实现。"""
        import re

        pattern = params["pattern"]
        flags = re.IGNORECASE if params.get("i") else 0
        try:
            regex = re.compile(pattern, flags)
        except re.error as e:
            return ToolResult(success=False, error=f"正则编译失败: {e}")

        glob_pat = params.get("glob", "*")
        output_mode = params.get("output_mode", "files_with_matches")
        show_line_num = params.get("n", True)
        head_limit = params.get("head_limit", 250)
        matches = []

        import fnmatch
        # 支持 ** 递归 glob：将 **/*.py 转为 fnmatch 可用的文件名匹配
        # 例如 **/*.py -> 只匹配 .py 后缀的文件名
        basename_pat = glob_pat
        if "/" in glob_pat or "\\" in glob_pat:
            # 提取最后一段作为文件名匹配模式（如 **/*.py -> *.py）
            basename_pat = glob_pat.rsplit("/", 1)[-1].rsplit("\\", 1)[-1]

        # 文件大小限制（回退模式下跳过超大文件）
        MAX_FILE_SIZE = 10 * 1024 * 1024  # 10MB

        for root, dirs, files in os.walk(abs_path):
            # 跳过常见无关目录
            dirs[:] = [d for d in dirs if d not in {".git", "node_modules", "__pycache__", ".venv", "venv"}]
            for fname in files:
                if not fnmatch.fnmatch(fname, basename_pat):
                    continue
                fpath = os.path.join(root, fname)
                # 跳过超大文件
                try:
                    if os.path.getsize(fpath) > MAX_FILE_SIZE:
                        continue
                except OSError:
                    continue
                try:
                    with open(fpath, "r", encoding="utf-8", errors="replace") as f:
                        file_match_count = 0  # count 模式下统计每个文件的匹配行数
                        for i, line in enumerate(f, 1):
                            if regex.search(line):
                                if output_mode == "files_with_matches":
                                    matches.append(fpath)
                                    break
                                elif output_mode == "count":
                                    file_match_count += 1
                                else:
                                    prefix = f"{fpath}:{i}:" if show_line_num else f"{fpath}:"
                                    matches.append(prefix + line.rstrip())
                                    if len(matches) >= head_limit:
                                        break
                        # count 模式：读完整个文件后输出计数
                        if output_mode == "count" and file_match_count > 0:
                            matches.append(f"{fpath}:{file_match_count}")
                            if len(matches) >= head_limit:
                                break
                except Exception:
                    continue
                if len(matches) >= head_limit:
                    break
            if len(matches) >= head_limit:
                break

        if not matches:
            return ToolResult(success=True, output="无匹配")
        return ToolResult(success=True, output="\n".join(matches))
