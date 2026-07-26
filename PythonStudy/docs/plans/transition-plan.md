# AI Agent 转型落地计划（v2 · 可执行版）

> 基于"16周每日任务清单"重构。主要调整：
> - **时间线放宽到 20-24 周**（原 16 周严重低估，尤其前端/Docker 周会塌方）。
> - **LangChain 降级为了解级**，主线改为"**先裸写再框架**"（裸写 ReAct loop / 裸写 RAG 链），补 **LangGraph** 入门。
> - **新增 AI 评估专题 + 原理散点**（原计划 AI 侧偏薄，工程强但面试易被原理卡）。
> - **新增第二个小项目**（避免单项目风险 + 证明可迁移）。
> - **模型统一**（chat 用 DeepSeek，embedding 待定，见选型部分）。
> - 修正编号跳号（原 100→103）、进度标记统一用本文件勾选框。
> - **基准日期：2026-07-20 = Day 1**，以此计算进度。
>
> 个人背景：PHP 后端转应用层 Agent 开发。当前基础：日常用 Claude Code/Cursor 做 PHP 业务，有 Agent 体感但未自写过 ReAct loop / RAG 全链路。

---

## 执行节奏

- **工作日**：1.5-2h（20min 学理论 + 60-90min 写码 + 10min 提交+笔记）
- **周末**：5-6h（30min 回顾 + 4-5h 实战 + 30min 提交+周报）
- **硬止损规则**：某周落后 ≥2 天 → 整体顺延一周，不要硬塞。宁可稳，不要赶。
- **每日 10 分钟原理散点**：每天看一篇短文（注意力机制/token/幻觉/SFT·RLHF·DPO 等），不集中到最后。原理是面试高频卡点，靠积累。
- 标记：`[ ]` 待办 / `[x]` 完成 / `[~]` 部分完成

---

## 第一阶段：基础奠基（第1-5周）

### 第1周：Python 语法速通
**Day 1（周一）**
- [ ] 安装 Python 3.11+，配置 VS Code + Python 插件
- [ ] 创建项目文件夹，执行 git init
- [ ] 学习：变量、数据类型、字符串操作、f-string
- [ ] 练习：写 5 个小脚本（温度转换、字符串反转、列表去重等）
- [ ] ⚠️ 预算提醒：Day1 任务偏重，可能需 2-2.5h，合理

**Day 2（周二）**
- [ ] 学习：控制流（if/elif/else、for/while、break/continue）
- [ ] 练习：用 Python 写一个猜数字游戏
- [ ] 学习：函数定义、参数（位置/关键字/默认值）、返回值、类型注解
- [ ] 练习：封装 3 个工具函数（计算器相关）

**Day 3（周三）**
- [ ] 学习：列表推导式、字典推导式、生成器（yield）
- [ ] 对比练习：用 PHP foreach 和 Python list comprehension 分别实现同一功能
- [ ] 学习：异常处理（try/except/finally、自定义异常）
- [ ] 练习：给之前的函数加上异常处理

**Day 4（周四）**
- [ ] 学习：类与对象、`__init__`、方法、继承、dataclass
- [ ] 练习：用 Python 写一个 User 类（属性：用户名、密码、邮箱；方法：验证密码、修改信息）
- [ ] 学习：虚拟环境（venv）/ uv 创建与激活
- [ ] 为项目创建 venv，安装第一个第三方包

**Day 5（周五）**
- [ ] 学习：文件读写、with 语句、pathlib
- [ ] 学习：SQLite 基础 + Python sqlite3 模块
- [ ] 练习：创建一个 SQLite 数据库，建用户表，实现增删改查

**Day 6（周六，5-6h）**
- [ ] 综合实战：用 Python 重写 PHP 登录验证模块
  - 创建 SQLite 用户表
  - 实现用户注册（密码 hash，用 bcrypt 或 hashlib）
  - 实现登录验证
  - 实现修改密码
- [ ] 所有代码提交到 GitHub
- [ ] 自检：能在 5 秒内解释 list comprehension 和 PHP foreach 的区别

**Day 7（周日）**
- [ ] 复盘本周学习，整理笔记
- [ ] 预习：浏览目标 LLM 的 API 文档（选定一家，见下"模型选型"），了解基本概念

---

### 模型选型（已确定，贯穿全程）

- [x] **chat 用 DeepSeek**（便宜、快、中文好；当前开放 `deepseek-v4-flash` / `deepseek-v4-pro`）
- [ ] **embedding 待定**（⚠️ 实测 DeepSeek **不提供 embedding 接口**，端点 404）：
  - 倾向方案：**本地开源 embedding 模型 `BAAI/bge-small-zh` + 本地 Chroma**（零 API、零 key、不受云可用性影响，简历可写"部署开源 embedding 模型"）
  - 备选：智谱 GLM `embedding-3` / 通义 / OpenAI（要 API key）
  - ⚠️ embedding 不急，第5周做 RAG 时再定。**第3周裸写 ReAct loop 不需要 embedding**
  - ⚠️ 不管选哪个，**定下后不能换**（换 = 向量维度/语义空间不同 = 向量库作废重算）
- [x] chat API 已调通（DeepSeek），能拿到结果
- [x] 已实战完成一个"周报分析工具"（覆盖了 Day8-13 的核心能力：API 调用 + Prompt + 结构化输出 + 完整小工具）
- [x] chat API 已调通（DeepSeek），能拿到结果
- [x] 已实战完成一个"周报分析工具"（覆盖了 Day8-13 的核心能力：API 调用 + Prompt + 结构化输出 + 完整小工具）

---

