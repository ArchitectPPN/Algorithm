"""
LLM 适配器：统一 OpenAI / Anthropic 两家 API

Agent 代码只调 adapter.chat()，不关心底层是哪家模型。

用法：
    adapter = OpenAIAdapter(api_key="...", model="deepseek-chat")
    response = adapter.chat(messages, tools)
    print(response.usage)   # 统一格式
    print(response.cost)    # 本次成本
"""
import os
import json
import requests
from dataclasses import dataclass, field
from typing import Optional


# ── 统一数据模型 ──

@dataclass
class Usage:
    """统一的 token 用量"""
    input_tokens: int = 0
    output_tokens: int = 0
    total_tokens: int = 0
    cache_hit: int = 0  # 缓存命中的 token 数

    def __add__(self, other):
        return Usage(
            input_tokens=self.input_tokens + other.input_tokens,
            output_tokens=self.output_tokens + other.output_tokens,
            total_tokens=self.total_tokens + other.total_tokens,
            cache_hit=self.cache_hit + other.cache_hit,
        )


@dataclass
class LLMResponse:
    """统一的模型响应"""
    message: dict           # 模型返回的 message（含 content / tool_calls）
    usage: Usage            # token 用量
    cost: float             # 本次请求成本（元）
    model: str              # 实际用的模型名
    finish_reason: str      # stop / tool_calls / end_turn / tool_use


# ── 模型价格表（每千 token，单位：元）──
# 来源：各模型官网，定期更新
MODEL_PRICING = {
    # DeepSeek
    "deepseek-chat": {"input": 0.001, "output": 0.002},
    "deepseek-reasoner": {"input": 0.004, "output": 0.016},
    # OpenAI
    "gpt-4o-mini": {"input": 0.015, "output": 0.06},
    "gpt-4o": {"input": 0.03, "output": 0.06},
    # Claude
    "claude-sonnet-5": {"input": 0.03, "output": 0.15},
    "claude-haiku-4-5-20251001": {"input": 0.001, "output": 0.005},
}


def calc_cost(model: str, usage: Usage) -> float:
    """根据模型价格和 token 用量计算成本"""
    pricing = MODEL_PRICING.get(model)
    if not pricing:
        return 0.0  # 未知模型不计算
    input_cost = usage.input_tokens / 1000 * pricing["input"]
    output_cost = usage.output_tokens / 1000 * pricing["output"]
    # 缓存命中更便宜（通常 50% 折扣）
    if usage.cache_hit > 0:
        cache_discount = 0.5
        input_cost -= usage.cache_hit / 1000 * pricing["input"] * cache_discount
    return round(input_cost + output_cost, 6)


# ── 适配器基类 ──

class LLMAdapter:
    """所有适配器的基类，定义统一接口"""

    def chat(self, messages: list, tools: list = None, temperature: float = 0, **kwargs) -> LLMResponse:
        raise NotImplementedError

    def get_total_usage(self) -> Usage:
        """获取累计 token 用量"""
        return self._total_usage

    def get_total_cost(self) -> float:
        """获取累计成本"""
        return self._total_cost

    def reset_stats(self):
        """重置统计"""
        self._total_usage = Usage()
        self._total_cost = 0.0


# ── OpenAI 兼容适配器 ──
# 覆盖：OpenAI / DeepSeek / 通义 / 智谱 / Kimi / 所有 OpenAI 兼容模型

