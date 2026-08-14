# Day 38：Embedding 模型对比实验

> 目标：用真实数据对比本地模型（nomic-embed-text / bge-m3）和云 API（智谱）的检索效果，用数据决定选型。
>
> ⚠️ 注意：Ollama 官方库**没有** `bge-small-zh`（直接 `ollama pull bge-small-zh` 会报 `file does not exist`）。中文 BGE 走 Ollama 用 `bge-m3`（多语言，1024 维，中文效果强）；若必须用 `bge-small-zh`（512 维）则改走 HuggingFace `sentence-transformers` 加载，不走 Ollama。

---

## 学习路线（约 60-90 分钟）

```
选型思路（10min）→ 接入 bge-m3（20min）→ 接入云 API（20min）→ 对比实验（30min）
```

---

## 第一步：选型思路（10min）

Embedding 模型是 RAG 的"眼睛"，决定检索质量上限。选型要考虑：

| 维度 | 说明 |
|------|------|
| **中文效果** | 通用模型中文弱，专用中文模型（bge）更强 |
| **本地 vs 云** | 本地零成本零延迟；云 API 质量高但花钱、依赖网络 |
| **维度大小** | 维度越高信息越丰富，但存储/计算成本越高 |
| **上下文长度** | 旧模型只支持 512 tokens，长文档要预分片 |
| **是否开源** | 开源可商用、可私有化部署 |

### 候选模型

| 模型 | 类型 | 维度 | 中文 | 成本 |
|------|------|------|------|------|
| nomic-embed-text | 本地（Ollama） | 768 | 一般 | 免费 |
| bge-m3 | 本地（Ollama） | 1024 | 强（多语言） | 免费，吃内存 |
| bge-small-zh | 本地（HuggingFace） | 512 | 强（中文专用） | 免费，轻量 |
| 智谱 embedding-3 | 云 API | 2048 | 强 | 按量收费 |

---

## 第二步：接入 bge-m3（20min）

```bash
# 拉取模型（本地跑，免费）
ollama pull bge-m3
```

```python
def get_embedding_bge(text: str) -> list[float]:
    """调用 ollama 获取 bge-m3 的 embedding"""
    import requests
    resp = requests.post(
        "http://localhost:11434/api/embeddings",
        json={"model": "bge-m3", "prompt": text},
        timeout=30
    )
    resp.raise_for_status()
    return resp.json()["embedding"]
```

---

## 第三步：接入云 API（20min）

