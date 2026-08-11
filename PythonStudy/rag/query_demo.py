"""
Day36：元数据过滤（where 条件检索）

功能：
1. 建带 category 字段的新集合（不动 Week5 的旧数据）
2. where 基础语法：精确匹配 / $ne / $in / 范围
3. 组合过滤：$and / $or / where_document
4. 检索 + 过滤结合：collection.query(where=...)
5. 验证两个坑 + 边界情况（过滤无匹配 → 降级全库检索）
6. 全库检索 vs 过滤后检索 结果对比
7. 进阶：$or 嵌套 $and（category 范围 OR file 条件）

⚠️ 依赖 ollama 运行 nomic-embed-text 模型（http://localhost:11434）
"""

# 让 3.9 支持 dict | None 这类 3.10+ 注解写法（注解惰性求值，不报 TypeError）
from __future__ import annotations

import os
import sys
import requests
import chromadb

# 添加父目录到路径，方便导入 document_loader
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from rag.document_loader import load_all_docs, chunk_all_docs


# ══════════════════════════════════════════════════════════
# 配置：文件 → 类别 映射（Day36 新增的 metadata 字段）
# ══════════════════════════════════════════════════════════

FILE_CATEGORY = {
    "python-coding-style.md": "编程规范",
    "sql-best-practices.md":  "数据库",
    "api-design.md":          "接口设计",
    "error-handling.md":      "错误处理",
    "git-workflow.md":        "协作规范",
}


# ══════════════════════════════════════════════════════════
# 工具函数
# ══════════════════════════════════════════════════════════

def get_embedding(text: str) -> list[float]:
    """调用 ollama 获取文本的 embedding 向量"""
    resp = requests.post(
        "http://localhost:11434/api/embeddings",
        json={"model": "nomic-embed-text", "prompt": text},
        timeout=30
    )
    resp.raise_for_status()
    return resp.json()["embedding"]


def print_results(title: str, results: dict, show_content: bool = False):
    """格式化打印 collection.get / collection.query 的结果

    Args:
        title: 标题
        results: Chroma 返回的结果
        show_content: 是否打印片段正文（get 返回的是平铺结构，query 返回的是嵌套结构）
    """
    print(f"\n  【{title}】")

    # collection.get 返回平铺结构：ids/documents/metadatas 都是 list
    # collection.query 返回嵌套结构：ids[0]/documents[0]/metadatas[0] 是 list
    # 这里统一判断：如果 ids[0] 是 list → query 结构；否则 → get 结构
    # ⚠️ Chroma 返回的字典里这些键始终存在，不 include 时值为 None（不是缺键），
    #    所以不能用 .get(key, default) 的默认值兜底（None 时不会走默认），要用 `or` 模式
    ids = results["ids"]
    is_query = len(ids) > 0 and isinstance(ids[0], list)
    if is_query:
        ids = ids[0]
        documents = results["documents"][0] if results.get("documents") else []
        metadatas = results["metadatas"][0] if results.get("metadatas") else []
        distances = (results.get("distances") or [[None] * len(ids)])[0]
    else:
        documents = results.get("documents") or [None] * len(ids)
        metadatas = results.get("metadatas") or [None] * len(ids)
        distances = [None] * len(ids)

    if not ids:
        print("    （空结果，无匹配）")
        return

    for i in range(len(ids)):
        meta = metadatas[i] if metadatas else {}
        dist = distances[i]
        dist_str = f" 距离={dist:.3f}" if dist is not None else ""
        cat = meta.get("category", "?") if meta else "?"
        file = meta.get("file", "?") if meta else "?"
        idx = meta.get("chunk_index", "?") if meta else "?"
        print(f"    [{ids[i]}] [{cat}] {file}#{idx}{dist_str}")
        if show_content and documents and documents[i]:
            preview = documents[i][:70].replace("\n", " ")
            print(f"      {preview}...")


# ══════════════════════════════════════════════════════════
# 第一步：建带 category 字段的新集合
# ══════════════════════════════════════════════════════════

