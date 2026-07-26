"""
subprocess 练习：用 Python 执行 git 命令

PHP 对照：
  exec("git log --oneline -5", $output);  → Python: subprocess.run(...)
  shell_exec("git log");                    → 同上
"""
import subprocess

# ── 1. 最基本用法：跑一条命令，拿到输出 ──
# PHP: $output = shell_exec("git log --oneline -5");
result = subprocess.run(
    ["git", "log", "--oneline", "-5"],  # 命令拆成列表（比拼字符串安全）
    capture_output=True,                 # 捕获输出（不然打印到终端就没了）
    text=True,                           # 输出当字符串处理（不然是 bytes）
)

print("=== git log --oneline -5 ===")
print(result.stdout)  # 标准输出（就是命令结果）
print(f"返回码: {result.returncode}")  # 0 = 成功，非0 = 失败

if result.returncode != 0:
    print(f"错误: {result.stderr}")  # 标准错误（命令报错信息）

# ── 2. 拿到 git diff ──
result2 = subprocess.run(
    ["git", "diff", "HEAD~1"],
    capture_output=True,
    text=True,
)

print("=== git diff HEAD~1 ===")
# diff 可能很长，只打印前500字符
print(result2.stdout[:500] if result2.stdout else "(无变更)")
print(f"\n返回码: {result2.returncode}")

# ── 3. 读文件内容（用 git show）──
result3 = subprocess.run(
    ["git", "show", "HEAD:list.py"],
    capture_output=True,
    text=True,
)

print("=== git show HEAD:list.py ===")
print(result3.stdout[:300] if result3.stdout else "(文件不存在或无内容)")
print(f"\n返回码: {result3.returncode}")
