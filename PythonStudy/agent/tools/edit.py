"""Edit 工具：精确字符串替换。"""

import os
from typing import Any, Dict

from tools.base import BaseTool, ToolContext, ToolResult


class EditTool(BaseTool):
    name = "Edit"
    description = "对文件做精确字符串替换。old_string 必须在文件中唯一存在，否则报错。可用于重命名变量、修改代码块等。"
    parameters = {
        "type": "object",
        "properties": {
            "path": {"type": "string", "description": "文件路径"},
            "old_string": {"type": "string", "description": "要被替换的原字符串（必须唯一）"},
            "new_string": {"type": "string", "description": "替换后的新字符串"},
            "replace_all": {"type": "boolean", "description": "是否替换所有匹配，默认 false"},
        },
        "required": ["path", "old_string", "new_string"],
    }
    risk_level = "write"

    def execute(self, params: Dict[str, Any], context: ToolContext) -> ToolResult:
        path = params.get("path", "")
        old_string = params.get("old_string", "")
        new_string = params.get("new_string", "")
        replace_all = params.get("replace_all", False)

        if not path or not old_string:
            return ToolResult(success=False, error="path 和 old_string 参数必填")
        if old_string == new_string:
            return ToolResult(success=False, error="old_string 与 new_string 相同")

        abs_path = self._resolve(path, context.work_dir)
        if not os.path.exists(abs_path):
            return ToolResult(success=False, error=f"文件不存在: {abs_path}")

        try:
            with open(abs_path, "r", encoding="utf-8", errors="replace") as f:
                content = f.read()
        except Exception as e:
            return ToolResult(success=False, error=f"读取失败: {e}")

        # 检查匹配
        count = content.count(old_string)
        if count == 0:
            return ToolResult(success=False, error=f"未找到匹配的字符串。请确认 old_string 是否准确（包含正确缩进）")
        if count > 1 and not replace_all:
            return ToolResult(
                success=False,
                error=f"old_string 在文件中出现 {count} 次，非唯一。请提供更长的上下文使其唯一，或设置 replace_all=true",
            )

        # 检测原文件换行风格
        has_crlf = "\r\n" in content

        # 执行替换
        if replace_all:
            new_content = content.replace(old_string, new_string)
            replaced = count
        else:
            new_content = content.replace(old_string, new_string, 1)
            replaced = 1

        # 保留原文件换行风格：如果原文件用 CRLF，确保写回时也用 CRLF
        if has_crlf and "\r\n" not in new_content:
            new_content = new_content.replace("\n", "\r\n")

        try:
            with open(abs_path, "w", encoding="utf-8", newline="") as f:
                f.write(new_content)
        except Exception as e:
            return ToolResult(success=False, error=f"写回失败: {e}")

        return ToolResult(
            success=True,
            output=f"已替换 {replaced} 处: {abs_path}",
        )

    def _resolve(self, path: str, work_dir: str) -> str:
        if os.path.isabs(path):
            return os.path.normpath(path)
        return os.path.normpath(os.path.join(work_dir, path))
