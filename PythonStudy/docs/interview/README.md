# 面试题整理

> 持续维护的开发 Agent 相关面试题记录。每道题包含：考察点、答题思路、加分项、追问、代码示例。

## 维护方式

- 新增题目：在「题目索引」表加一行，并在文件末尾追加「题目详情」一节
- 题号格式：`Q编号`（如 Q1、Q2），按加入顺序递增
- 难度分级：⭐ 基础 / ⭐⭐ 中等 / ⭐⭐⭐ 进阶
- 分类标签：工程认知 / Prompt 工程 / 工具调用 / 上下文管理 / 多 Agent / 评测 / 安全 / 其他

## 题目索引

| 题号 | 题目 | 分类 | 难度 | 状态 | 文件 |
|------|------|------|------|------|------|
| Q1 | 如何保证大模型输出可以解析的 JSON | 工程认知 | ⭐⭐ | ✅ 已整理 | [q1-json-parsing.md](q1-json-parsing.md) |
| Q2 | JSON 解析失败时的重试上限怎么定 | 工程认知 | ⭐⭐ | ✅ 已整理 | [q2-retry-strategy.md](q2-retry-strategy.md) |
| Q3 | 为什么上下文不是越长越好 | 上下文管理 | ⭐⭐ | ✅ 已整理 | [q3-context-length.md](q3-context-length.md) |
| Q4 | 何时应该压缩？Claude Code 和 Cursor 怎么限制上下文窗口 | 上下文管理 | ⭐⭐⭐ | ✅ 已整理 | [q4-context-compression.md](q4-context-compression.md) |
| Q5 | LangChain 帮你做了什么 vs LLM 本身做的 | 工程认知 | ⭐⭐ | ✅ 已整理 | [q5-langchain-vs-naked.md](q5-langchain-vs-naked.md) |

## 专题文档

部分主题集中的题目会拆到独立文档，避免单文件过长：

- [模型窗口兼容性专题](model-window-compat.md)（含 Q5）

---

## 题目详情
