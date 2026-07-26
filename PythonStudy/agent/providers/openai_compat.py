"""OpenAI 兼容 Provider。

适用于 modelscope / DeepSeek / Qwen / GLM 兼容端点 / OpenAI 官方 等。
走 /v1/chat/completions 流式接口 + 标准 tool calling。
"""

import json
import time
from typing import Iterator, List, Optional, Dict, Any

from openai import OpenAI, RateLimitError, APIError, AuthenticationError, BadRequestError

from core.message import Message, ToolCall
from providers.base import BaseProvider, ProviderEvent


class OpenAICompatProvider(BaseProvider):
    def __init__(
        self,
        base_url: str,
        api_key: str,
        models: List[str],
        default_model_index: int = 0,
        frequency_penalty: float = 0.0,
    ):
        self.client = OpenAI(base_url=base_url, api_key=api_key)
        self.models = models
        self.current_model_index = default_model_index
        # 每个模型限流后的冷却时间戳
        self._rate_limit_until: Dict[str, float] = {}
        # 重复惩罚参数（0.0 - 2.0，>0 时惩罚重复 token，缓解模型退化重复）
        self.frequency_penalty = frequency_penalty

    def _pick_model(self, requested: Optional[str] = None) -> str:
        """选择可用模型。优先 requested，否则按 current_model_index。
        遇到限流冷却中的模型自动跳到下一个。
        """
        if requested:
            return requested
        now = time.time()
        for i in range(len(self.models)):
            idx = (self.current_model_index + i) % len(self.models)
            m = self.models[idx]
            if self._rate_limit_until.get(m, 0) < now:
                return m
        # 全部限流中，返回第一个
        return self.models[self.current_model_index]

    def _mark_rate_limited(self, model: str, cool_seconds: int = 60):
        self._rate_limit_until[model] = time.time() + cool_seconds

    def chat(
        self,
        messages: List[Message],
        tools: List[Dict[str, Any]],
        system_prompt: str,
        model: Optional[str] = None,
    ) -> Iterator[ProviderEvent]:
        """流式对话。自动处理限流切换，使用指数退避。"""
        # 尝试所有模型，限流则切换下一个
        tried_models = set()
        last_error = None
        for attempt in range(len(self.models)):
            m = self._pick_model(model)
            if m in tried_models:
                # 指数退避：1s, 2s, 4s, 8s...，最大 30s
                wait_time = min(2 ** (attempt - 1), 30)
                time.sleep(wait_time)
            tried_models.add(m)
            try:
                yield from self._chat_once(m, messages, tools, system_prompt)
                # 成功则更新当前模型
                self.current_model_index = self.models.index(m)
                return
            except RateLimitError as e:
                last_error = f"模型 {m} 限流: {e}"
                self._mark_rate_limited(m)
                continue
            except BadRequestError as e:
                # 模型不支持 tools 等问题，直接报错
                yield ProviderEvent(type="error", error=f"请求错误（{m}）: {e}")
                return
            except (APIError, AuthenticationError) as e:
                last_error = f"模型 {m} API 错误: {e}"
                continue
        yield ProviderEvent(type="error", error=last_error or "所有模型均不可用")

    def _chat_once(
        self,
        model: str,
        messages: List[Message],
        tools: List[Dict[str, Any]],
        system_prompt: str,
    ) -> Iterator[ProviderEvent]:
        """单次流式请求。"""
        # 构造请求消息
        req_messages = [{"role": "system", "content": system_prompt}]
        for m in messages:
            req_messages.append(m.to_dict())

        kwargs: Dict[str, Any] = {
            "model": model,
            "messages": req_messages,
            "stream": True,
        }
        if tools:
            kwargs["tools"] = tools
        if self.frequency_penalty > 0:
            kwargs["frequency_penalty"] = self.frequency_penalty

        stream = self.client.chat.completions.create(**kwargs)

        # 收集 tool_calls（流式分片，需聚合）
        tool_call_chunks: Dict[int, Dict[str, Any]] = {}

        for chunk in stream:
            if not chunk.choices:
                continue
            delta = chunk.choices[0].delta
            if delta is None:
                continue
            # 文本流
            if delta.content:
                yield ProviderEvent(type="text_delta", text=delta.content)
            # 工具调用流式分片
            if delta.tool_calls:
                for tc in delta.tool_calls:
                    idx = tc.index
                    if idx not in tool_call_chunks:
                        tool_call_chunks[idx] = {
                            "id": tc.id or "",
                            "name": tc.function.name if tc.function and tc.function.name else "",
                            "arguments_str": "",
                        }
                    if tc.id:
                        tool_call_chunks[idx]["id"] = tc.id
                    if tc.function and tc.function.name:
                        tool_call_chunks[idx]["name"] = tc.function.name
                    if tc.function and tc.function.arguments:
                        tool_call_chunks[idx]["arguments_str"] += tc.function.arguments

        # 聚合完成后发出 tool_call 事件
        for idx in sorted(tool_call_chunks.keys()):
            tc_data = tool_call_chunks[idx]
            args_str = tc_data["arguments_str"]
            try:
                args = json.loads(args_str) if args_str else {}
            except json.JSONDecodeError:
                args = {"_raw_arguments": args_str}
            yield ProviderEvent(
                type="tool_call",
                tool_call=ToolCall(id=tc_data["id"], name=tc_data["name"], arguments=args),
            )

        yield ProviderEvent(type="done")

    def summarize(self, messages: List[Message], model: Optional[str] = None) -> str:
        """调用模型对消息做摘要。"""
        m = self._pick_model(model)
        to_summarize = [
            {"role": "system", "content": "请将以下对话历史压缩为简洁的摘要，保留关键事实、决策和未完成的事项。不要丢失任何技术细节。"},
        ]
        for msg in messages:
            to_summarize.append({"role": msg.role, "content": msg.content or ""})

        try:
            resp = self.client.chat.completions.create(
                model=m,
                messages=to_summarize,
                stream=False,
            )
            return resp.choices[0].message.content or ""
        except Exception as e:
            # 摘要失败则简单截断
            return f"[摘要失败，已截断] {str(e)[:200]}"

    def list_models(self) -> List[str]:
        return list(self.models)

    def get_current_model(self) -> str:
        return self.models[self.current_model_index]

    def set_current_model(self, index: int):
        if 0 <= index < len(self.models):
            self.current_model_index = index