### 第2周：LLM 初体验 + API 调用 ✅ 已超额完成
> 📍 当前进度：本周核心能力已全部掌握。不仅跑通了 chat API，还完成了**周报分析工具**（实战项目）和 **Function Calling demo**（`react_minimal_demo.py`——模型自主决定调用工具）。Function Calling 是第3周 ReAct 的地基，你已提前铺好。

**Day 8（周一）** ✅
- [x] 学习：Token 概念、上下文窗口、计费方式
- [x] 注册 DeepSeek API，获取 API Key，存入 .env（不硬编码）
- [x] 用 Python 调用 chat API，发送消息并打印响应
- [x] 用 Python 调用 embedding API ← **已验证 DeepSeek 无此接口，embedding 待第5周另选方案**

**Day 9（周二）** ✅
- [x] Prompt Engineering 基础（周报工具已实践）
- [x] JSON 解析、结构化输出（周报工具已实践）

**Day 10（周三）** 🔄 部分完成
- [~] Temperature、Top-p、流式输出 ← 面试知识点，建议抽空补齐概念

**Day 11-13（周四-周六）** ✅ 已通过周报分析工具实战覆盖
- [ ] 实战：写一个脚本，读取一段 PHP 代码文件
- [ ] 设计 Prompt：让大模型判断代码是否有 SQL 注入风险
- [ ] 要求模型以 JSON 格式输出：`{"risk": true/false, "line": 5, "reason": "..."}`
- [ ] 测试 3 段不同代码，验证输出稳定性（注意 Temperature=0 但仍可能不一致）

**Day 12（周五）**
- [ ] 优化脚本：加入异常处理、API 重试逻辑（指数退避）
- [ ] 支持命令行参数：`python review.py --file code.php`
- [ ] 加入 Token 计数，打印每次调用的 Token 消耗
- [ ] 代码提交到 GitHub

**Day 13（周六，5-6h）**
- [ ] 综合实战：完善代码审查脚本
  - 支持批量审查（传入目录，遍历所有 PHP 文件）
  - 输出结构化审查报告（JSON 文件）
  - 加入不同严重等级（高/中/低）
- [ ] 自检：能说出什么是 Token，你的输入大概消耗了多少 Token

**Day 14（周日）**
- [ ] 复盘本周，整理 Token 消耗记录
- [ ] 预习：浏览 Agent 框架生态概览（LangChain / LangGraph / LlamaIndex 各自定位，**只看概览**）

---

### 第3周：Agent 核心——裸写 ReAct loop（本阶段重中之重）

> ⚠️ 本周是整个转型的**认知转折点**：你从"用 Agent 工具的人"变成"写 Agent 的人"。**主线是裸写，不是学框架。**
> 📍 **当前进度**：Day15-19 核心已完成（ReAct loop + 容错 + 3工具）。Day17-18（git 工具封装）和 Day20（Git Agent 综合实战）待完成。

**Day 15（周一）** ✅
- [x] 学习：Agent 框架生态概览（LangChain/LangGraph/LlamaIndex 定位对比，**了解级**）
- [x] 学习：Function Calling 原理（模型如何决定调用哪个工具、工具定义 JSON Schema）
- [x] 学习：ReAct 循环原理（Reason-Act：模型决策→执行工具→结果回灌上下文→再决策→终止条件）
- [x] ⚠️ 对应面试笔记模块 A1/A2，务必吃透"决策-执行分离""结果回灌""终止条件"

**Day 16（周二）** ✅
- [x] **裸写起步**：用 requests/SDK 调 API，定义一个工具（calculate）的 JSON Schema
- [x] 把工具定义塞进 tools 参数，发给模型
- [x] 让模型根据用户问题自动决定是否调用工具
- [x] 打印模型返回的"工具调用指令"，理解它是个结构化输出

**Day 17（周三）** 📍 7/23 Day4 目标
- [ ] 学习：Python subprocess 模块
- [ ] 练习：用 subprocess 执行 `git log --oneline -5`，解析输出
- [ ] 练习：用 subprocess 执行 `git diff HEAD~1`，获取代码变更
- [ ] 封装为 Python 函数：`get_git_diff(repo_path, commit_id)`

**Day 18（周四）** 📍 7/23 Day4 目标
- [ ] 将 git 函数封装为 Agent Tool（JSON Schema + 注册到 TOOL_FUNCTIONS）
- [ ] 测试：让 Agent 回答"最近一次提交改了什么"
- [ ] 调试 Tool 描述，确保 Agent 能正确选择调用

**Day 19（周五）** ✅ 已完成
- [x] 再创建 1-2 个工具（get_time / get_file_info）
- [x] 让 Agent 在多 Tool 场景下正确选择
- [x] 处理工具调用失败、参数错误等边界（execute_tool 三层容错）
- [x] 自检：能解释 Agent、Tool、ReAct 循环三者关系（对应面试笔记 A1）

**Day 20（周六，5-6h）** ✅
- [x] 综合实战：构建一个能获取 Git 信息的裸写 Agent
  - Tool 1：获取 commit 列表
  - Tool 2：获取指定 commit 的 diff
  - Tool 3：获取文件内容
  - Agent 能根据自然语言指令组合使用多个 Tool
- [x] 测试 5 个不同问题，记录 Agent 的 Tool 选择是否正确
  - 打招呼、获取提交列表、查看提交详情、读文件、复杂问题 - 全部通过
- [x] 测试脚本：`myagent/git_agent_test.py`

**Day 21（周日）** 🔄 待完成
- [ ] **LangChain 了解级**（半天，非主线）：跑通官方 Quick Start，记住四个核心概念（Chain/Agent/Tool/Memory）、看一眼 LCEL 语法
- [ ] 产出对比笔记：**裸写 ReAct loop vs LangChain**，LangChain 帮你封装了哪些手写步骤、藏了什么
- [ ] 能回答 5 个了解级问题（见附录 B），即可算过