print("=" * 60)
print("第一步：建带 category 字段的新集合")
print("=" * 60)

base_dir = os.path.dirname(__file__)
chroma_path = os.path.join(base_dir, "..", "chroma_data")
knowledge_dir = os.path.join(base_dir, "..", "data", "knowledge")

client = chromadb.PersistentClient(path=chroma_path)

# 用独立集合名，避免污染 Week5 的 knowledge_base_cosine
COLLECTION_NAME = "knowledge_base_filtered"
try:
    client.delete_collection(COLLECTION_NAME)
except Exception:
    pass

collection = client.get_or_create_collection(
    name=COLLECTION_NAME,
    metadata={"hnsw:space": "cosine"}  # RAG 场景推荐余弦（Day33 结论）
)

# 加载 + 分片
docs = load_all_docs(knowledge_dir)
chunks = chunk_all_docs(docs, chunk_size=500, chunk_overlap=50)
print(f"  加载 {len(docs)} 个文档，分片 {len(chunks)} 条")

# 向量化 + 插入（metadata 多挂一个 category 字段）
ids, embeddings, documents, metadatas = [], [], [], []
for i, chunk in enumerate(chunks):
    embedding = get_embedding(chunk["content"])
    ids.append(f"chunk_{i}")
    embeddings.append(embedding)
    documents.append(chunk["content"])
    metadatas.append({
        "file": chunk["file"],
        "chunk_index": chunk["chunk_index"],
        "category": FILE_CATEGORY.get(chunk["file"], "未分类"),  # Day36 新增
    })
    if (i + 1) % 5 == 0:
        print(f"  已向量化 {i + 1}/{len(chunks)} 条")

collection.add(ids=ids, embeddings=embeddings, documents=documents, metadatas=metadatas)
print(f"  插入完成：{collection.count()} 条")

# 看看各类别分布
all_data = collection.get(include=["metadatas"])
cat_counts = {}
for m in all_data["metadatas"]:
    cat_counts[m["category"]] = cat_counts.get(m["category"], 0) + 1
print(f"  类别分布: {cat_counts}")


# ══════════════════════════════════════════════════════════
# 第二步：where 基础语法
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第二步：where 基础语法")
print("=" * 60)

# 2.1 精确匹配（类似 WHERE category = '数据库'）
print("\n--- 2.1 精确匹配 category = '数据库' ---")
results = collection.get(
    where={"category": "数据库"},   # 简写，等价于 {"category": {"$eq": "数据库"}}
    include=["documents", "metadatas"]
)
print_results("精确匹配", results, show_content=True)

# 2.2 不等（类似 WHERE category != '数据库'）
print("\n--- 2.2 不等 category != '数据库' ---")
results = collection.get(
    where={"category": {"$ne": "数据库"}},
    include=["metadatas"]
)
print_results("不等", results)

# 2.3 范围：chunk_index >= 0 AND chunk_index <= 1
# ⚠️ 坑 1：同一字段多个运算符必须用 $and 组合，不能写 {"chunk_index": {"$gte": 0, "$lte": 1}}
# ⚠️ 注意：每个文件的 chunk_index 都从 0 重新计数，最大才到 2，所以范围取 0~1
print("\n--- 2.3 范围 0 <= chunk_index <= 1（$and 组合同字段多运算符）---")
results = collection.get(
    where={"$and": [
        {"chunk_index": {"$gte": 0}},
        {"chunk_index": {"$lte": 1}},
    ]},
    include=["metadatas"]
)
print_results("范围", results)

# 2.4 $in 列表（类似 WHERE category IN (...)）
print("\n--- 2.4 $in 列表 category IN ('数据库', '错误处理') ---")
results = collection.get(
    where={"category": {"$in": ["数据库", "错误处理"]}},
    include=["metadatas"]
)
print_results("$in 列表", results)


# ══════════════════════════════════════════════════════════
# 第三步：组合过滤
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第三步：组合过滤")
print("=" * 60)