以智谱为例（需要 API key，去 [open.bigmodel.cn](https://open.bigmodel.cn) 申请）。

```python
# pip install zhipuai
import os
from zhipuai import ZhipuAI

# ⚠️ 不要把 key 写进代码！用环境变量读取，避免提交到 git 泄露
# 设置方法（PowerShell）：$env:ZHIPU_API_KEY="你的key"
# 设置方法（bash）：     export ZHIPU_API_KEY="你的key"
client = ZhipuAI(api_key=os.environ["ZHIPU_API_KEY"])

def get_embedding_zhipu(text: str) -> list[float]:
    """调用智谱 embedding-3 获取单条文本的向量

    embedding-3 默认 2048 维，可通过 dimensions 参数降维（如 1024）。
    降维能省存储/计算，但会损失一点信息，按需取舍。
    """
    resp = client.embeddings.create(
        model="embedding-3",      # 智谱 embedding 模型
        input=text,               # 单条文本
        dimensions=1024,          # 可选，不传则默认 2048
    )
    return resp.data[0].embedding  # 注意返回结构：resp.data[0].embedding
```

> **批量调用**：`input` 也可以传 `list[str]`（如 `input=["文本1", "文本2"]`），
> 返回 `resp.data` 是列表，`resp.data[i].embedding` 对应第 i 条。
> 建知识库时用批量调用，比逐条快得多。
>
> **返回结构差异**：智谱是 `resp.data[0].embedding`；
> 若以后换通义（`dashscope.TextEmbedding`），则是 `resp.output["embeddings"][0]["embedding"]`，
> 且通义要区分 `text_type="document" / "query"`，两边不一样，换模型时注意改。

---

## 第四步：对比实验（30min）

核心思路：**同一批查询，用不同模型做检索，比较"谁召回的相关片段更准"**。

```python
# 1. 模型 → 向量化函数 → 集合 的映射（不同模型的向量空间不同，必须各存各的）
EMBED_MODELS = {
    "nomic": {
        "embed_fn": get_embedding,      # 第一步定义的 nomic 函数
        "collection_name": "kb_nomic",
    },
    "bge": {
        "embed_fn": get_embedding_bge,  # 第二步定义的 bge 函数
        "collection_name": "kb_bge",
    },
    # "zhipu": {
    #     "embed_fn": get_embedding_zhipu,
    #     "collection_name": "kb_zhipu",
    # },
}

# 每个模型一个集合，用对应模型向量化知识库
def build_collection(model_key: str):
    """用指定模型向量化知识库并建集合（每个模型只建一次）"""
    spec = EMBED_MODELS[model_key]
    collection = client.get_or_create_collection(name=spec["collection_name"])

    docs = load_all_docs(knowledge_dir)
    chunks = chunk_all_docs(docs, chunk_size=500, chunk_overlap=50)

    ids, embeddings, documents, metadatas = [], [], [], []
    for i, chunk in enumerate(chunks):
        embeddings.append(spec["embed_fn"](chunk["content"]))
        ids.append(f"chunk_{i}")
        documents.append(chunk["content"])
        metadatas.append({"file": chunk["file"], "chunk_index": chunk["chunk_index"]})

    collection.add(ids=ids, embeddings=embeddings,
                   documents=documents, metadatas=metadatas)
    return collection

def evaluate_model(model_key: str, collection, queries: list[dict]):
    """用一组"已知答案"的查询评估某个 embedding 模型

    Args:
        model_key: EMBED_MODELS 的 key
        collection: 已用该模型建好的集合
        queries: [{"query": "...", "expected_file": "应该命中的文件"}, ...]
    """
    embed_fn = EMBED_MODELS[model_key]["embed_fn"]
    correct = 0
    for item in queries:
        vec = embed_fn(item["query"])          # 用该模型向量化查询
        results = collection.query(
            query_embeddings=[vec],
            n_results=3,
            include=["metadatas"]
        )
        hit_files = set(m["file"] for m in results["metadatas"][0])
        if item["expected_file"] in hit_files:
            correct += 1

    accuracy = correct / len(queries)
    print(f"{model_key}: 准确率 {accuracy:.0%} ({correct}/{len(queries)})")
    return accuracy

# 测试查询（已知答案）
test_queries = [
    {"query": "Python 变量怎么命名", "expected_file": "python-coding-style.md"},
    {"query": "如何防止 SQL 注入", "expected_file": "sql-best-practices.md"},
    {"query": "API 接口怎么设计", "expected_file": "api-design.md"},
    {"query": "Git 提交信息格式", "expected_file": "git-workflow.md"},
    {"query": "代码出错怎么处理", "expected_file": "error-handling.md"},
]

# 分别建集合并评估（⚠️ 每个集合的向量必须由对应模型生成，不能混用）
for key in ["nomic", "bge"]:
    col = build_collection(key)
    evaluate_model(key, col, test_queries)
```

### 注意事项

1. **知识库也要用同模型向量化**：不同模型的向量不在同一空间，不能混用
2. **每个模型建一个集合**：`knowledge_base_nomic` / `knowledge_base_bge`
3. **结果判断**：用 `expected_file` 是否出现在 Top-3 里作为"命中"
4. **人工复核**：Top-1 文件对了不一定内容就准，抽样看片段内容

---

## 实验任务

1. 拉取 bge-m3，写出三种模型的 embedding 函数
2. 用同一批测试查询评估，记录准确率
3. 对比维度、速度（响应时间）、成本
4. 输出结论：本项目的知识库该用哪个模型？为什么？

---

## 检验标准

1. **一句话**："Embedding 选型要看中文效果、本地/云、维度、成本，用已知答案的查询实测准确率，数据说话"
2. **追问**：能混用两个模型的向量吗？（不能，不同模型的向量空间不同）
3. **追问**：本地模型和云 API 怎么选？（数据敏感/离线 → 本地；追求极致效果且有钱 → 云）

---

## 实验结果（2026-08-13 实测）

运行 `python rag/embedding_compare.py --rebuild`。

**第一轮（知识库 5 文档 11 分片，5 查询）**：三模型全 100%，区分度只在速度。

**第二轮（扩充知识库至 11 文档 23 分片，15 查询，含 4 条易混淆题）**：

| 模型 | 来源 | 准确率 | 建库耗时 | 平均查询 | 维度 |
|------|------|--------|---------|---------|------|
| nomic-embed-text | 本地 Ollama | 67% (10/15) | 59.7s | 2637ms | 768 |
| bge-m3 | 本地 Ollama | 87% (13/15) | 68.8s | 2404ms | 1024 |
| embedding-3（降维 1024） | 云 API（智谱） | 87% (13/15) | 1.0s | 315ms | 1024 |
| embedding-3（默认 2048） | 云 API（智谱） | 87% (13/15) | 1.1s | 337ms | 2048 |

> **关键发现**：
> 1. **中文能力拉开差距**：bge/zhipu 比 nomic 高 20 个百分点（nomic 英文为主，中文弱）
> 2. **维度 1024 vs 2048 无差异**：同一模型降维到 1024 和默认 2048 准确率都是 87%——
>    小知识库（23 分片）1024 维已足够表达，2048 维白存一倍向量。高维度只在
>    大数据量/语义更细的场景才有价值
> 3. **四模型全错的一题**：「登录密码怎么安全存储」→ 期望 `sql-best-practices.md`（数据安全），
>    实际都偏到 `frontend-security.md` / `http-api-auth.md`——"登录/密码/安全"是多文档共有的主题词，
>    这是 embedding 检索的真实难点（词重叠但文档语义不同）
> 4. **速度**：zhipu 建库 1.0s vs bge 68.8s（快 68 倍）；查询 315ms vs 2.4s（快 7.5 倍）
> 5. **选型**：bge 与 zhipu 打平，本地免费 → 选 bge-m3

### 结论

本项目知识库小、中文为主、离线可跑 → **推荐 bge-m3**（本地免费、中文强、无需联网）；
数据敏感/完全离线选本地，追求极致效果且不差钱再上云 API。
维度够用即可：小知识库 768~1024 维足够，无需为高维度付费。
真实环境里"主题词重叠"的查询（如密码安全 vs 前端安全）命中不稳定，说明 embedding 粗筛后应配合 LLM 精筛（Day34 的 rerank）提升准确率。

### 踩坑记录

1. **`ollama pull bge-small-zh` 不存在** → Ollama 官方库无此模型，中文 BGE 用 `bge-m3`（详见文档开头 ⚠️）
2. **Windows 控制台中文乱码** → Python 默认 GBK，脚本开头 `sys.stdout.reconfigure(encoding="utf-8")` 强制 UTF-8
3. **首次调用 embedding 超时（30s 不够）** → Ollama 首次要加载模型进内存，timeout 提到 120s，并在建库前先"预热"一次
4. **智谱 API key 泄露风险** → 不能硬编码/提交 git。三种填法：脚本常量 / 环境变量 / `.env`（推荐，已加入 `.gitignore`）
5. **`zhipuai` 包未安装** → `python -m pip install zhipuai`
6. **云 API 批量调用更快** → 攒 16 条一批再调，比逐条快得多（建库 0.5s vs 逐条会慢很多）

---

## 产出文件

`rag/embedding_compare.py` — 三种 Embedding 模型对比实验
