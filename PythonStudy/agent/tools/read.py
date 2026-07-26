"""Read 工具：读取文件内容。"""

import os
from typing import Any, Dict

from tools.base import BaseTool, ToolContext, ToolResult


# 常见二进制文件扩展名
BINARY_EXTENSIONS = {
    # 图片
    ".png", ".jpg", ".jpeg", ".gif", ".bmp", ".ico", ".webp", ".svg", ".tiff", ".tif",
    # 音视频
    ".mp3", ".mp4", ".wav", ".avi", ".mkv", ".flv", ".wmv", ".mov", ".ogg", ".flac",
    # 压缩包
    ".zip", ".rar", ".7z", ".tar", ".gz", ".bz2", ".xz", ".zst",
    # 可执行文件
    ".exe", ".dll", ".so", ".dylib", ".bin", ".msi",
    # 编译产物
    ".pyc", ".pyo", ".o", ".obj", ".class", ".jar", ".war",
    # 数据库
    ".db", ".sqlite", ".sqlite3",
    # 文档格式
    ".pdf", ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx",
    # 字体
    ".ttf", ".otf", ".woff", ".woff2",
    # 其他
    ".pkl", ".npy", ".npz", ".h5", ".hdf5",
}


class ReadTool(BaseTool):
    name = "Read"
    description = "读取本地文件内容。可指定行号范围。默认返回带行号格式。"
    parameters = {
        "type": "object",
        "properties": {
            "path": {"type": "string", "description": "文件绝对路径或相对路径"},
            "offset": {"type": "integer", "description": "起始行号（从 1 开始），不填默认 1"},
            "limit": {"type": "integer", "description": "读取行数，不填默认全部"},
        },
        "required": ["path"],
    }
    risk_level = "read_only"

    def execute(self, params: Dict[str, Any], context: ToolContext) -> ToolResult:
        path = params.get("path", "")
        offset = params.get("offset", 1)
        limit = params.get("limit")

        if not path:
            return ToolResult(success=False, error="path 参数必填")

        abs_path = self._resolve(path, context.work_dir)
        if not os.path.exists(abs_path):
            return ToolResult(success=False, error=f"文件不存在: {abs_path}")
        if os.path.isdir(abs_path):
            return ToolResult(success=False, error=f"路径是目录而非文件: {abs_path}")

        # 二进制文件检测
        if self._is_binary_file(abs_path):
            file_size = os.path.getsize(abs_path)
            return ToolResult(
                success=False,
                error=f"文件是二进制格式，无法以文本方式读取（大小: {self._format_size(file_size)}）",
            )

        # 大文件警告（超过 10MB）
        file_size = os.path.getsize(abs_path)
        if file_size > 10 * 1024 * 1024:
            return ToolResult(
                success=False,
                error=f"文件过大（{self._format_size(file_size)}），超过 10MB 限制。请使用 offset/limit 参数分段读取，或用 Grep 搜索特定内容",
            )

        # 大文件且指定了 offset/limit 时，按需读取避免全量加载
        if limit and file_size > 1024 * 1024:  # 超过 1MB 且指定了 limit
            lines, total_lines = self._read_lines_range(abs_path, offset, limit)
            if lines is None:
                # 回退到全量读取
                try:
                    with open(abs_path, "r", encoding="utf-8", errors="replace") as f:
                        all_lines = f.readlines()
                except Exception as e:
                    return ToolResult(success=False, error=f"读取失败: {e}")
                total_lines = len(all_lines)
                start = max(1, offset) - 1
                end = start + limit
                lines = all_lines[start:end]
                start_display = start + 1
                end_display = min(end, total_lines)
            else:
                start_display = max(1, offset)
                end_display = start_display + len(lines) - 1
        else:
            try:
                with open(abs_path, "r", encoding="utf-8", errors="replace") as f:
                    all_lines = f.readlines()
            except Exception as e:
                return ToolResult(success=False, error=f"读取失败: {e}")
            total_lines = len(all_lines)
            start = max(1, offset) - 1
            end = start + limit if limit else total_lines
            lines = all_lines[start:end]
            start_display = start + 1
            end_display = min(end, total_lines)

        # 带行号格式输出
        numbered = []
        for i, line in enumerate(lines, start=start_display):
            numbered.append(f"{i:>6}\t{line.rstrip()}")
        output = "\n".join(numbered)

        # 输出过长截断
        if len(output) > 50000:
            output = output[:50000] + f"\n\n[输出已截断，共 {total_lines} 行]"

        return ToolResult(
            success=True,
            output=f"文件: {abs_path} (共 {total_lines} 行，显示 {start_display}-{end_display})\n\n{output}",
        )

    def _is_binary_file(self, abs_path: str) -> bool:
        """检测文件是否为二进制格式。先检查扩展名，再检查文件内容。"""
        # 1. 扩展名检查
        _, ext = os.path.splitext(abs_path)
        if ext.lower() in BINARY_EXTENSIONS:
            return True

        # 2. 内容检查：读取前 8KB，如果包含大量 NULL 字节则判定为二进制
        try:
            with open(abs_path, "rb") as f:
                chunk = f.read(8192)
            if not chunk:
                return False  # 空文件视为文本
            # 统计 NULL 字节比例
            null_count = chunk.count(b"\x00")
            if null_count / len(chunk) > 0.1:  # 超过 10% NULL 字节
                return True
        except Exception:
            pass

        return False

    @staticmethod
    def _format_size(size: int) -> str:
        """格式化文件大小。"""
        if size < 1024:
            return f"{size} B"
        elif size < 1024 * 1024:
            return f"{size / 1024:.1f} KB"
        elif size < 1024 * 1024 * 1024:
            return f"{size / (1024 * 1024):.1f} MB"
        return f"{size / (1024 * 1024 * 1024):.1f} GB"

    @staticmethod
    def _read_lines_range(abs_path: str, offset: int, limit: int):
        """按需读取指定行范围，避免全量加载大文件。

        返回 (lines_list, total_line_count)，失败返回 (None, 0)。
        """
        try:
            lines = []
            total_lines = 0
            start = max(1, offset) - 1
            end = start + limit
            with open(abs_path, "r", encoding="utf-8", errors="replace") as f:
                for i, line in enumerate(f):
                    total_lines = i + 1
                    if i >= start and i < end:
                        lines.append(line)
                    if i >= end:
                        # 继续计数以获取总行数（但最多多读 1000 行避免太慢）
                        # 如果文件很大，后续行数用估算
                        if total_lines > end + 1000:
                            # 快速估算剩余行数
                            f.seek(0, 2)  # 到文件末尾
                            file_size = f.tell()
                            avg_line_len = f.tell() / total_lines if total_lines > 0 else 80
                            total_lines = int(file_size / avg_line_len)
                            break
                        continue
            return lines, total_lines
        except Exception:
            return None, 0