---

### 第4周：FastAPI 入门
**Day 22（周一）**
- [ ] 学习：FastAPI 基础（安装、第一个路由、运行 dev server、uvicorn）
- [ ] 学习：Pydantic 数据模型（BaseModel、字段验证）
- [ ] 练习：创建一个简单的 CRUD API（如：待办事项管理）
- [ ] 访问自动生成的 Swagger 文档（/docs）

**Day 23（周二）**
- [ ] 学习：async/await 基础（与 PHP 的异步对比）
- [ ] 学习：FastAPI 异步路由写法
- [ ] 练习：将之前的 CRUD API 改为异步版本
- [ ] 学习：依赖注入（Depends）

**Day 24（周三）**
- [ ] 设计代码审查 API 的请求/响应模型
  - 请求：`ReviewRequest(repo_path, commit_id)`
  - 响应：`ReviewResponse(risks, summary, token_count)`
- [ ] 实现 `POST /review` 路由，内部调用之前的 Agent
- [ ] 测试：用 Swagger UI 发送请求，验证响应

**Day 25（周四）**
- [ ] 加入错误处理（HTTPException、参数验证失败）
- [ ] 加入日志记录（logging 模块）
- [ ] 实现健康检查接口：`GET /health`
- [ ] 用 curl 测试完整流程

**Day 26（周五）**
- [ ] 代码整理、添加注释、类型注解
- [ ] 编写简单的启动脚本
- [ ] 代码提交到 GitHub
- [ ] 🎯 里程碑检查：用 curl 请求 API，得到结构化 JSON 审查结果

**Day 27（周六，5-6h）**
- [ ] 综合实战：完善 API 服务
  - 加入配置管理（API Key 从环境变量读取，用 pydantic-settings）
  - 加入请求限流（slowapi）
  - 编写 API 使用说明
- [ ] 录制一个 30 秒的命令行演示

**Day 28（周日）**
- [ ] 第一阶段复盘：回顾 4 周成果
- [ ] 整理项目代码结构
- [ ] 预习：了解 Embedding 和向量数据库概念

---

### 第5周：RAG 基础 + 文档处理
**Day 29（周一）**
- [ ] 学习：Embedding 原理（文本→向量，语义相似度，余弦相似度）
- [ ] 学习：不同 Embedding 模型对比
- [ ] 练习：调用 Embedding API，将几句话转为向量，计算余弦相似度（手写 numpy 实现）
- [ ] ⚠️ 对应面试笔记模块 B1，吃透"为什么语义相近的向量距离近"

**Day 30（周二）**
- [ ] 实战：整理 PHP 编码规范文档（至少 10 条规则）
  - SQL 注入防护、XSS 防护、密码存储、输入验证、错误处理、CSRF 等
- [ ] 保存为 Markdown 格式
- [ ] 这份文档是后续 RAG 的"知识库原料"，认真写

**Day 31（周三）**
- [ ] 学习：文档加载（纯 Python 读 md/txt，或简单 loader）
- [ ] 学习：文本分片策略（按字符切、递归切、按语义切）
- [ ] 练习：用两种分片方式处理同一文档，对比结果
- [ ] ⚠️ 对应面试笔记模块 B2，分片是 RAG 工程最易出问题环节

**Day 32（周四）**
- [ ] 深入理解分片参数：chunk_size、chunk_overlap
- [ ] 实验：不同 chunk_size（200/500/1000）对分片结果的影响
- [ ] 记录实验结果，选择最佳参数组合
- [ ] 自检：能说出为什么需要分片，分片大小对检索的影响

**Day 33（周五）**
- [ ] 编写完整的文档处理脚本：加载→分片→预览
- [ ] 加入分片统计信息（片段数、平均长度、Token 估算）
- [ ] 代码提交到 GitHub

**Day 34（周六，5-6h）**
- [ ] 综合实战：构建文档处理 Pipeline
  - 支持多种格式（.md、.txt、.pdf）
  - 可配置分片策略
  - 输出处理报告（片段数、Token 估算）
- [ ] 准备更多知识文档（安全规范、代码风格指南等）

**Day 35（周日）**
- [ ] 复盘本周，整理 Embedding 和分片笔记
- [ ] 预习：了解 Chroma 向量数据库

---

## 第二阶段：核心能力建设（第6-9周）

### 第6周：向量存储与检索
**Day 36（周一）**
- [ ] 学习：向量数据库概念与选型（Chroma vs FAISS vs Milvus，了解各自定位）
- [ ] 安装 Chroma：`pip install chromadb`
- [ ] 练习：用 Chroma SDK（裸用，不接 LangChain）创建集合，插入几条向量
- [ ] 练习：执行相似度检索，查看返回结果

**Day 37（周二）**
- [ ] 实战：将上周的规范文档分片向量化并存入 Chroma（裸写：embedding API + Chroma SDK）
- [ ] 测试：输入"SQL 注入"，检索 Top-3 相关片段
- [ ] 记录检索结果，评估相关性

**Day 38（周三）**
- [ ] 学习：Embedding 模型选型与对比
- [ ] 实验：用不同 Embedding 模型处理同一文档
- [ ] 对比检索效果差异
- [ ] 记录成本与效果对比

**Day 39（周四）**
- [ ] 实战：构建完整的检索脚本
  - 输入：自然语言查询
  - 输出：Top-K 相关规范条目（含来源、相似度分数）
- [ ] 加入元数据过滤（如按规范类别筛选）
- [ ] 自检：输入"SQL 注入"能检索到对应条目

