"""消息队列：输入线程往里塞，主线程取。

支持查看和取消队列中的消息（queue.Queue 不支持按索引删除，所以自定义实现）。
"""

import threading
from typing import List, Optional


class MessageQueue:
    def __init__(self):
        self._items: List[Optional[str]] = []
        self._cond = threading.Condition()

    def put(self, item: Optional[str]):
        """塞入消息。None 作为退出哨兵。"""
        with self._cond:
            self._items.append(item)
            self._cond.notify()

    def get(self) -> Optional[str]:
        """阻塞等待并取出第一条消息。"""
        with self._cond:
            while not self._items:
                self._cond.wait()
            return self._items.pop(0)

    def list_items(self) -> List[Optional[str]]:
        """返回队列快照（不含哨兵）。"""
        with self._cond:
            return [it for it in self._items if it is not None]

    def cancel(self, idx: int) -> bool:
        """取消队列中指定索引的消息（0-based）。"""
        with self._cond:
            if 0 <= idx < len(self._items):
                self._items.pop(idx)
                return True
            return False

    def clear(self) -> int:
        """清空队列，返回清空的消息数。"""
        with self._cond:
            count = sum(1 for it in self._items if it is not None)
            self._items.clear()
            return count

    def size(self) -> int:
        """返回队列中消息数（不含哨兵）。"""
        with self._cond:
            return sum(1 for it in self._items if it is not None)