class OpenAIAdapter(LLMAdapter):
    """
    OpenAI 兼容格式适配器

    参数：
        api_key: API 密钥
        model: 模型名（如 deepseek-chat / gpt-4o-mini）
        base_url: API 地址（不同厂商不同）
    """

    # 各厂商默认 base_url
    DEFAULT_BASE_URLS = {
        "openai": "https://api.openai.com/v1",
        "deepseek": "https://api.deepseek.com",
    }

    def __init__(self, api_key: str, model: str, base_url: str = None):
        self.api_key = api_key
        self.model = model
        # 自动推断 base_url（如果没传）
        if base_url:
            self.base_url = base_url.rstrip("/")
        elif "deepseek" in model:
            self.base_url = self.DEFAULT_BASE_URLS["deepseek"]
        else:
            self.base_url = self.DEFAULT_BASE_URLS["openai"]
        self.url = f"{self.base_url}/chat/completions"
        self.headers = {
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json",
        }
        self._total_usage = Usage()
        self._total_cost = 0.0

    def chat(self, messages: list, tools: list = None, temperature: float = 0, **kwargs) -> LLMResponse:
        """调 chat API，返回统一格式"""
        body = {
            "model": self.model,
            "messages": messages,
            "temperature": temperature,
        }
        if tools:
            body["tools"] = tools
        body.update(kwargs)  # 允许传额外参数（如 max_tokens）

        resp = requests.post(self.url, headers=self.headers, json=body)
        resp.raise_for_status()
        data = resp.json()

        # 提取 message
        message = data["choices"][0]["message"]

        # 提取 usage（统一格式）
        raw_usage = data.get("usage", {})
        usage = Usage(
            input_tokens=raw_usage.get("prompt_tokens", 0),
            output_tokens=raw_usage.get("completion_tokens", 0),
            total_tokens=raw_usage.get("total_tokens", 0),
            cache_hit=raw_usage.get("prompt_cache_hit_tokens",
                      raw_usage.get("prompt_tokens_details", {}).get("cached_tokens", 0)),
        )

        # 计算成本
        cost = calc_cost(self.model, usage)

        # finish_reason
        finish_reason = data["choices"][0].get("finish_reason", "unknown")

        # 累计统计
        self._total_usage = self._total_usage + usage
        self._total_cost += cost

        return LLMResponse(
            message=message,
            usage=usage,
            cost=cost,
            model=data.get("model", self.model),
            finish_reason=finish_reason,
        )


# ── Anthropic 适配器（壳子，以后填）──

class AnthropicAdapter(LLMAdapter):
    """
    Anthropic (Claude) 适配器

    TODO: 实现以下转换：
    - system prompt 从 messages 提出来放顶层
    - tool 定义字段名转换（parameters → input_schema）
    - tool 结果从 role:tool 转成 role:user + tool_result 块
    - 响应里 tool_use 从 content 数组提取
    - usage 字段名转换（prompt_tokens → input_tokens）
    """

    def __init__(self, api_key: str, model: str = "claude-sonnet-5"):
        self.api_key = api_key
        self.model = model
        self.url = "https://api.anthropic.com/v1/messages"
        self.headers = {
            "x-api-key": api_key,
            "anthropic-version": "2023-06-01",
            "Content-Type": "application/json",
        }
        self._total_usage = Usage()
        self._total_cost = 0.0

    def chat(self, messages: list, tools: list = None, temperature: float = 0, **kwargs) -> LLMResponse:
        # TODO: 实现完整的 Anthropic 格式转换
        raise NotImplementedError("Anthropic 适配器待实现，请使用 OpenAIAdapter")


# ── 工厂函数：根据配置创建适配器 ──

def create_adapter(provider: str = None, api_key: str = None, model: str = None, base_url: str = None) -> LLMAdapter:
    """
    根据配置创建适配器

    provider: "openai" / "anthropic" / "deepseek"（自动映射到 OpenAIAdapter）
    api_key: API 密钥
    model: 模型名
    base_url: 自定义 API 地址（可选）
    """
    if not provider:
        # 根据 model 名自动推断
        if model and "claude" in model:
            provider = "anthropic"
        else:
            provider = "openai"

    if not api_key:
        # 从环境变量读
        if provider == "anthropic":
            api_key = os.environ.get("ANTHROPIC_API_KEY", "")
        else:
            api_key = os.environ.get("DEEPSEEK_API_KEY",
                    os.environ.get("OPENAI_API_KEY", ""))

    if provider == "anthropic":
        return AnthropicAdapter(api_key=api_key, model=model or "claude-sonnet-5")
    else:
        return OpenAIAdapter(api_key=api_key, model=model or "deepseek-chat", base_url=base_url)