**Day 40（周五）**
- [ ] 优化检索：调整 Top-K 值、相似度阈值
- [ ] 编写检索效果测试用例（10 个查询，人工评估相关性）
- [ ] 代码提交到 GitHub

**Day 41（周六，5-6h）**
- [ ] 综合实战：构建知识库检索服务
  - 支持文档增量添加
  - 支持多种检索模式（相似度、MMR 多样性检索）
  - 输出格式化检索报告
- [ ] 测试 20 个不同查询，记录检索准确率

**Day 42（周日）**
- [ ] 复盘本周，整理向量数据库笔记
- [ ] 预习：了解 RAG 链构建方式

---

### 第7周：RAG + Agent 整合（先裸写再对比 LangChain）
**Day 43（周一）**
- [ ] **裸写 RAG 链**：embedding 检索 Top-K + 拼 prompt + 调 chat API（全程不依赖 LangChain）
- [ ] 测试：用规范文档回答"如何防止 SQL 注入"
- [ ] ⚠️ 裸写完才知道哪步是 LLM 能力、哪步是框架封装

**Day 44（周二）**
- [ ] 学习：Prompt 模板设计（system/user 角色设定、上下文组织）
- [ ] 设计代码审查 Prompt 模板：
  - 角色：PHP 代码安全审查专家
  - 上下文：检索到的规范条目
  - 输入：待审查代码
  - 输出：结构化审查意见
- [ ] 测试模板效果

**Day 45（周三）**
- [ ] 实战：将 RAG 检索集成到 Agent 中
- [ ] 流程：用户提交代码 → RAG 检索相关规范 → Agent 结合规范审查代码
- [ ] 对比测试：同一代码，有 RAG vs 无 RAG 的审查结果
- [ ] 记录差异，RAG 是否让审查意见更具体、更准确

**Day 46（周四）**
- [ ] 优化 Prompt 模板，让 Agent 输出带规范引用
- [ ] 目标：Agent 能说出"根据规范第 X 条，此处应使用参数化查询"
- [ ] 测试 5 段代码，验证规范引用的准确性
- [ ] 自检：Agent 能指出具体规范条目

**Day 47（周五）**
- [ ] **LangChain 了解级**（半天）：用 RetrievalQA / create_retrieval_chain 跑一遍同样的 RAG，**对比**你的裸写
- [ ] 产出对比笔记：LangChain 帮你封装了检索→拼 prompt→调模型的哪几步
- [ ] 将 RAG+Agent 整合到 FastAPI 服务中
- [ ] 代码提交到 GitHub

**Day 48（周六，5-6h）**
- [ ] 综合实战：完善 RAG 增强的代码审查 Agent
  - 支持多种规范文档
  - 审查意见包含：风险等级、规范引用、修复建议
  - 对比报告：有 RAG vs 无 RAG 的审查质量
- [ ] 准备 10 段测试代码，跑完整审查流程

**Day 49（周日）**
- [ ] 复盘本周，整理 RAG 整合笔记
- [ ] **了解 LangGraph 基础**（2-3h）：StateGraph、节点、边的概念，知道它和 LangChain 的定位差异（链式 pipeline vs 状态机/图），不深做
- [ ] 预习：了解 LangFuse/LangSmith 可观测性

---

### 第8周：RAG 评估专题（新增·重点补 AI 侧）
> ⚠️ 原计划缺评估，面试必问"你怎么客观量化系统质量"。本周专门补这块。

**Day 50（周一）**
- [ ] 学习：RAG 评估指标体系
  - 检索质量：Recall@K、Precision@K、MRR、context precision
  - 生成质量：faithfulness（忠实度）、answer relevance（答案相关性）
- [ ] 理解：为什么"人工看 20 个查询"不够，需要可复现的自动评估

**Day 51（周二）**
- [ ] 选型并安装评估工具：ragas 或自写评估脚本
- [ ] 构建**评估数据集**：20-30 个（query, 期望相关文档, 标准答案）三元组
- [ ] 对你的 RAG 系统跑一次自动评估，得到数字基线

**Day 53（周三）**（编号按原习惯略跳，保持节奏）
- [ ] 分析评估结果：哪类 query 检索召回低？哪类答案 faithfulness 低？
- [ ] 定位根因（分片问题？embedding 问题？prompt 问题？）并修复
- [ ] 重跑评估，对比改进前后数字（对应面试笔记 B2 思路）

**Day 54（周四）**
- [ ] 加入 **LLM-as-Judge**：用另一个模型给你的审查结果打分（准确度、规范引用对不对）
- [ ] 记录评估结果，形成可写进简历的数字（如"检索 Recall@5 从 0.6 优化到 0.85"）

**Day 55（周五）**
- [ ] 把评估流程脚本化，可重复运行
- [ ] 代码提交到 GitHub
- [ ] 🎯 里程碑：简历可写出量化评估数据

**Day 56（周六，5-6h）**
- [ ] 综合实战：建一个评估 Dashboard（简单 HTML+图表即可）
- [ ] 记录关键指标：Recall@K、faithfulness、平均 Token 消耗、响应时间
- [ ] 截图存档，作为简历/面试证据

**Day 57（周日）**
- [ ] 复盘本周，整理评估笔记（这块是新知识，多记）
- [ ] 预习：可观测性（LangFuse）

---

### 第9周：可观测性与调试
**Day 58（周一）**
- [ ] 学习：可观测性概念（Trace、Span、Token 追踪）
- [ ] 注册 LangFuse 账号，了解界面和功能
- [ ] 安装 LangFuse SDK：`pip install langfuse`
- [ ] 学习：LangFuse 与裸写代码的集成方式

