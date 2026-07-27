# FastAPI 入门学习设计

> 目标：用一周时间（Day 22-28），掌握 FastAPI 核心能力，并将 Git Agent 包装成可调用的 HTTP API 服务。
> 定位：实战级——不是了解概念就过，而是要产出能跑的代码。

---

## 学习产出

1. FastAPI 核心概念笔记（路由、Pydantic、依赖注入、自动文档）
2. async/await 基础笔记（与 PHP 对比理解）
3. Git Agent API 服务（可 curl 调用，返回结构化 JSON）
4. 踩坑记录（延续之前的风格）

---

## 两阶段安排

### 阶段一：基础打底（Day 22-23）

**Day 22（周一）—— FastAPI 基础 + Pydantic**

| 内容 | 时长 | 产出 |
|------|------|------|
| 安装 FastAPI + uvicorn，跑通 Hello World | 20min | 第一个路由能访问 |
| 路由基础：路径参数、查询参数、请求体 | 30min | 3 种参数写法的 demo |
| Pydantic BaseModel：字段定义、类型验证、默认值 | 30min | 请求/响应模型 demo |
| 自动文档：访问 /docs，理解 Swagger UI | 10min | 截图记录 |
| 练习：写一个简单的待办事项 CRUD（2-3 个路由即可） | 40min | `todo_api.py` |

**Day 23（周二）—— async/await + FastAPI 异步路由**

| 内容 | 时长 | 产出 |
|------|------|------|
| async/await 概念：同步 vs 异步，用餐厅点餐比喻理解 | 20min | 概念笔记 |
| Python async 基础：async def、await、asyncio.run | 30min | 异步 demo |
| 与 PHP 对比：PHP-FPM 同步阻塞 vs Python 异步非阻塞 | 10min | 对比笔记 |
| FastAPI 异步路由写法：async def + await | 20min | 异步路由 demo |
| 依赖注入（Depends）：从环境变量读配置 | 20min | Depends demo |
| 练习：把 Day22 的 CRUD 改为异步版本 | 30min | `todo_api_async.py` |

---

### 阶段二：Git Agent API 实战（Day 24-27）

**Day 24（周三）—— 设计请求/响应模型 + 第一个路由**

| 内容 | 时长 | 产出 |
|------|------|------|
| 设计 API 接口：`POST /review`、`GET /health` | 20min | 接口设计文档 |
| 定义 Pydantic 模型：ReviewRequest、ReviewResponse | 20min | 模型代码 |
| 实现 `GET /health` 健康检查 | 10min | 可访问 |
| 实现 `POST /review` 骨架（先返回 mock 数据） | 30min | Swagger 可测试 |
| 接入 Git Agent：把 `git_agent_practice.py` 的核心逻辑抽成可调用函数 | 40min | Agent 可被 API 调用 |

**Day 25（周四）—— 错误处理 + 日志 + 完善路由**

| 内容 | 时长 | 产出 |
|------|------|------|
| HTTPException：参数校验失败、Agent 执行异常 | 20min | 错误响应规范 |
| 日志记录：logging 模块基础配置 | 15min | 请求日志输出 |
| 完善 `POST /review`：接入真实 Agent，处理超时 | 40min | 端到端可跑 |
| 用 curl 测试完整流程 | 15min | 测试记录 |

**Day 26（周五）—— 配置管理 + 代码整理**

| 内容 | 时长 | 产出 |
|------|------|------|
| pydantic-settings：API Key 从 .env 读取 | 20min | 配置管理 |
| 代码整理：类型注解、文件结构 | 30min | 代码规范 |
| 编写启动脚本 | 10min | `run.sh` |
| 里程碑自检：curl 请求 API，得到结构化 JSON 审查结果 | 10min | 通过 |

**Day 27（周六，5-6h）—— 综合实战**

| 内容 | 时长 | 产出 |
|------|------|------|
| 加入请求限流（slowapi） | 40min | 限流中间件 |
| 加入流式输出支持（SSE） | 60min | Agent 输出可流式返回 |
| 编写 API 使用说明 | 30min | README 片段 |
| 端到端测试：5 个不同问题 | 30min | 测试记录 |
| 踩坑记录 + 笔记整理 | 30min | 踩坑文档 |

**Day 28（周日）—— 复盘**

| 内容 | 时长 | 产出 |
|------|------|------|
| 第一阶段复盘：回顾 4 周成果 | 30min | 复盘笔记 |
| 整理项目代码结构 | 30min | 目录规范 |
| 预习：了解 Embedding 和向量数据库概念 | 30min | 概念笔记 |