# 3.1 不同字段 AND：也必须用 $and（Chroma 1.5.9 不支持平铺写法）
# 类似 WHERE category = '数据库' AND chunk_index < 2
# ⚠️ 不能写 {"category": "数据库", "chunk_index": {"$lt": 2}}，会报错"exactly one operator"
print("\n--- 3.1 不同字段 AND（必须 $and）category='数据库' AND chunk_index<2 ---")
results = collection.get(
    where={"$and": [
        {"category": "数据库"},
        {"chunk_index": {"$lt": 2}},
    ]},
    include=["metadatas"]
)
print_results("不同字段 AND", results)

# 3.2 $or 组合（类似 WHERE category='数据库' OR category='协作规范'）
print("\n--- 3.2 $or 组合 category='数据库' OR category='协作规范' ---")
results = collection.get(
    where={"$or": [
        {"category": "数据库"},
        {"category": "协作规范"},
    ]},
    include=["metadatas"]
)
print_results("$or 组合", results)

# 3.3 where_document：正文子串匹配（类似 WHERE content LIKE '%参数化%'）
print("\n--- 3.3 where_document 正文含 '参数化' ---")
results = collection.get(
    where_document={"$contains": "参数化"},
    include=["documents", "metadatas"]
)
print_results("正文含'参数化'", results, show_content=True)


# ══════════════════════════════════════════════════════════
# 第四步：验证两个坑
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第四步：验证两个坑")
print("=" * 60)

# 坑 1：同字段多运算符不拆 $and，直接报错
print("\n--- 坑 1：同字段多运算符（错误写法 vs 正确写法）---")
print("  错误写法：where={'chunk_index': {'$gte': 0, '$lte': 1}}")
try:
    wrong = collection.get(
        where={"chunk_index": {"$gte": 0, "$lte": 1}},
        include=["metadatas"]
    )
    print_results("错误写法结果", wrong)
except Exception as e:
    print(f"    报错（{type(e).__name__}）: {e}")

print("  正确写法：where={'$and': [{'chunk_index': {'$gte': 0}}, {'chunk_index': {'$lte': 1}}]}")
right = collection.get(
    where={"$and": [
        {"chunk_index": {"$gte": 0}},
        {"chunk_index": {"$lte": 1}},
    ]},
    include=["metadatas"]
)
print_results("正确写法结果", right)
print("  结论：where 字典只能有一个 operator，同字段多运算符必须用 $and 拆开")

# 坑 2：$contains 用在标量字符串元数据上，静默返回空
print("\n--- 坑 2：$contains 用在元数据 file 上（静默返回空）---")
print("  错误用法：where={'file': {'$contains': 'sql'}}  ← 想找 file 含 'sql' 的")
silent = collection.get(
    where={"file": {"$contains": "sql"}},
    include=["metadatas"]
)
print_results("元数据用 $contains", silent)
print("  ↑ 静默返回空！不报错但啥也搜不到（这就是坑）")

print("  正确做法 1：元数据用精确匹配/$in")
correct1 = collection.get(
    where={"file": {"$in": ["sql-best-practices.md"]}},
    include=["metadatas"]
)
print_results("元数据用 $in", correct1)

print("  正确做法 2：搜正文子串用 where_document")
correct2 = collection.get(
    where_document={"$contains": "sql"},
    include=["metadatas"]
)
print_results("正文用 where_document", correct2)
print("  结论：$contains 只对 document 正文有效，元数据只支持精确匹配")


# ══════════════════════════════════════════════════════════
# 第五步：检索 + 过滤结合
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第五步：检索 + 过滤结合")
print("=" * 60)


def search_with_filter(query: str, where: dict | None = None, top_k: int = 3) -> dict:
    """按 where 过滤后做相似检索；过滤无匹配时降级为全库检索

    Args:
        query: 用户查询
        where: 元数据过滤条件，None 表示全库
        top_k: 返回条数
    """
    query_vec = get_embedding(query)
    results = collection.query(
        query_embeddings=[query_vec],
        n_results=top_k,
        where=where,
        include=["documents", "metadatas", "distances"]
    )
    # 边界：过滤太严返回空 → 降级全库检索
    if not results["ids"][0] and where is not None:
        print(f"  [!] 过滤后无结果，降级为全库检索")
        results = collection.query(
            query_embeddings=[query_vec],
            n_results=top_k,
            include=["documents", "metadatas", "distances"]
        )
    return results


