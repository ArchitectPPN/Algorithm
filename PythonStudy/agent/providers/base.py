"""BaseProvider 抽象类与事件类型定义。"""

from abc import ABC, abstractmethod
from dataclasses import dataclass
from typing import Iterator, List, Optional, Dict, Any

from core.message import Message, ToolCall


@dataclass
class ProviderEvent:
    """Provider 流式事件"""
    type: str  # text_delta / tool_call_start / tool_call_delta / done / error
    text: Optional[str] = None                  # text_delta 时的文本片段
    tool_call: Optional[ToolCall] = None        # tool_call_start 时的完整工具调用
    error: Optional[str] = None                 # error 时的错误信息


class BaseProvider(ABC):
    """所有 Provider 的抽象基类"""

    @abstractmethod
    def chat(
        self,
        messages: List[Message],
        tools: List[Dict[str, Any]],
        system_prompt: str,
        model: Optional[str] = None,
    ) -> Iterator[ProviderEvent]:
        """流式对话。

        参数：
            messages: 对话历史（不含 system）
            tools: 工具定义列表（OpenAI 格式）
            system_prompt: 系统提示
            model: 指定模型，None 则用默认

        返回：ProviderEvent 迭代器
        """
        ...

    @abstractmethod
    def summarize(self, messages: List[Message], model: Optional[str] = None) -> str:
        """对消息列表做摘要，用于 ContextManager 压缩历史"""
        ...

    @abstractmethod
    def list_models(self) -> List[str]:
        """列出可用模型"""
        ...

    @abstractmethod
    def get_current_model(self) -> str:
        """获取当前模型"""
        ...
