"""Write 工具：写入文件（覆盖）。"""

import os
from typing import Any, Dict

from tools.base import BaseTool, ToolContext, ToolResult


class WriteTool(BaseTool):
    name = "Write"
    description = "将内容写入文件（覆盖原内容）。会自动创建父目录。写前应先 Read 文件了解内容。"
    parameters = {
        "type": "object",
        "properties": {
            "path": {"type": "string", "description": "文件路径"},
            "content": {"type": "string", "description": "要写入的内容（完整覆盖）"},
        },
        "required": ["path", "content"],
    }
    risk_level = "write"

    def execute(self, params: Dict[str, Any], context: ToolContext) -> ToolResult:
        path = params.get("path", "")
        content = params.get("content", "")

        if not path:
            return ToolResult(success=False, error="path 参数必填")

        abs_path = self._resolve(path, context.work_dir)

        try:
            parent_dir = os.path.dirname(abs_path)
            if parent_dir:
                os.makedirs(parent_dir, exist_ok=True)
            with open(abs_path, "w", encoding="utf-8", newline="") as f:
                f.write(content)
        except Exception as e:
            return ToolResult(success=False, error=f"写入失败: {e}")

        line_count = content.count("\n") + (1 if content and not content.endswith("\n") else 0)
        return ToolResult(
            success=True,
            output=f"已写入: {abs_path} ({len(content)} 字符, {line_count} 行)",
        )

    def _resolve(self, path: str, work_dir: str) -> str:
        if os.path.isabs(path):
            return os.path.normpath(path)
        return os.path.normpath(os.path.join(work_dir, path))
