"""Anthropic 原生 Provider。

走 /v1/messages 接口 + tool_use。系统提示单独传 system=。
"""

import json
from typing import Iterator, List, Optional, Dict, Any

try:
    import anthropic
    from anthropic import Anthropic
    HAS_ANTHROPIC = True
except ImportError:
    HAS_ANTHROPIC = False
    Anthropic = None  # type: ignore

from core.message import Message, ToolCall
from providers.base import BaseProvider, ProviderEvent


class AnthropicProvider(BaseProvider):
    def __init__(self, api_key: str, models: List[str], default_model_index: int = 0):
        if not HAS_ANTHROPIC:
            raise ImportError("未安装 anthropic SDK，请运行 pip install anthropic")
        self.client = Anthropic(api_key=api_key)
        self.models = models
        self.current_model_index = default_model_index

    def chat(
        self,
        messages: List[Message],
        tools: List[Dict[str, Any]],
        system_prompt: str,
        model: Optional[str] = None,
    ) -> Iterator[ProviderEvent]:
        m = model or self.models[self.current_model_index]

        # 转换消息格式：OpenAI -> Anthropic
        anthropic_messages = []
        for msg in messages:
            if msg.role == "user":
                anthropic_messages.append({"role": "user", "content": msg.content or ""})
            elif msg.role == "assistant":
                content_blocks = []
                if msg.content:
                    content_blocks.append({"type": "text", "text": msg.content})
                if msg.tool_calls:
                    for tc in msg.tool_calls:
                        content_blocks.append({
                            "type": "tool_use",
                            "id": tc.id,
                            "name": tc.name,
                            "input": tc.arguments,
                        })
                anthropic_messages.append({"role": "assistant", "content": content_blocks})
            elif msg.role == "tool":
                anthropic_messages.append({
                    "role": "user",
                    "content": [{
                        "type": "tool_result",
                        "tool_use_id": msg.tool_call_id,
                        "content": msg.content or "",
                    }],
                })

        # 转换工具格式
        anthropic_tools = []
        for t in tools:
            if t.get("type") == "function":
                fn = t.get("function", {})
                anthropic_tools.append({
                    "name": fn.get("name"),
                    "description": fn.get("description"),
                    "input_schema": fn.get("parameters", {"type": "object", "properties": {}}),
                })

        kwargs: Dict[str, Any] = {
            "model": m,
            "system": system_prompt,
            "messages": anthropic_messages,
            "tools": anthropic_tools,
            "max_tokens": 8192,
        }

        try:
            with self.client.messages.stream(**kwargs) as stream:
                for event in stream:
                    if event.type == "content_block_delta":
                        if event.delta.type == "text_delta":
                            yield ProviderEvent(type="text_delta", text=event.delta.text)
                    # 忽略 message_start / content_block_start / content_block_stop 等事件
                    # message_stop 时自然退出循环，由 get_final_message 获取完整结果

                # 流结束后取完整消息拿 tool_use
                final = stream.get_final_message()
                for block in final.content:
                    if block.type == "tool_use":
                        yield ProviderEvent(
                            type="tool_call",
                            tool_call=ToolCall(
                                id=block.id,
                                name=block.name,
                                arguments=block.input if isinstance(block.input, dict) else {},
                            ),
                        )
            yield ProviderEvent(type="done")
        except Exception as e:
            yield ProviderEvent(type="error", error=f"Anthropic API 错误: {e}")

    def summarize(self, messages: List[Message], model: Optional[str] = None) -> str:
        m = model or self.models[self.current_model_index]
        to_summarize = []
        for msg in messages:
            content = msg.content or ""
            if msg.tool_calls:
                content += f"\n[调用工具: {', '.join(tc.name for tc in msg.tool_calls)}]"
            to_summarize.append({"role": msg.role if msg.role in ("user", "assistant") else "user", "content": content})

        try:
            resp = self.client.messages.create(
                model=m,
                system="请将以下对话历史压缩为简洁的摘要，保留关键事实、决策和未完成的事项。不要丢失任何技术细节。",
                messages=to_summarize,
                max_tokens=2048,
            )
            return resp.content[0].text if resp.content else ""
        except Exception as e:
            return f"[摘要失败，已截断] {str(e)[:200]}"

    def list_models(self) -> List[str]:
        return list(self.models)

    def get_current_model(self) -> str:
        return self.models[self.current_model_index]

    def set_current_model(self, index: int):
        if 0 <= index < len(self.models):
            self.current_model_index = index
