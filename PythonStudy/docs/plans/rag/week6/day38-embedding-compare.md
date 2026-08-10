# Day 38：Embedding 模型对比实验

> 目标：用真实数据对比本地模型（nomic-embed-text / bge-small-zh）和云 API（智谱/通义）的检索效果，用数据决定选型。

---

## 学习路线（约 60-90 分钟）

```
选型思路（10min）→ 接入 bge-small-zh（20min）→ 接入云 API（20min）→ 对比实验（30min）
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
| bge-small-zh | 本地（Ollama） | 512 | 强（中文专用） | 免费 |
| bge-large-zh | 本地（Ollama） | 1024 | 更强 | 免费，吃内存 |
| 智谱 embedding-3 | 云 API | 2048 | 强 | 按量收费 |
| 通义 text-embedding-v4 | 云 API | 1024 | 强 | 按量收费 |

---

## 第二步：接入 bge-small-zh（20min）

```bash
# 拉取模型（本地跑，免费）
ollama pull bge-small-zh
```

```python
def get_embedding_bge(text: str) -> list[float]:
    """调用 ollama 获取 bge-small-zh 的 embedding"""
    import requests
    resp = requests.post(
        "http://localhost:11434/api/embeddings",
        json={"model": "bge-small-zh", "prompt": text},
        timeout=30
    )
    resp.raise_for_status()
    return resp.json()["embedding"]
```

---

## 第三步：接入云 API（20min）

以智谱为例（需要 API key）：

```python
# pip install zhipuai
from zhipuai import ZhipuAI

client = ZhipuAI(api_key="你的 key")

def get_embedding_zhipu(text: str) -> list[float]:
    """调用智谱 embedding-3 获取向量"""
    resp = client.embeddings.create(
        model="embedding-3",   # 智谱 embedding 模型
        input=text,
    )
    return resp.data[0].embedding
```

> 通义类似：`pip install dashscope`，用 `dashscope.TextEmbedding`。

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

1. 拉取 bge-small-zh，写出三种模型的 embedding 函数
2. 用同一批测试查询评估，记录准确率
3. 对比维度、速度（响应时间）、成本
4. 输出结论：本项目的知识库该用哪个模型？为什么？

---

## 检验标准

1. **一句话**："Embedding 选型要看中文效果、本地/云、维度、成本，用已知答案的查询实测准确率，数据说话"
2. **追问**：能混用两个模型的向量吗？（不能，不同模型的向量空间不同）
3. **追问**：本地模型和云 API 怎么选？（数据敏感/离线 → 本地；追求极致效果且有钱 → 云）

---

## 产出文件

`rag/embedding_compare.py` — 三种 Embedding 模型对比实验
