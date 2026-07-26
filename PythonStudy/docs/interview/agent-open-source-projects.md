# Agent 开源学习项目推荐

> 按学习阶段推荐，每个项目标注适合学什么、代码复杂度、文档质量。

---

## 第一阶段：理解核心概念（1-2 周）

### 1. Smolagents（HuggingFace）

- GitHub: `huggingface/smolagents` | 28K+ stars | Python | Apache-2.0
- **最精简的 Agent 库**，核心代码极少，但完整实现了 Agent 关键概念
- 特色：CodeAgent——让模型生成可执行代码而非结构化文本，而非传统的 function calling
- **最适合从零理解 Agent 原理**，2-3 天可读完源码
- 与你的 CLI 项目体量接近，可直接对比架构

### 2. OpenAI Agents SDK

- GitHub: `openai/openai-agents-python` | 28K+ stars | Python | MIT
- OpenAI 官方出品，**最"正统"的 Agent 实现参考**
- 代码量小，核心逻辑清晰
- 特色：
  - **Handoff**：Agent 交接机制，一个 Agent 可以把任务交给另一个 Agent
  - **Guardrails**：护栏，输入/输出校验，防止 Agent 跑偏
  - **Tracing**：全链路追踪，调试利器
- **理解"Agent 到底应该怎么设计"的最佳起点**

---

## 第二阶段：深入架构设计（2-4 周）

### 3. LangGraph

- GitHub: `langchain-ai/langgraph` | 38K+ stars | Python | MIT
- 把 Agent 抽象为**有向图**：节点 = 函数/Agent，边 = 条件跳转
- **Agent Loop 和状态管理的最佳学习项目**
- 核心概念：
  - **State**：共享状态，所有节点读写同一份数据
  - **Node**：执行逻辑的函数
  - **Edge**：条件跳转，决定下一步走哪个节点
  - **Checkpoint**：状态持久化，支持暂停/恢复
- 文档质量极高，有完整教程和概念解释
- **大多数复杂 Agent 本质上就是"循环+条件分支"的状态机，LangGraph 把这个抽象做到了极致**

### 4. Pydantic AI

- GitHub: `pydantic/pydantic-ai` | 18K+ stars | Python | MIT
- **类型安全 + 依赖注入**，工程化程度最高
- 核心概念：
  - **类型安全的 Agent 定义**：用 Python 类型注解约束输入输出
  - **依赖注入**：Agent 的依赖（数据库、API 客户端）通过参数注入，方便测试
  - **结构化输出**：用 Pydantic Model 定义输出格式，自动校验
- **适合有后端经验的开发者学习"怎么把 Agent 写得可测试、可维护"**

---

## 第三阶段：多 Agent 系统（2-3 周）

### 5. CrewAI

- GitHub: `crewAIInc/crewAI` | 56K+ stars | Python | MIT
- 角色扮演式多 Agent，**10 行代码就能跑起来**
- 核心抽象：
  - **Agent**：角色定义（Role + Goal + Backstory）
  - **Task**：任务定义（描述 + 预期输出 + 指定 Agent）
  - **Crew**：团队编排（哪些 Agent + 哪些 Task + 执行流程）
  - **Process**：流程模式（Sequential 顺序 / Hierarchical 层级）
- **多 Agent 系统设计模式的最佳范例**，代码可读性极强

### 6. AutoGen（微软）

- GitHub: `microsoft/autogen` | 60K+ stars | Python | CC-BY-4.0
- 对话式多 Agent 协作，与 CrewAI 的角色式形成对比
- 核心概念：
  - **ConversableAgent**：可对话的 Agent，能收发消息
  - **GroupChat**：多 Agent 群聊，自动选择下一个发言者
  - **Human-in-the-loop**：人参与决策
- 注意：v0.4 做了重大架构重构，建议学新版本
- **多 Agent 对话协作的最佳学习项目**

---

## 第四阶段：专项领域（按需选择）

### 7. Browser Use

- GitHub: `browser-use/browser-use` | 106K+ stars | Python | MIT
- 让 AI Agent 操控浏览器，自动化完成网页任务
- **学习 Agent 与外部环境交互**的经典模式：观察→思考→行动
- 展示了视觉+DOM 双模态感知

