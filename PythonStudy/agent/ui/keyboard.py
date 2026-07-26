"""跨平台单键读取。

Windows 用 msvcrt，Unix 用 termios + tty。
支持方向键，返回 'up'/'down'/'left'/'right'。
"""

import sys


def read_key() -> str:
    """读一个键。

    返回：
      'up'/'down'/'left'/'right' 方向键
      '\\r' 或 '\\n' 回车
      '\\x03' Ctrl+C
      '\\x1b' ESC
      其他单字符（如 'q'）
    """
    if sys.platform == "win32":
        return _read_key_windows()
    return _read_key_unix()


def _read_key_windows() -> str:
    import msvcrt
    ch = msvcrt.getch()
    if ch in (b"\x00", b"\xe0"):
        # 特殊键前缀，再读一个字节
        ch2 = msvcrt.getch()
        special = {
            b"H": "up",
            b"P": "down",
            b"K": "left",
            b"M": "right",
        }
        return special.get(ch2, "")
    try:
        return ch.decode("utf-8", errors="ignore")
    except Exception:
        return ""


def _read_key_unix() -> str:
    import termios
    import tty
    fd = sys.stdin.fileno()
    old = termios.tcgetattr(fd)
    try:
        tty.setraw(fd)
        ch = sys.stdin.read(1)
        if ch == "\x1b":
            # 可能是方向键序列 \x1b[A/B/C/D
            ch2 = sys.stdin.read(1)
            if ch2 == "[":
                ch3 = sys.stdin.read(1)
                arrows = {"A": "up", "B": "down", "C": "right", "D": "left"}
                return arrows.get(ch3, "")
            return "\x1b"
        return ch
    finally:
        termios.tcsetattr(fd, termios.TCSADRAIN, old)