---

## API 接口设计

### `GET /health`

健康检查，确认服务可用。

**响应**：
```json
{
  "status": "ok",
  "model": "deepseek-v4-flash"
}
```

### `POST /review`

提交代码审查请求，Agent 分析后返回结果。

**请求**：
```json
{
  "question": "最近一次提交改了什么",
  "repo_path": "/path/to/repo"
}
```

- `question`：必填，自然语言问题
- `repo_path`：必填，Git 仓库路径

**响应**：
```json
{
  "answer": "最近一次提交修改了 3 个文件...",
  "tool_calls": [
    {"name": "get_commits", "arguments": "{}", "result_summary": "获取到5条提交记录"},
    {"name": "get_diff", "arguments": "{\"commit_id\": \"abc123\"}", "result_summary": "修改了3个文件"}
  ],
  "token_usage": {
    "prompt_tokens": 1234,
    "completion_tokens": 567,
    "total_tokens": 1801
  }
}
```

- `answer`：Agent 最终回答
- `tool_calls`：Agent 调用了哪些工具（调试/可观测用）
- `token_usage`：Token 消耗统计

### `GET /docs`

FastAPI 自动生成的 Swagger 交互式文档，无需手写。

**设计原则**：
- 接口少而精，先跑通再扩展
- 响应包含 tool_calls 和 token_usage，延续 Agent 可观测思路
- repo_path 做安全校验（禁止路径穿越），延续 read_file 的安全意识

---

## 项目文件结构

```
myagent/
├── git_agent.py              # 已有，参考版
├── git_agent_practice.py     # 已有，手写版
├── git_agent_test.py         # 已有，测试脚本
├── llm_adapter.py            # 已有，LLM 适配器
├── langchain/                # 已有，LangChain 版
└── api/                      # 新增，FastAPI 服务
    ├── main.py               # FastAPI 应用入口 + 路由定义
    ├── models.py             # Pydantic 请求/响应模型
    ├── config.py             # 配置管理（pydantic-settings）
    ├── agent_service.py      # Git Agent 核心逻辑封装（从 git_agent_practice.py 抽取）
    └── requirements.txt      # FastAPI 相关依赖
```

**关键决策**：
- API 代码放在 `myagent/api/` 下，和已有的 Agent 脚本同属 myagent 但独立目录
- `agent_service.py` 是核心——把 `git_agent_practice.py` 的 ReAct 循环抽成可被 API 调用的函数，不改动原文件
- 不搞多层目录（routers/services/），当前规模一个文件一个职责就够了

---

## 错误处理与安全

### 错误处理

| 场景 | 处理方式 | HTTP 状态码 |
|------|---------|------------|
| 请求参数校验失败 | Pydantic 自动校验，返回字段级错误 | 422 |
| repo_path 不存在 | 检查路径是否存在，不存在则拒绝 | 400 |
| repo_path 路径穿越 | 检查是否包含 `..`，禁止 | 400 |
| Agent 执行超时 | 设置 60s 超时，超时返回提示 | 504 |
| LLM API 调用失败 | 捕获异常，返回友好错误信息 | 502 |
| API Key 缺失 | 启动时检查，缺失则报错退出 | 500（启动失败） |

### 安全

- **路径安全**：repo_path 禁止 `..`，防止路径穿越（和之前 read_file 的安全检查一脉相承）
- **密钥管理**：API Key 从 .env 读取，不硬编码（延续之前的踩坑教训）
- **请求限流**：slowapi 限制单 IP 每分钟请求数，防止滥用

---

## 学习检查点

**FastAPI 基础**
1. FastAPI 和 Flask/Django 的核心区别是什么？（自动文档、类型校验、异步原生）
2. Pydantic BaseModel 做了什么？（定义数据结构 + 自动校验 + 自动生成文档）
3. `@app.get` 和 `@app.post` 的区别？

**async/await**
4. 同步和异步的区别？用餐厅比喻解释
5. FastAPI 里 `def` 和 `async def` 都能用，什么时候该用 async？
6. PHP-FPM 和 FastAPI 异步模型的区别？

**工程化**
7. 依赖注入（Depends）解决什么问题？
8. HTTPException 怎么用？和 PHP 里 throw Exception 有什么区别？
9. pydantic-settings 怎么管理配置？和 PHP 的 .env 读取对比？

**实战**
10. curl 请求 `POST /review`，得到结构化 JSON 结果
