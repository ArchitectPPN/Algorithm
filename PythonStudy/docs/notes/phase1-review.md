# 第一阶段复盘（第1-4周）

> 基准日期：2026-07-20，复盘日期：2026-08-02
> 从 PHP 后端到能裸写 ReAct Agent + 异步 API 服务

---

## 一句话总结

4 周前只会用 Claude Code，4 周后能自己写出完整的 ReAct 循环 + FastAPI 异步服务。从"用 Agent 的人"变成了"写 Agent 的人"。

---

## 各周回顾

### 第1周：Python 语法速通

**学会了：** 变量、控制流、函数、OOP、异常处理、文件读写

**产出：** `learning/python-basics/` 下 18 个练习文件

**关键认知：** Python 和 PHP 语法相似但不相同，列表推导式、f-string、with 语句是 PHP 没有的

---

### 第2周：LLM API 调用

**学会了：** Token 概念、上下文窗口、Prompt Engineering、Function Calling、流式输出

**产出：** 周报分析工具、`.env` 配置管理、API 调通

**关键认知：** 
- Token ≠ 字符数，中文 1 字符 ≈ 1.5-2 tokens
- 128K 上下文窗口指的是 Token 数，不是字符数
- Function Calling 的本质：模型输出结构化指令（函数名 + 参数），你负责执行

---

### 第3周：Agent 核心——裸写 ReAct loop

> ⚠️ 整个转型的**认知转折点**

**学会了：** ReAct 循环原理、Tool 定义（JSON Schema）、工具执行与容错、流式输出 + loading 动画

**产出：** `git_agent_practice.py`（裸写 Agent，含 4 个 git 工具 + 完整的 Reason→Act→Observe 循环）

**关键认知：**
- **模型只决策，不执行。** 模型输出 tool_calls 指令，你执行工具，结果喂回模型
- ReAct = Reason（模型想）→ Act（你执行）→ Observe（结果回灌）→ 再 Reason
- 终止条件：模型不再返回 tool_calls，直接返回 content

---

### 第4周：FastAPI 入门

**学会了：** 路由、Pydantic 数据校验、Swagger 自动文档、async/await、Depends 依赖注入、gather 并发

**产出：** `api/main.py` + `api/agent_service.py`（异步 Git Agent API）

**关键认知：**
- FastAPI 路由本质：URL → 函数的映射表，装饰器语法糖
- Pydantic BaseModel：类型注解自动转 JSON Schema，自动校验
- `await` 是"让出 CPU 的等"，不是死等——FastAPI 能在等待时处理其他请求
- `yield` + `Depends`：进入时初始化，退出时自动清理，写一次到处复用
- `gather`：同时点燃多个 async 任务，按传入顺序返回结果
- `asyncio.to_thread`：把同步阻塞操作扔到线程池，不阻塞事件循环

---

## 当前项目结构

```
PythonStudy/
├── docs/
│   ├── interview/          # 面试题（q1-q5）
│   ├── notes/              # 学习笔记（10篇）
│   │   ├── token-and-context-management.md
│   │   ├── model-parameters.md
│   │   ├── langchain-notes.md
│   │   ├── learning-checklist.md
│   │   ├── python-import.md
│   │   ├── python-yield.md
│   │   ├── python-gather.md
│   │   └── python-unpacking-zip.md
│   └── plans/              # 学习计划
│       ├── transition-plan.md
│       └── interview-notes.md
├── learning/
│   ├── python-basics/      # 18 个基础练习
│   └── concurrency/        # async/await 练习
└── myagent/
    ├── api/
    │   ├── main.py         # FastAPI 路由（/review + /todos + /health）
    │   ├── agent_service.py # ReAct 核心逻辑（异步版）
    │   └── gen_openapi.py  # 静态文档生成
    ├── langchain/          # LangChain 了解级 demo
    ├── git_agent_practice.py # 命令行版 Git Agent
    └── git_agent_test.py   # 测试脚本
```

---

## 检验通过

按三步检验法自检：

| 检验项 | 结果 |
|--------|------|
| ReAct 循环核心 | ✅ "模型自主调用工具，根据结果自主决策" |
| async/await | ✅ "服务同时处理多请求 + 并行操作降低耗时" |
| FastAPI 路由 | ✅ "定义路由自动绑定最近的函数" |

---

## 下一阶段

第5周：RAG 基础（Embedding + 向量数据库 + 文档分片）