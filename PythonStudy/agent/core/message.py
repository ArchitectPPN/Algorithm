"""消息数据结构定义。

统一消息格式，兼容 OpenAI 和 Anthropic 两个 Provider：
- user: {role, content}
- assistant: {role, content, tool_calls?}
- tool: {role, tool_call_id, name, content}
- system: 单独传给 Provider，不放入 messages 列表
"""

import json
from dataclasses import dataclass, field
from typing import List, Optional, Dict, Any


@dataclass
class ToolCall:
    """模型发起的工具调用"""
    id: str               # 工具调用 ID（由 Provider 生成）
    name: str             # 工具名
    arguments: Dict[str, Any]  # 参数（已解析的 dict）


@dataclass
class Message:
    """统一消息结构"""
    role: str                              # user / assistant / tool / summary
    content: Optional[str] = None          # 文本内容
    tool_calls: Optional[List[ToolCall]] = None  # assistant 发起的工具调用
    tool_call_id: Optional[str] = None     # tool 消息对应的 tool_call id
    name: Optional[str] = None             # tool 消息对应的工具名

    def to_dict(self) -> Dict[str, Any]:
        """转换为 OpenAI 兼容消息 dict。

        注意：tool_calls.function.arguments 必须是 JSON 字符串（不是 dict），
        否则部分 OpenAI 兼容服务器（如 modelscope）会报 500 错误。
        """
        d = {"role": self.role}
        if self.content is not None:
            d["content"] = self.content
        if self.tool_calls:
            d["tool_calls"] = [
                {
                    "id": tc.id,
                    "type": "function",
                    "function": {
                        "name": tc.name,
                        "arguments": tc.arguments if isinstance(tc.arguments, str)
                                     else json.dumps(tc.arguments, ensure_ascii=False),
                    },
                }
                for tc in self.tool_calls
            ]
        if self.tool_call_id is not None:
            d["tool_call_id"] = self.tool_call_id
        if self.name is not None:
            d["name"] = self.name
        return d

    @classmethod
    def user(cls, content: str) -> "Message":
        return cls(role="user", content=content)

    @classmethod
    def assistant(cls, content: Optional[str] = None, tool_calls: Optional[List[ToolCall]] = None) -> "Message":
        return cls(role="assistant", content=content, tool_calls=tool_calls)

    @classmethod
    def tool(cls, tool_call_id: str, name: str, content: str) -> "Message":
        return cls(role="tool", tool_call_id=tool_call_id, name=name, content=content)

    @classmethod
    def summary(cls, content: str) -> "Message":
        """压缩后的历史摘要"""
        return cls(role="user", content=f"[对话历史摘要]\n{content}")
