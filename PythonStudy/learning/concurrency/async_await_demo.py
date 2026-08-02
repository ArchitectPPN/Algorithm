"""
async/await Demo —— 对比同步 vs 异步

场景：调用 3 次 LLM API（模拟），看耗时差异
"""
import asyncio
import time


# ══════════════════════════════════════════════════════════
# 模拟 API 调用（每次 1 秒）
# ══════════════════════════════════════════════════════════

def call_api_sync(name: str) -> str:
    """同步调用：阻塞等待"""
    print(f"  开始 {name}...")
    time.sleep(1)          # 模拟网络 IO，CPU 空等
    print(f"  完成 {name}")
    return f"{name} 结果"


async def call_api_async(name: str) -> str:
    """异步调用：等待时不阻塞"""
    print(f"  开始 {name}...")
    await asyncio.sleep(1) # 模拟网络 IO，让出控制权
    print(f"  完成 {name}")
    return f"{name} 结果"


# ══════════════════════════════════════════════════════════
# 1. 同步方式 —— 一个接一个
# ══════════════════════════════════════════════════════════

def demo_sync():
    print("\n【同步方式】")
    start = time.time()

    r1 = call_api_sync("分析文件A")
    r2 = call_api_sync("分析文件B")
    r3 = call_api_sync("分析文件C")

    elapsed = time.time() - start
    print(f"结果: {r1}, {r2}, {r3}")
    print(f"总耗时: {elapsed:.1f} 秒  ← 3 个任务串行，3 秒")


# ══════════════════════════════════════════════════════════
# 2. 异步方式 —— 同时发出，谁先回来收谁
# ══════════════════════════════════════════════════════════

async def demo_async():
    print("\n【异步方式】")
    start = time.time()

    r1, r2, r3 = await asyncio.gather(
        call_api_async("分析文件A"),
        call_api_async("分析文件B"),
        call_api_async("分析文件C"),
    )

    elapsed = time.time() - start
    print(f"结果: {r1}, {r2}, {r3}")
    print(f"总耗时: {elapsed:.1f} 秒  ← 3 个任务并发，1 秒")


# ══════════════════════════════════════════════════════════
# 3. 混合方式 —— 逐个 await（异步里的"伪同步"）
# ══════════════════════════════════════════════════════════

async def demo_async_sequential():
    """虽然用了 async 函数，但逐个 await，还是串行"""
    print("\n【异步但逐个 await（反面教材）】")
    start = time.time()

    r1 = await call_api_async("分析文件A")  # 等 A 完成
    r2 = await call_api_async("分析文件B")  # 等 B 完成
    r3 = await call_api_async("分析文件C")  # 等 C 完成

    elapsed = time.time() - start
    print(f"总耗时: {elapsed:.1f} 秒  ← 还是 3 秒！逐个 await = 串行")


# ══════════════════════════════════════════════════════════
# 运行
# ══════════════════════════════════════════════════════════

if __name__ == "__main__":
    demo_sync()
    asyncio.run(demo_async())
    asyncio.run(demo_async_sequential())

    print("\n" + "=" * 50)
    print("关键理解")
    print("=" * 50)
    print("""
    async 只是声明"这个函数可以异步"。
    真正的并发靠 asyncio.gather() —— 同时启动多个任务。

    常见误区：给函数加了 async，但逐个 await = 还是串行。
    正确姿势：asyncio.gather() 让它们同时跑。
    """)
