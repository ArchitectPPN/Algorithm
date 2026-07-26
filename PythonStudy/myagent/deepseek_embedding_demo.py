"""
DeepSeek embedding API 最简调用示例
- 把一段文本转成向量
- 打印向量维度和前几个分量
- 顺手算语义相似度（embedding 的真正价值）
- 为后续 RAG 做准备

Key 读取方式：从项目根目录的 .env 文件读（优先），其次读环境变量。
依赖：requests

⚠️ 没设 key 时会自动用"假向量"演示模式跑通逻辑，让你先看到效果；
   设了 key 才是真正调 DeepSeek 接口。

用法：
1. 在项目根目录建 .env 文件（不进 git，.gitignore 已挡住），内容：
   DEEPSEEK_API_KEY=sk-你的key
2. python3 deepseek_embedding_demo.py
"""
import os
import math
import requests

def load_env(env_path=".env"):
    """轻量 .env 读取器（不依赖 python-dotenv）：把 KEY=VALUE 加载进环境变量。
    格式：DEEPSEEK_API_KEY=sk-xxx  （value 不加引号也能读，加了也兼容）"""
    if not os.path.exists(env_path):
        return
    with open(env_path, encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            k, v = line.split("=", 1)
            v = v.strip().strip('"').strip("'")  # 兼容带引号写法
            os.environ["DEEPSEEK_API_KEY"] = line.split("=", 1)[1].strip().strip('"').strip("'")  # 强制覆盖，不用 setdefault

load_env()  # 加载 .env

API_KEY = os.environ.get("DEEPSEEK_API_KEY", "")  # ⚠️ 永远从环境变量/.env 读，绝不硬编码
URL = "https://api.deepseek.com/embeddings"   # DeepSeek 兼容 OpenAI 格式
HEADERS = {"Authorization": f"Bearer {API_KEY}", "Content-Type": "application/json"}
USING_REAL_API = bool(API_KEY)

def embed(text: str):
    """调 DeepSeek embedding API，返回向量（一串数字）"""
    if not USING_REAL_API:
        # 没设 key 时的演示模式：造一个"伪 embedding"
        # 用文本的简单哈希映射成向量，只是为了让你看到流程跑通，不是真语义
        return [((hash(text) + i * 7919) % 1000) / 1000 for i in range(1024)]
    resp = requests.post(URL, headers=HEADERS, json={
        "model": "text-embedding",   # DeepSeek 的 embedding 模型名
        "input": text,
    })
    if resp.status_code == 404:
        print("\n❌ DeepSeek 当前不提供 embedding 接口（只开放了 chat：deepseek-v4-flash/pro）。")
        print("   embedding 需换用智谱 GLM / 通义 / OpenAI 等。")
        print("   第3周裸写 ReAct loop 不需要 embedding，可先跳过此脚本，等第5周做 RAG 再补。")
        raise SystemExit(0)
    resp.raise_for_status()
    return resp.json()["data"][0]["embedding"]

print(f"模式: {'真实调用 DeepSeek API' if USING_REAL_API else '演示模式（无 API Key，用伪向量）'}")
if not USING_REAL_API:
    print("⚠️  这是演示数据，相似度数值无意义。设置 DEEPSEEK_API_KEY 后才是真语义。\n")

# 1. 单条文本转向量
vec = embed("如何防止 SQL 注入：使用参数化查询，不要拼接 SQL")
print(f"向量维度: {len(vec)}")          # DeepSeek text-embedding 通常是 1024 维
print(f"前5个分量: {vec[:5]}")         # 长这样：[0.012, -0.34, ...]

# 2. 顺手算语义相似度（embedding 的真正价值）—— 理解 RAG 为什么能"按意思找"
def cosine(a, b):
    """余弦相似度：两个向量越接近，值越接近 1"""
    dot = sum(x * y for x, y in zip(a, b))
    na = math.sqrt(sum(x * x for x in a))
    nb = math.sqrt(sum(y * y for y in b))
    return dot / (na * nb)

texts = [
    "如何防止 SQL 注入：使用参数化查询",          # 跟原文意思一样
    "数据库查询要避免直接拼接用户输入",            # 换个说法，意思相近
    "今天天气真好，适合出去玩",                    # 完全无关
]
vecs = [embed(t) for t in texts]

print("\n--- 语义相似度对比 ---")
print(f"原文 vs 同义改写:  {cosine(vec, vecs[1]):.4f}  (应接近 1)")
print(f"原文 vs 无关话题:  {cosine(vec, vecs[2]):.4f}  (应明显更低)")
# 你会看到：意思相近的两句话向量距离近（分数高），无关的距离远（分数低）
# 这就是 RAG 能"按意思检索"而非"按关键词检索"的底层原理
