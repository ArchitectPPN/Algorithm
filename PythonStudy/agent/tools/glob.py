"""Glob 工具：按文件名模式匹配。"""

import fnmatch
import os
from typing import Any, Dict, List

from tools.base import BaseTool, ToolContext, ToolResult


# 递归搜索时排除的目录名
EXCLUDED_DIRS = {
    ".git", ".svn", ".hg",           # 版本控制
    "node_modules", "bower_components",  # JS 依赖
    "__pycache__", ".pytest_cache",   # Python 缓存
    ".venv", "venv", "env",           # Python 虚拟环境
    ".tox", ".mypy_cache",           # Python 工具缓存
    "dist", "build", ".eggs",         # Python 打包产物
    ".idea", ".vscode",              # IDE 配置
    ".next", ".nuxt",                # 前端框架缓存
    "target",                         # Java/Rust 构建产物
    ".gradle",                        # Gradle 缓存
}


class GlobTool(BaseTool):
    name = "Glob"
    description = "按文件名模式快速匹配文件路径。支持 ** 递归。返回按修改时间排序的文件列表。"
    parameters = {
        "type": "object",
        "properties": {
            "pattern": {"type": "string", "description": "glob 模式，如 '**/*.py' 或 'src/**/*.ts'"},
            "path": {"type": "string", "description": "搜索根目录，默认当前工作目录"},
        },
        "required": ["pattern"],
    }
    risk_level = "read_only"

    def execute(self, params: Dict[str, Any], context: ToolContext) -> ToolResult:
        pattern = params.get("pattern", "")
        if not pattern:
            return ToolResult(success=False, error="pattern 参数必填")

        search_path = params.get("path") or context.work_dir
        abs_root = search_path if os.path.isabs(search_path) else os.path.join(context.work_dir, search_path)
        if not os.path.exists(abs_root):
            return ToolResult(success=False, error=f"路径不存在: {abs_root}")

        # 使用 os.walk 手动递归，排除无关目录
        files = self._walk_and_match(abs_root, pattern)

        # 按修改时间倒序
        files.sort(key=lambda p: os.path.getmtime(p), reverse=True)

        if not files:
            return ToolResult(success=True, output="无匹配文件")

        # 限制输出数量
        if len(files) > 500:
            files = files[:500]
            truncated = "\n\n[输出已截断，仅显示前 500 个]"
        else:
            truncated = ""

        output = "\n".join(files) + truncated
        return ToolResult(success=True, output=output)

    def _walk_and_match(self, root: str, pattern: str) -> List[str]:
        """手动递归遍历目录，排除无关目录，匹配文件名模式。"""
        # 提取文件名匹配模式（如 **/*.py -> *.py）
        basename_pat = pattern
        if "/" in pattern or "\\" in pattern:
            basename_pat = pattern.rsplit("/", 1)[-1].rsplit("\\", 1)[-1]

        matched_files = []
        for dirpath, dirnames, filenames in os.walk(root):
            # 排除无关目录（原地修改 dirnames 影响 os.walk 的递归）
            dirnames[:] = [d for d in dirnames if d not in EXCLUDED_DIRS and not d.startswith(".")]

            for fname in filenames:
                if fnmatch.fnmatch(fname, basename_pat):
                    full_path = os.path.join(dirpath, fname)
                    if os.path.isfile(full_path):
                        matched_files.append(full_path)

        return matched_files
