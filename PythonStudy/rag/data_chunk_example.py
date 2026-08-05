
import os

def chunk_text(textContent: str, chunk_size: int = 1000, overlap: int = 50):
    """按固定字符数分片，支持重叠"""
    if overlap >= chunk_size:
        raise ValueError(f"overlap({overlap}) 必须小于 chunk_size({chunk_size})")
    chunks = []
    start = 0
    while start < len(textContent):
        end = start + chunk_size
        chunks.append(textContent[start:end])
        start += chunk_size - overlap  # 下一段起点 = 当前位置 + 步长
    return chunks

# 从文件中读取
text = ""
file = os.path.join(os.path.dirname(__file__), "document", "document.md")
if os.path.exists(file):
    with open(file, encoding="utf-8") as f:
        text = f.read()
    print(f"文件长度: {len(text)} 字")
else:
    print(f"文件 {file} 不存在")
    exit()

if not text:
    print("未读取到文本")
    exit()

# ============================================================
# 实验1：参数对比
# ============================================================
print("\n" + "=" * 60)
print("实验1：不同参数的片段数和平均长度")
print("=" * 60)
print(f"  {'size':>6} {'overlap':>8} {'片段数':>6} {'平均长度':>8}")
print(f"  {'-'*6} {'-'*8} {'-'*6} {'-'*8}")

for size in [200, 500, 1000]:
    for overlap_val in [0, 50, 100]:
        if overlap_val >= size:
            continue
        chunks = chunk_text(text, chunk_size=size, overlap=overlap_val)
        avg_len = sum(len(c) for c in chunks) / len(chunks) if chunks else 0
        print(f"  {size:>6} {overlap_val:>8} {len(chunks):>6} {avg_len:>8.0f}")

# ============================================================
# 实验2：被切断的句子统计
# ============================================================
sentence_endings = set("。！？\n")

print("\n" + "=" * 60)
print("实验2：被切断的句子数（片段末尾不在句号/换行处 = 被切断）")
print("=" * 60)
print(f"  {'size':>6} {'overlap':>8} {'片段数':>6} {'被切断':>8} {'切断率':>8}")
print(f"  {'-'*6} {'-'*8} {'-'*6} {'-'*8} {'-'*8}")

for size in [200, 500, 1000]:
    for overlap_val in [0, 50, 100]:
        if overlap_val >= size:
            continue
        chunks_test = chunk_text(text, chunk_size=size, overlap=overlap_val)
        cut_count = sum(
            1 for c in chunks_test[:-1]
            if c and c[-1] not in sentence_endings
        )
        total = len(chunks_test) - 1
        cut_rate = cut_count / total * 100 if total > 0 else 0
        print(f"  {size:>6} {overlap_val:>8} {len(chunks_test):>6} {cut_count:>8} {cut_rate:>7.1f}%")

print(f"\n结论：")
print(f"  - 所有参数都有句子被切断 → 固定长度分片是盲切")
print(f"  - overlap 增大能略微降低切断率，但不能根治")
print(f"  - 根本方案：按标题/段落切（Day32）")
