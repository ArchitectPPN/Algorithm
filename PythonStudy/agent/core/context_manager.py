"""上下文管理器。

监控消息总长度，超阈值时调用 Provider 做摘要压缩。
"""

from typing import List, Optional

from core.message import Message
from providers.base import BaseProvider


class ContextManager:
    def __init__(
        self,
        provider: BaseProvider,
        max_tokens: int = 60000,         # 模型上下文窗口大小（保守默认 60k）
        compress_threshold: float = 0.7, # 超过 70% 触发压缩
        keep_recent_rounds: int = 10,    # 压缩时保留最近 N 轮对话
    ):
        self.provider = provider
        self.max_tokens = max_tokens
        self.compress_threshold = compress_threshold
        self.keep_recent_rounds = keep_recent_rounds

    def estimate_tokens(self, messages: List[Message]) -> int:
        """估算消息列表的 token 数。

        估算策略：
        - 中文字符：1 字 ≈ 1.5 token
        - 英文/数字：4 字符 ≈ 1 token
        - 混合内容：字符数 / 2（折中）
        - 工具调用：额外计算 arguments 内容
        - 每条消息 +15 元数据开销（role、格式化等）
        """
        total = 0
        for msg in messages:
            if msg.content:
                total += self._estimate_text_tokens(msg.content) + 15
            else:
                total += 5  # 空消息仍有元数据开销
            if msg.tool_calls:
                for tc in msg.tool_calls:
                    # 工具名 + ID 开销
                    total += len(tc.name) + 20
                    # arguments 内容
                    if tc.arguments:
                        args_str = str(tc.arguments) if not isinstance(tc.arguments, str) else tc.arguments
                        total += self._estimate_text_tokens(args_str)
            # tool 消息的 tool_call_id 和 name 开销
            if msg.tool_call_id:
                total += 10
            if msg.name:
                total += len(msg.name) + 5
        return total

    @staticmethod
    def _estimate_text_tokens(text: str) -> int:
        """估算单段文本的 token 数。区分中英文，更精确。"""
        if not text:
            return 0
        # 统计中文字符数（CJK 统一汉字范围）
        chinese_count = 0
        for ch in text:
            if '\u4e00' <= ch <= '\u9fff' or '\u3400' <= ch <= '\u4dbf':
                chinese_count += 1
        non_chinese_len = len(text) - chinese_count
        # 中文 1 字 ≈ 1.5 token，英文 4 字符 ≈ 1 token
        return int(chinese_count * 1.5 + non_chinese_len / 4)

    def should_compress(self, messages: List[Message]) -> bool:
        return self.estimate_tokens(messages) > self.max_tokens * self.compress_threshold

    def compress(self, messages: List[Message]) -> List[Message]:
        """压缩历史：早期对话做摘要，保留最近 N 轮。"""
        if len(messages) <= self.keep_recent_rounds * 2:
            return messages  # 不够长，不压缩

        # 切分：早期 + 最近
        # 用 round 概念：每对 (user, assistant) 算 1 轮
        split_idx = self._find_split_index(messages, self.keep_recent_rounds)
        if split_idx <= 0:
            return messages

        early = messages[:split_idx]
        recent = messages[split_idx:]

        # 调用 Provider 做摘要
        try:
            summary_text = self.provider.summarize(early)
            summary_msg = Message.summary(summary_text)
            return [summary_msg] + recent
        except Exception as e:
            print(f"  [⚠️ 上下文] 压缩失败: {e}，保留最近 {len(recent)} 条")
            return recent

    def _find_split_index(self, messages: List[Message], keep_rounds: int) -> int:
        """从后往前找 keep_rounds 个 user 消息的位置。"""
        user_count = 0
        for i in range(len(messages) - 1, -1, -1):
            if messages[i].role == "user":
                user_count += 1
                if user_count >= keep_rounds:
                    return i
        return 0