**Day 59（周二）**
- [ ] 实战：集成 LangFuse 到你的 Agent
- [ ] 配置环境变量（LANGFUSE_PUBLIC_KEY、LANGFUSE_SECRET_KEY）
- [ ] 运行一次审查，在 LangFuse 中查看 Trace
- [ ] 理解 Trace 中的每个步骤（检索→Prompt 构建→LLM 调用→输出）

**Day 60（周三）**
- [ ] 用真实代码场景跑 3 次审查
- [ ] 在 LangFuse 中对比 3 次 Trace：Token 消耗差异、检索结果差异、响应时间差异
- [ ] 记录分析结果

**Day 61（周四）**
- [ ] 定位一次"错误输出"的根因（对应面试笔记 B2 排错思路）
  - 检索不准？→ 优化分片/Embedding
  - Prompt 问题？→ 优化模板
  - 模型幻觉？→ 加入约束/引用
- [ ] 修复问题，重新测试
- [ ] 记录调试过程（这是面试爱问的"你怎么调 Agent"）

**Day 62（周五）**
- [ ] 加入自定义 Span，标记关键步骤
- [ ] 加入 Token 消耗统计和成本计算
- [ ] 代码提交到 GitHub
- [ ] 🎯 里程碑：能展示 Agent 完整思考链路和 Token 消耗

**Day 63（周六，5-6h）**
- [ ] 综合实战：构建可观测的 Agent 服务
  - LangFuse 完整集成
  - 自定义 Dashboard
  - 审查质量评分机制（结合第8周评估）
- [ ] 截图 LangFuse 仪表盘，记录关键指标

**Day 64（周日）**
- [ ] 第二阶段复盘：回顾 9 周成果
- [ ] 整理项目，确保代码可运行
- [ ] 预习：了解 Celery 和 Redis

---

## 第三阶段：工程化与产品化（第10-14周）

### 第10周：异步任务与消息队列
**Day 65（周一）**
- [ ] 学习：Celery 基础概念（Worker、Broker、Task、Result Backend）
- [ ] 安装 Celery + Redis：`pip install celery redis`
- [ ] 练习：写一个简单的 Celery Task（如：延时加法运算）
- [ ] 启动 Worker，触发 Task，获取结果

**Day 66（周二）**
- [ ] 学习：Redis 基础（安装、数据类型、常用命令）
- [ ] 练习：用 redis-cli 执行基本操作（SET/GET/LIST/PUB）
- [ ] 配置 Celery 使用 Redis 作为 Broker

**Day 67（周三）**
- [ ] 实战：将代码审查 Agent 封装为 Celery Task
- [ ] 改造 API：
  - `POST /review` → 立即返回 `{"task_id": "xxx"}`
  - `GET /task/{task_id}` → 返回任务状态和结果
- [ ] 测试：提交审查请求，2 秒内得到 task_id

**Day 68（周四）**
- [ ] 实现任务状态查询接口（PENDING → PROCESSING → COMPLETED/FAILED）
- [ ] 加入任务超时处理
- [ ] 加入任务失败重试（Celery retry 机制）

**Day 69（周五）**
- [ ] 测试：提交大型仓库审查，验证异步执行
- [ ] 加入任务进度反馈（如：正在检索规范...正在分析代码...）
- [ ] 代码提交到 GitHub
- [ ] 自检：大型审查请求 API 能在 2 秒内响应

**Day 70（周六，5-6h）**
- [ ] 综合实战：完善异步任务系统
  - Celery Worker 配置优化
  - 任务优先级队列
  - 并发审查限制
  - 任务清理机制（过期任务自动删除）
- [ ] 压力测试：同时提交 5 个审查请求

**Day 71（周日）**
- [ ] 复盘本周，整理 Celery/Redis 笔记
- [ ] 预习：了解 SQLAlchemy ORM

---

### 第11周：结果持久化与历史查询
**Day 72（周一）**
- [ ] 学习：SQLAlchemy 基础（Engine、Session、Model、CRUD）
- [ ] 练习：用 SQLAlchemy 定义一个简单模型，实现增删改查
- [ ] 学习：数据库迁移（Alembic 基础）

**Day 73（周二）**
- [ ] 设计数据库 Schema：
  - review_tasks 表：task_id, repo_path, commit_id, status, created_at, completed_at
  - review_results 表：task_id, risks(JSON), summary, token_count, cost
- [ ] 用 SQLAlchemy 定义模型
- [ ] 执行数据库迁移

**Day 74（周三）**
- [ ] 实战：将审查结果存入数据库
- [ ] 在 Celery Task 完成后自动保存结果
- [ ] 实现历史查询接口：`GET /reviews?repo=xxx&date=xxx`
- [ ] 测试：多次审查后，查询历史记录

**Day 75（周四）**
- [ ] 实现统计接口：
  - `GET /stats` → 审查次数、平均 Token 消耗、平均耗时
  - `GET /stats/trends` → 按天/周统计趋势
- [ ] 加入分页支持
- [ ] 自检：能查到"上周总共审查了 10 次，平均消耗 500 Token/次"

**Day 76（周五）**
- [ ] 加入数据导出功能（CSV/JSON）
- [ ] 代码提交到 GitHub

**Day 77（周六，5-6h）**
- [ ] 综合实战：完善数据持久化系统
  - 数据库连接池配置
  - 事务管理
  - 数据备份脚本
  - 清理过期数据的定时任务
- [ ] 测试完整流程：审查→存储→查询→统计

**Day 78（周日）**
- [ ] 复盘本周，整理数据库设计笔记
- [ ] 预习：了解 Docker 基础

---