# 5.1 只在"数据库"类别里检索 SQL 注入
print("\n--- 5.1 查询'如何防止注入攻击'，只搜 category='数据库' ---")
hits = search_with_filter("如何防止注入攻击", where={"category": "数据库"}, top_k=3)
print_results("按类别过滤检索", hits, show_content=True)

# 5.2 边界：过滤条件无匹配（拼错的类别）→ 降级
print("\n--- 5.2 边界：查询'变量命名'，过滤 category='不存在的类别' → 降级 ---")
hits = search_with_filter("变量命名规范", where={"category": "不存在的类别"}, top_k=2)
print_results("边界-降级", hits, show_content=True)


# ══════════════════════════════════════════════════════════
# 第六步：全库检索 vs 过滤后检索 对比
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第六步：全库检索 vs 过滤后检索 对比")
print("=" * 60)

query = "如何防止注入攻击"
query_vec = get_embedding(query)

# 全库检索
all_hits = collection.query(
    query_embeddings=[query_vec],
    n_results=5,
    include=["documents", "metadatas", "distances"]
)
print("\n--- 全库检索（top 5）---")
print_results("全库", all_hits)

# 过滤后检索（只搜数据库类）
filtered_hits = collection.query(
    query_embeddings=[query_vec],
    n_results=5,
    where={"category": "数据库"},
    include=["documents", "metadatas", "distances"]
)
print("\n--- 过滤后检索 category='数据库'（top 5）---")
print_results("过滤后", filtered_hits)

print("\n  对比结论：")
print("  - 全库检索：可能混入'安全/接口'类的相似片段，范围大")
print("  - 过滤后检索：只在'数据库'类里找，又快又准，不会跑偏")
print("  - 真实场景：知识库越大，过滤的价值越明显（避免全库搜的噪音）")


# ══════════════════════════════════════════════════════════
# 第七步：进阶——$or 嵌套 $and
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("第七步：进阶——$or 嵌套 $and")
print("=" * 60)

# 需求：(chunk_index 在 0~2 范围) OR (file = 'git-workflow.md')
# 即：前几个片段，或者 git-workflow 的所有片段
print("\n  需求：(chunk_index 0~2) OR (file = 'git-workflow.md')")
print("  写法：$or 里面嵌套 $and（同字段范围必须 $and）")
nested = collection.get(
    where={"$or": [
        {   # 第一组：chunk_index 范围（同字段多运算符 → $and）
            "$and": [
                {"chunk_index": {"$gte": 0}},
                {"chunk_index": {"$lte": 2}},
            ]
        },
        {   # 第二组：file 条件
            "file": "git-workflow.md"
        },
    ]},
    include=["metadatas"]
)
print_results("$or 嵌套 $and", nested)
print("  说明：$or 列表里每个元素是一个子条件；第一组是 $and（范围），第二组是 file 精确匹配")
print("  布尔树：OR( AND(gte, lte), file=git ) —— 两个组之间 OR，组内 AND")


# ══════════════════════════════════════════════════════════
# 收尾总结
# ══════════════════════════════════════════════════════════

print("\n" + "=" * 60)
print("Day36 总结")
print("=" * 60)
print("""
  1. metadata 是给向量贴的"标签"，where 让检索先在标签上缩小范围再做相似匹配
  2. where 字典只能有一个 operator：任何 AND（同字段或跨字段）都要 $and，任何 OR 都要 $or
  3. $contains 只对 document 正文有效，元数据只支持精确匹配（静默返回空是坑）
  4. where + where_document 可同时用，AND 关系
  5. 过滤太严返回空 → 降级全库检索，避免给用户空结果
  6. 全库 vs 过滤：知识库越大，过滤的价值越明显（又快又准）
  7. 嵌套：$or 里可套 $and，实现"范围 OR 单条件"等复杂布尔逻辑
""")
