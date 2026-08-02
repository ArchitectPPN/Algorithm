# asyncio.gather —— 并发执行

> `gather` 就是同时点燃多个 async 任务，等全部跑完，按传入顺序返回结果。

---

## 一句话

`await asyncio.gather(A, B, C)` = A、B、C 同时跑，谁先回来谁先完，但返回结果按 A、B、C 的顺序排队。

---

## 基础用法

```python
import asyncio

async def do_math():
    await asyncio.sleep(5)
    return "Math"

async def do_english():
    await asyncio.sleep(3)
    return "English"

async def do_art():
    await asyncio.sleep(4)
    return "Art"

async def main():
    math, english, art = await asyncio.gather(
        do_math(),
        do_english(),
        do_art(),
    )
    print(f"{math} {english} {art} was done!")

asyncio.run(main())
```

输出：
```
开始做数学家庭作业
开始做English家庭作业
开始做Art家庭作业
Finished the English homework     ← 3 秒，最先完成
Finished the Art homework          ← 4 秒
Finished the Math homework         ← 5 秒，最后完成
Math English Art was done!         ← 但返回值按传入顺序！
```

总耗时 5 秒（不是 5+3+4=12 秒），返回顺序与传入顺序一致，和实际完成顺序无关。

---

## 对比三种用法

| 用法 | 效果 | 3 任务各 3 秒耗时 |
|------|------|-------------------|
| 同步调用 ×3 | 串行，一个完了下一个 | 9 秒 |
| `await` ×3（逐个） | 加了 async 也串行 | 9 秒 |
| `gather` | 真正并发 | 3 秒 |
| `create_task` + `await` | 先点火，回头取 | 3 秒 |

---

## gather 的返回顺序

不管谁先完成，返回结果永远和传入顺序一致：

```python
a, b, c = await asyncio.gather(A(), B(), C())
# a = A 的结果，b = B 的结果，c = C 的结果
# 哪怕 B 比 A 先完成，顺序也不变
```

---

## 什么时候用 gather vs create_task

| 场景 | 用法 |
|------|------|
| 多个任务同时跑，全等完 | `gather(A(), B(), C())` |
| 先点火一个，干别的事，回头再取 | `create_task(A())` → 干别的 → `await task` |
| 任务数量不固定 | `gather(*[task() for task in tasks])` |
| 某个任务失败也不要紧 | `gather(A(), B(), return_exceptions=True)` |