### 第12周：容器化部署（预算宽松，可能溢出 1-2 天）
**Day 79（周一）**
- [ ] 学习：Docker 基础概念（镜像、容器、Dockerfile、docker-compose）
- [ ] 练习：写一个简单的 Dockerfile，构建 Python 应用镜像
- [ ] 练习：docker build、docker run 基本操作

**Day 80（周二）**
- [ ] 为 Agent 服务编写 Dockerfile
  - 基于 python:3.11-slim
  - 安装依赖（requirements.txt）
  - 复制代码
  - 设置启动命令
- [ ] 构建镜像，本地测试运行

**Day 81（周三）**
- [ ] 学习：Docker Compose 多服务编排
- [ ] 编写 docker-compose.yml：
  - agent-service（FastAPI + Celery Worker）
  - redis
  - chroma
  - postgresql
- [ ] 配置服务间网络和依赖关系

**Day 82（周四）**
- [ ] 测试：`docker compose up` 一键启动所有服务
- [ ] 验证各服务连通性
- [ ] 处理常见问题（端口冲突、数据持久化、环境变量）
- [ ] 加入 volume 配置，确保数据持久化

**Day 83（周五）**
- [ ] 编写 .dockerignore 文件
- [ ] 优化镜像大小（多阶段构建）
- [ ] 编写部署文档
- [ ] 代码提交到 GitHub
- [ ] 自检：在另一台电脑上成功拉起服务

**Day 84（周六，5-6h）**
- [ ] 综合实战：完善容器化部署
  - 加入健康检查（healthcheck）
  - 加入环境变量配置（.env 文件）
  - 编写详细的 README（一键启动指南）
  - 测试：从零开始 `git clone` → `docker compose up` → 可用
- [ ] 在云服务器上部署测试（如有条件）

**Day 85（周日）**
- [ ] 复盘本周，整理 Docker 笔记
- [ ] 预习：了解 GitHub Actions 基础

---

### 第13周：CI/CD 集成
**Day 86（周一）**
- [ ] 学习：GitHub Actions 基础（workflow、job、step、runner）
- [ ] 练习：创建一个简单的 workflow（如：push 时自动运行 pytest）
- [ ] 学习：GitHub API 基础（创建 Comment、设置 Status Check）

**Day 87（周二）**
- [ ] 设计 CI/CD 流程：
  - 当有 PR/MR 时 → 触发 Agent 审查
  - Agent 审查完成 → 将结果作为 Comment 贴到 PR 下
  - 审查结果可设为**建议性 + 可选门禁**（⚠️ 强门禁误报得罪开发，面试讲"可选门禁"更成熟）
- [ ] 编写 workflow 配置文件框架

**Day 88（周三）**
- [ ] 实战：编写 `.github/workflows/ai-review.yml`
  - 触发条件：pull_request
  - 步骤：检出代码 → 获取 diff → 调用 Agent API → 解析结果
- [ ] 测试 workflow 运行

**Day 89（周四）**
- [ ] 实战：Agent 将审查结果作为 Comment 贴到 PR 下
- [ ] 使用 GitHub API 或 Action（如 actions/github-script）
- [ ] 格式化审查结果为 Markdown Comment
- [ ] 测试：创建测试 PR，验证 Comment 自动出现

**Day 90（周五）**
- [ ] 实现"审查未通过可阻塞合并"的可选门禁功能
- [ ] 设置 Status Check（可设为 required 或 advisory）
- [ ] 端到端测试：创建有问题的 PR → Agent 审查 → 出现 Comment
- [ ] 代码提交到 GitHub
- [ ] 自检：测试 PR 自动触发 Agent 审查并留下评论

**Day 91（周六，5-6h）**
- [ ] 综合实战：完善 CI/CD 集成
  - 加入审查结果缓存（同一 commit 不重复审查）
  - 加入超时处理
  - 加入通知（审查完成后飞书/Slack 通知）
  - 编写 CI/CD 使用文档
- [ ] 录制演示：从创建 PR 到 Agent 审查的完整流程
- [ ] ⚠️ 把 Agent 接到你**自己或同事的真实 PHP 项目** GitHub 上跑，留真实 PR Comment 截图——简历可信度神器

**Day 92（周日）**
- [ ] 复盘本周，整理 CI/CD 笔记
- [ ] 预习：了解前端基础（Vue 或直接用现成 UI 库）

---

### 第14周：前端对话界面（预算最宽松，可能需溢出）
> ⚠️ 原计划一周做 Vue 对话+SSE+详情页不现实。这里放宽要求：用现成 UI 库搭最小可用界面，不追求精美。

**Day 93（周一）**
- [ ] 选型：优先用现成 UI 库（Element Plus / Ant Design）+ Vue3，或直接用 React+shadcn/ui
- [ ] 学习：组件基础、模板语法、数据绑定（最小够用即可）
- [ ] 练习：创建一个简单对话界面（输入框+消息列表）

**Day 94（周二）**
- [ ] 学习：SSE（Server-Sent Events）原理
- [ ] 后端：为 FastAPI 添加 SSE 端点（StreamingResponse）
- [ ] 前端：用 EventSource 接收流式数据
- [ ] 测试：前端逐字显示 Agent 输出

**Day 95（周三）**
- [ ] 实战：搭建最小可用的对话界面
  - 用户输入"帮我审查这个 PR"
  - Agent 开始工作，流式输出审查意见
  - 显示审查进度（检索规范中...分析代码中...）
- [ ] 用 UI 库现成组件美化（不自己造样式）

**Day 96（周四）**
- [ ] 加入历史记录展示
- [ ] 加入审查结果详情页（风险列表、规范引用、修复建议）
- [ ] 加入代码高亮显示（highlight.js 或 Prism）

