"""温度稳定性采样 v2：连跑 N 次，统计回答文本的唯一版本数

判据升级（对比 v1）：
- v1 数"拒答语出现次数"——低频事件，小样本抓不到（10 次全 0 也不奇怪）
- v2 数"唯一回答文本数"——温度的高频直接效应（措辞浮动），每次采样都有信息
  → 判据每次都产生信息，20 次就够，不需要 v1 设想的 100 次大样本

预期：
  t=1  → 唯一版本数 > 1（措辞浮动）
  t=0  → 唯一版本数 = 1（一字不差）

用法：python run10.py [次数]
"""
import hashlib
import os
import subprocess
import sys

N = int(sys.argv[1]) if len(sys.argv) > 1 else 20

# 双端钉死 UTF-8（Windows 管道默认 GBK，父子进程编码不一致就是乱码根因）
env = {**os.environ, "PYTHONUTF8": "1"}


def extract_answer(stdout: str) -> str:
    """只取"回答:"到"来源"之间的文本做判重。

    不能拿整段 stdout 判重——"来源（3 条，92214ms，...）"里的耗时数字每次
    都不同，整段判重会永远显示"全不重复"，那是无关变量在污染观察结果。
    控制变量：只比真正的观察对象（回答文本）。
    """
    try:
        return stdout.split("回答:")[1].split("\n来源")[0].strip()
    except IndexError:
        return "(输出格式异常)"


versions: dict[str, int] = {}  # 文本hash → 版本号
bad = 0
for i in range(1, N + 1):
    r = subprocess.run(
        ["python", "rag/rag_chain.py", "--query", "如何防止 SQL 注入"],
        capture_output=True, text=True, encoding="utf-8", env=env,
    )
    answer = extract_answer(r.stdout or "")
    hit = "知识库中没有相关信息" in answer and "参数化" in answer
    bad += hit

    h = hashlib.md5(answer.encode("utf-8")).hexdigest()[:8]
    if h in versions:
        tag = f"同版本 v{versions[h]}"
    else:
        versions[h] = len(versions) + 1
        tag = f"新版本 v{len(versions)}"
    print(f"第{i:2d} 次: {tag}（{len(answer)} 字）{' <- 拒答语异常' if hit else ''}")

print(f"\n采样 {N} 次：唯一回答版本数 {len(versions)}，拒答语异常 {bad} 次")
print("解读：唯一版本数=1 → 输出完全稳定；>1 → 措辞在浮动（温度效应现形）")
