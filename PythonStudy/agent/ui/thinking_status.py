"""Claude Code 风格的思考状态显示。

显示：✽ Wibbling… (5s · 45 tokens)
- spinner 旋转
- 词每隔几秒随机变化
- 实时计时
- 流式 token 数实时增长（粗略估算）
"""

import random
import time
from typing import Optional

from rich.console import Console
from rich.live import Live
from rich.spinner import Spinner
from rich.text import Text


def estimate_tokens(text: str) -> int:
    """粗略估算 token 数。

    中文按 1 字 ≈ 1 token，其他字符按 4 个 ≈ 1 token。
    不依赖 tokenizer，适用于 GLM/Qwen/DeepSeek 等模型的大致估算。
    """
    if not text:
        return 0
    cjk = 0
    other = 0
    for ch in text:
        if '一' <= ch <= '鿿' or '　' <= ch <= '〿' or '＀' <= ch <= '￯':
            cjk += 1
        else:
            other += 1
    return cjk + other // 4


class ThinkingStatus:
    WORDS = [
        "Wibbling", "Pondering", "Musing", "Cogitating",
        "Ruminating", "Thinking", "Considering", "Reflecting",
        "Deliberating", "Contemplating", "Noodling", "Percolating",
    ]

    def __init__(self, console: Console):
        self.console = console
        self.start_time = time.time()
        self.current_word = random.choice(self.WORDS)
        self.last_change = self.start_time
        self.tokens = 0
        self._live: Optional[Live] = None

    def start(self):
        self._live = Live(
            self,
            console=self.console,
            refresh_per_second=8,
            transient=True,
        )
        self._live.start()

    def stop(self):
        if self._live:
            self._live.stop()
            self._live = None

    def add_text(self, text: str):
        """累加文本并更新 token 计数。"""
        if text:
            self.tokens += estimate_tokens(text)

    def __rich__(self):
        now = time.time()
        elapsed = int(now - self.start_time)
        # 每 3 秒换词
        if now - self.last_change > 3:
            self.current_word = random.choice(
                [w for w in self.WORDS if w != self.current_word]
            )
            self.last_change = now
        text = Text(
            f" {self.current_word}… ({elapsed}s · ↓ {self.tokens} tokens)",
            style="magenta",
        )
        return Spinner("line", text=text)