### 8. Google ADK（Agent Development Kit）

- GitHub: `google/adk-python` | 20K+ stars | Python | Apache-2.0
- Google 对 Agent 架构的官方理解，与 OpenAI Agents SDK 形成对比
- 特色：**评估框架（Evaluation）**，其他项目少有的亮点
- 适合学习如何系统化测试 Agent

### 9. Vercel AI SDK

- GitHub: `vercel/ai` | 25K+ stars | TypeScript | Apache-2.0
- TypeScript/前端开发者的首选
- 展示了如何在 Web 应用中集成 Agent，流式交互实现优雅

### 10. Pi Agent Harness

- GitHub: `earendil-works/pi` | 77K+ stars | TypeScript | MIT
- 官网：https://pi.dev
- 核心维护者：badlogic（Mario Zechner，libGDX 作者）
- **Monorepo 分层架构**，每层包可独立使用：
  - `pi-ai`：统一多供应商 LLM API（OpenAI/Anthropic/Google/AWS Bedrock/Mistral），自动模型发现
  - `pi-agent-core`：Agent 运行时 + 工具调用 + 状态管理
  - `pi-coding-agent`：编码 Agent CLI（read/bash/edit/write）
  - `pi-tui`：自研终端 UI，差分渲染
  - `pi-evals`：评估
- **特色**：
  - 分层解耦，每层包可独立使用，不像 Claude Code 绑定单一供应商
  - 供应链安全投入极高（依赖锁定、审计、shrinkwrap）
  - 自研 TUI 差分渲染，性能优于简单终端输出
  - 会话数据可分享到 HuggingFace，推动 Agent 研究
- **适合学什么**：
  - 统一 LLM API 的抽象设计（Provider 层）
  - Monorepo 分层架构
  - 工程化最佳实践（供应链安全、依赖管理）
- **注意事项**：
  - TypeScript 编写，Python 开发者有阅读门槛
  - 项目较新（2025-08 创建），文档社区还在成长
  - 新贡献者门槛高（新人 PR 默认自动关闭）


---

## 对比表

| 项目 | 代码复杂度 | 多 Agent | 状态管理 | 工程化程度 | 文档质量 | 最适合学什么 |
|------|-----------|---------|---------|-----------|---------|-------------|
| Smolagents | 低 | 否 | 简单 | 中 | 高 | Agent 基本原理 |
| OpenAI Agents SDK | 低 | 是(Handoff) | 简单 | 高 | 高 | 正统架构设计 |
| LangGraph | 中 | 是(图) | 强 | 高 | 极高 | 状态管理+复杂工作流 |
| Pydantic AI | 中 | 否 | 简单 | 极高 | 高 | 工程化实践 |
| CrewAI | 中 | 是(角色) | 中 | 中 | 高 | 多 Agent 协作 |
| AutoGen | 中高 | 是(对话) | 中 | 中 | 中 | 对话式多 Agent |
| Browser Use | 中 | 否 | 中 | 中 | 高 | Agent 与环境交互 |
| Google ADK | 中 | 是 | 中 | 高 | 高 | Agent 评估与测试 |
| Vercel AI SDK | 中 | 否 | 简单 | 高 | 高 | Web 端 Agent 集成 |
| Pi Agent Harness | 中高 | 否 | 中 | 极高 | 中 | 统一 LLM API + 分层架构 |

---

## 学习建议

结合你现在的阶段（有 CLI Agent 项目基础、在学核心概念）：

1. **先读 Smolagents 源码**（2-3 天）——体量接近你的项目，直接对比架构找改进点
2. **再看 OpenAI Agents SDK**（2-3 天）——理解官方设计思路，特别是 Handoff 机制
3. **然后看 LangGraph**（1 周）——理解状态图抽象，这是复杂 Agent 的核心
4. **再看 Pi 的架构设计**（3-5 天）——重点看 `pi-ai` 和 `pi-agent-core` 的接口定义，不用读全部源码，学它的统一 LLM API 抽象和分层解耦思路
5. **最后按兴趣选**：多 Agent 看 CrewAI，工程化看 Pydantic AI，浏览器看 Browser Use
