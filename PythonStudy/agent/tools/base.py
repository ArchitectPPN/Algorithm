"""BaseTool 抽象类与相关数据结构。"""

from abc import ABC, abstractmethod
from dataclasses import dataclass, field
from typing import Any, Dict, Optional

@dataclass
class ToolContext:
    """工具执行上下文"""
    work_dir: str                                # 当前工作目录
    session_id: str                              # 会话 ID
    permission_engine: Any                       # 权限引擎引用（避免循环导入用 Any）
    provider: Any                                # Provider 引用（Task 工具需要）
    tools: Dict[str, Any]                        # 工具字典引用（Task 工具派生子 agent 需要）
    skill_loader: Any = None                     # 技能加载器引用
    permission_callback: Any = None              # 权限弹窗回调函数：fn(tool_name, params) -> bool


@dataclass
class ToolResult:
    """工具执行结果"""
    success: bool
    output: str = ""
    error: Optional[str] = None

    def to_str(self) -> str:
        if self.success:
            return self.output
        return f"[ERROR] {self.error or '未知错误'}\n{self.output}"


class BaseTool(ABC):
    """所有工具的抽象基类"""

    name: str = ""
    description: str = ""
    parameters: Dict[str, Any] = {}  # JSON Schema
    risk_level: str = "read_only"   # read_only / write / destructive

    @abstractmethod
    def execute(self, params: Dict[str, Any], context: ToolContext) -> ToolResult:
        """执行工具。子类必须实现。"""
        ...

    def to_openai_schema(self) -> Dict[str, Any]:
        """转换为 OpenAI tool calling 格式"""
        return {
            "type": "function",
            "function": {
                "name": self.name,
                "description": self.description,
                "parameters": self.parameters,
            },
        }

    def to_anthropic_schema(self) -> Dict[str, Any]:
        """转换为 Anthropic tool calling 格式"""
        return {
            "name": self.name,
            "description": self.description,
            "input_schema": self.parameters,
        }