**Day 97（周五）**
- [ ] 加入响应式设计（手机可访问）
- [ ] 加入暗色模式
- [ ] 代码提交到 GitHub
- [ ] 🎯 里程碑：能录 30 秒演示视频

**Day 98（周六，5-6h）**
- [ ] 综合实战：完善前端界面
  - 加入简单 Token 认证
  - 加入审查统计 Dashboard
  - 加入导出审查报告功能
- [ ] 录制 30 秒演示视频
- [ ] 更新 docker-compose.yml，加入前端服务

**Day 99（周日）**
- [ ] 第三阶段复盘：回顾 14 周成果
- [ ] 整理项目，确保全流程可运行
- [ ] 预习：了解性能优化方法

---

## 第四阶段：第二项目 + 收尾备战（第15-20周）

### 第15周：第二个小项目（新增·证明可迁移）
> ⚠️ 单项目撑全程有风险。花 1 周做一个**小而完整**的独立 Agent，换场景证明你能迁移，不是只会代码审查。
> 建议选题（选一个，控制在 5-7 天）：①文档问答 mini Agent（换知识库场景）②客服 FAQ Agent ③数据分析助手（接 SQLite 自然语言查数）

**Day 100（周一）**
- [ ] 选定第二个项目选题，写 1 页设计（场景/工具/数据/流程）
- [ ] 复用已有脚手架（裸写 ReAct + RAG + FastAPI），快速搭起

**Day 101（周二）**
- [ ] 实现核心 Agent 逻辑（工具定义 + ReAct loop）
- [ ] 接入一个新场景的数据/工具

**Day 102（周三）**
- [ ] 接入 RAG 或新工具，跑通端到端
- [ ] 测试 5-10 个用例

**Day 103（周四）**
- [ ] 打磨：异常处理、流式输出、简单前端或 CLI
- [ ] 提交 GitHub，写 README

**Day 104（周五）**
- [ ] 跑一次评估（复用第8周评估框架），得到数字
- [ ] 代码提交 GitHub
- [ ] 🎯 里程碑：简历有两个项目

**Day 105（周六，5-6h）**
- [ ] 综合打磨第二个项目，录 30 秒演示
- [ ] 写一篇技术博客（选题：第二个项目踩坑/与第一个项目的差异）

**Day 106（周日）**
- [ ] 复盘本周，整理第二项目笔记
- [ ] 预习：性能优化

---

### 第16周：性能优化与文档
**Day 107（周一）**
- [ ] 学习：性能测试工具（Apache Bench、wrk）
- [ ] 对 Agent API 进行基准测试
- [ ] 记录：QPS、平均响应时间、P99 延迟
- [ ] 识别性能瓶颈

**Day 108（周二）**
- [ ] 优化1：加入 Redis 缓存（相同代码不重复审查）
- [ ] 优化2：批量 Embedding（减少 API 调用次数）
- [ ] 优化3：连接池优化（数据库、Redis）
- [ ] 重新基准测试，对比优化效果

**Day 109（周三）**
- [ ] 优化4：Prompt 精简（减少 Token 消耗）
- [ ] 优化5：异步并发优化
- [ ] 记录优化前后的 Token 成本对比
- [ ] 编写性能优化报告

**Day 110（周四）**
- [ ] 编写项目 README：
  - 项目简介、技术栈、快速启动指南、API 文档、架构说明
- [ ] 编写 API 文档（补充 Swagger 中缺少的说明）

**Day 111（周五）**
- [ ] 画系统架构图（Excalidraw / Draw.io）
  - 整体架构、数据流、部署架构
- [ ] 编写技术决策文档（为什么裸写不选 LangChain？为什么 Chroma 不选 Milvus？为什么 Celery？）
- [ ] 代码提交到 GitHub

**Day 112（周六，5-6h）**
- [ ] 综合实战：完善所有文档
  - CONTRIBUTING.md、CHANGELOG.md
  - 性能报告、成本分析报告
  - 2-3 篇技术博客（裸写 vs 框架、RAG 分片坑、Agent 调试方法论）
- [ ] 自检：让陌生人看 README，30 分钟内跑起项目

**Day 113（周日）**
- [ ] 复盘本周，整理优化笔记
- [ ] 预习：简历撰写技巧

---

### 第17周：简历重构
**Day 114（周一）**
- [ ] 学习：AI/Agent 岗位简历撰写要点
- [ ] 分析 3-5 个目标岗位 JD，提取关键词
- [ ] 列出你的项目亮点清单

**Day 115（周二）**
- [ ] 重写简历项目经历部分（两个项目都要写）：
  - 智能代码审查 Agent（主线项目）
  - 第二个项目
  - 技术栈：裸写 ReAct、RAG、FastAPI、Celery、Docker、CI/CD、LangFuse、ragas
  - 量化成果：检索 Recall@5 0.85+、Token 成本优化 XX%、P99 XXms
- [ ] 使用 STAR 法则描述（Situation-Task-Action-Result）

**Day 116（周三）**
- [ ] 优化简历其他部分：
  - 技能清单：按熟练度排列
  - 关键词植入：ReAct、RAG、Function Calling、MCP、可观测性、RAG 评估
  - 教育背景精简
- [ ] 准备不同版本（偏 Agent 开发 / 偏 AI 工程化）

**Day 117（周四）**
- [ ] 准备 5 分钟"电梯演讲"：
  - 为什么做（PHP 转 AI 的动机）
  - 怎么做（技术选型和架构决策，含"为什么裸写"）
  - 取得什么成果（量化数据）
- [ ] 对着镜子练习 3 遍，录音回听优化

**Day 118（周五）**
- [ ] 请朋友/同事看简历，收集反馈
- [ ] 根据反馈修改简历
- [ ] 准备项目 Demo 环境（确保随时可演示）
- [ ] 自检：朋友看完简历能复述你的核心亮点

