"""
Day32：文档加载 + 分片脚本

功能：
1. 加载 .md 文件（单文件/目录）
2. 分片 + Token 估算
3. 统计信息输出
"""

import os


# ══════════════════════════════════════════════════════════
# 文档加载
# ══════════════════════════════════════════════════════════

def load_markdown(file_path: str) -> str:
    """读取 .md 文件，返回纯文本"""
    if not os.path.exists(file_path):
        raise FileNotFoundError(f"文件不存在: {file_path}")
    with open(file_path, encoding="utf-8") as f:
        return f.read()


def load_all_docs(directory: str) -> list[dict]:
    """加载目录下所有 .md 文件，返回 [{file, content}, ...]"""
    docs = []
    for filename in sorted(os.listdir(directory)):
        if filename.endswith(".md"):
            path = os.path.join(directory, filename)
            docs.append({
                "file": filename,
                "content": load_markdown(path)
            })
    return docs


# ══════════════════════════════════════════════════════════
# 分片 + Token 估算
# ══════════════════════════════════════════════════════════

def chunk_document(doc: dict, chunk_size: int = 500, chunk_overlap: int = 50) -> list[dict]:
    """对单个文档分片，返回 [{file, chunk_index, content, token_estimate}, ...]"""
    if chunk_overlap >= chunk_size:
        raise ValueError(f"chunk_overlap({chunk_overlap}) 必须小于 chunk_size({chunk_size})")

    chunks = []
    text = doc["content"]
    start = 0
    idx = 0
    while start < len(text):
        end = start + chunk_size
        chunk_text = text[start:end]
        chunks.append({
            "file": doc["file"],
            "chunk_index": idx,
            "content": chunk_text,
            "token_estimate": int(len(chunk_text) / 1.5),  # 中文约 1 字符 ≈ 1.5 tokens
        })
        start += chunk_size - chunk_overlap
        idx += 1
    return chunks


def chunk_all_docs(docs: list[dict], chunk_size: int = 500, chunk_overlap: int = 50) -> list[dict]:
    """对所有文档分片"""
    all_chunks = []
    for doc in docs:
        all_chunks.extend(chunk_document(doc, chunk_size, chunk_overlap))
    return all_chunks


# ══════════════════════════════════════════════════════════
# 统计信息
# ══════════════════════════════════════════════════════════

def print_stats(chunks: list[dict]):
    """打印分片统计"""
    if not chunks:
        print("  无分片数据")
        return

    total_tokens = sum(c["token_estimate"] for c in chunks)
    avg_len = sum(len(c["content"]) for c in chunks) / len(chunks)
    files = len(set(c["file"] for c in chunks))

    print(f"  总片段数: {len(chunks)}")
    print(f"  平均长度: {avg_len:.0f} 字")
    print(f"  预估 Token: {total_tokens}")
    print(f"  涉及文件: {files} 个")


# ══════════════════════════════════════════════════════════
# 主流程：加载 → 分片 → 统计 → 预览
# ══════════════════════════════════════════════════════════

if __name__ == "__main__":
    knowledge_dir = os.path.join(os.path.dirname(__file__), "..", "data", "knowledge")

    if not os.path.exists(knowledge_dir):
        print(f"知识库目录不存在: {knowledge_dir}")
        print("请先创建 data/knowledge/ 并放入 .md 文档")
    else:
        # 加载
        docs = load_all_docs(knowledge_dir)
        print(f"加载了 {len(docs)} 个文档:")
        for doc in docs:
            print(f"  - {doc['file']} ({len(doc['content'])} 字)")

        # 分片
        chunks = chunk_all_docs(docs, chunk_size=500, chunk_overlap=50)

        # 统计
        print(f"\n分片统计 (chunk_size=500, overlap=50):")
        print_stats(chunks)

        # 预览前 3 个片段
        print(f"\n前 3 个片段预览:")
        for chunk in chunks[:3]:
            print(f"\n  [{chunk['file']}] 片段 {chunk['chunk_index']} (约 {chunk['token_estimate']} tokens):")
            preview = chunk["content"][:100].replace("\n", " ")
            print(f"  {preview}...")