**Day 119（周六，5-6h）**
- [ ] 综合实战：完善面试准备
  - 整理项目技术决策的"为什么"（为什么 Chroma 不 Milvus？为什么 Celery？为什么裸写？）
  - 准备架构图讲解话术
  - 准备代码走讲路线（从入口到核心逻辑）
  - 准备反问面试官的问题清单

**Day 120（周日）**
- [ ] 复盘本周，最终版简历定稿
- [ ] 预习：常见面试问题

---

### 第18周：原理系统补课 + 查漏补缺
> ⚠️ 原理散点应贯穿全程（每日 10 分钟），本周做**系统串联 + 查漏**，不是从零学。

**Day 121（周一）**
- [ ] 对照学习清单，逐项标记掌握程度：✅ 已掌握 / 🔄 需巩固 / ❌ 未掌握
- [ ] 列出 Top 5 薄弱项
- [ ] 对照面试笔记（AI-Agent-Interview-Notes.md）四模块查漏

**Day 122（周二）**
- [ ] 原理补课：Transformer / 注意力机制 / token / 上下文窗口（能讲清"lost in the middle"）
- [ ] 原理补课：幻觉为什么发生、缓解手段
- [ ] 原理补课：SFT / RLHF / DPO 概念层（不求数学推导）

**Day 123（周三）**
- [ ] 针对 Top 5 薄弱项深入练习
- [ ] 重点补：面试高频但你不熟的知识点

**Day 124（周四）**
- [ ] 准备 10 个高频面试问题回答（要点，不背稿）：
  1. 介绍你做的 Agent 项目
  2. RAG 完整流程是什么？
  3. Function Calling 原理？ReAct 循环？
  4. 你如何优化 Token 消耗？
  5. LangChain/LangGraph/裸写的区别？你为什么裸写？
  6. 如何保证 Agent 输出准确性？你怎么评估？
  7. 你的 Agent 如何处理工具调用失败？
  8. MCP 协议是什么？和 Function Calling 的关系？
  9. 你如何监控和调试 Agent？
  10. 从 PHP 转 AI，最大挑战是什么？
- [ ] 每个问题写要点

**Day 125（周五）**
- [ ] 模拟面试：找朋友或用 AI 模拟面试官
- [ ] 录音/录像，回看分析
- [ ] 记录表现不好的问题，重点改进

**Day 126（周六，5-6h）**
- [ ] 第二轮模拟面试（换角度/换人）
- [ ] 优化回答节奏和表达
- [ ] 整理 16 周学习笔记为一份技术博客

**Day 127（周日）**
- [ ] 复盘本周
- [ ] 投递 2-3 个"练手"岗位试水

---

### 第19-20周：投递与面试迭代
**第19周**
- [ ] 投递目标岗位 5-10 个
- [ ] 每次面试后立即复盘，记录被问倒的问题
- [ ] 针对性补课（面试反馈驱动学习）
- [ ] 保持 Demo 环境稳定可用

**第20周**
- [ ] 持续投递 + 面试
- [ ] 根据面试反馈迭代简历和话术
- [ ] 整理面经，沉淀为博客
- [ ] 🎉 计划完成！更新 MEMORY.md 记录转型经历
- [ ] 制定后续学习计划（面试反馈驱动）

---

## 附录 A：每日时间分配建议
**工作日（1.5-2h）**
- 20 分钟：学习理论/文档
- 60-90 分钟：动手写代码
- 10 分钟：提交代码 + 记录笔记 + 每日 10 分钟原理散点

**周末（5-6h）**
- 30 分钟：回顾本周，整理笔记
- 4-5 小时：综合实战项目
- 30 分钟：提交代码 + 写周报

---

## 附录 B：LangChain 了解级过关清单（能回答即算过）
1. LangChain 的核心抽象是什么？（Chain = 把 prompt/model/parser 串起来的管道；LCEL 是语法糖）
2. 它怎么定义 Tool？（`@tool` 装饰器 / BaseTool 类，name+description+args_schema）
3. RetrievalQA 和 create_retrieval_chain 区别？（前者旧版封装、后者新版更灵活）
4. LangChain vs LangGraph？（前者链式 pipeline，后者状态机/图，复杂多步 Agent 用后者）
5. 为什么生产很多团队不用 LangChain？（抽象重、breaking change 频繁、调试黑盒、简单场景过度封装）

---

## 附录 C：关键里程碑（用于自检进度）
- [ ] 第4周末：curl 请求 API 得到结构化 JSON 审查结果
- [ ] 第7周末：RAG 增强 Agent 能引用具体规范条目
- [ ] 第8周末：简历可写出量化评估数据（Recall@K 等）
- [ ] 第9周末：能展示 Agent 完整思考链路和 Token 消耗
- [ ] 第13周末：真实 PR 上有 Agent 审查 Comment（简历证据）
- [ ] 第14周末：30 秒演示视频
- [ ] 第15周末：简历有两个项目
- [ ] 第17周末：简历定稿 + 电梯演讲熟练
- [ ] 第20周末：拿到 offer 或进入持续面试节奏

---

## 附录 D：与面试笔记的对应关系
学习时对照 `AI-Agent-Interview-Notes.md` 四模块，学完每周回看对应题目自测：
- 模块 A（Agent 原理）→ 第3周裸写 ReAct 时吃透 A1/A2
- 模块 B（RAG 工程）→ 第5-8周吃透 B1，并在第8周评估专题补 B2
- 模块 C（Prompt 与结构化输出）→ 第2周 Prompt 工程时积累
- 模块 D（系统设计与工程化）→ 第10-14周工程化时积累
