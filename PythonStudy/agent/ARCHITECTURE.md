# AI Agent CLI 使用方法与实现原理

> Claude Code 风格的终端 CLI 编码助手。基于 Python，支持原生 function calling、多模型、权限分级、技能系统、子 agent。

---

## 目录

- [一、快速开始](#一快速开始)
- [二、使用方法](#二使用方法)
- [三、项目结构](#三项目结构)
- [四、模块说明](#四模块说明)
- [五、实现原理](#五实现原理)
- [六、配置文件说明](#六配置文件说明)
- [七、扩展指南](#七扩展指南)

---

## 一、快速开始

### 1.1 环境要求

- Python 3.10+
- Windows / macOS / Linux
- 网络连接（调用模型 API）

### 1.2 安装依赖

```bash
cd D:/AiGenerateProject/Agent
pip install -r requirements.txt
```

依赖清单：

```
openai>=1.0.0       # OpenAI 兼容 Provider
anthropic>=0.40.0   # Anthropic 原生 Provider
mcp>=1.0.0          # MCP 协议（保留，后续接入）
rich>=13.0.0        # 终端 Markdown 渲染
prompt_toolkit>=3.0 # 终端输入增强（保留）
```

### 1.3 配置 API

编辑 `config/config.json`，填入你的 API 密钥：

```json
{
  "api": {
    "base_url": "https://api-inference.modelscope.cn/v1",
    "api_key": "你的 API 密钥"
  },
  "provider": "openai_compat",
  "models": ["ZhipuAI/GLM-5", "deepseek-ai/DeepSeek-V3.2", "Qwen/Qwen3-Coder-480B-A35B-Instruct"]
}
```

> ModelScope Token 获取地址：https://modelscope.cn/my/myaccesstoken

### 1.4 启动

```bash
python agent_main.py
```

首次启动会自动生成 `config/settings.json`（权限规则）和 `config/CLAUDE.md`（项目指令）。

---

## 二、使用方法

### 2.1 交互界面

```
╭─ AI Agent ─╮
│  Claude Code 风格 CLI 编码助手
│  模型: ZhipuAI/GLM-5
│  工作目录: D:/AiGenerateProject/Agent
╰─────────────╯

输入 exit 退出 | /skill-name 触发技能 | Ctrl+C 中断当前操作

👤 你: 帮我看看 agent_main.py 有什么问题
🤖 助手: 我先读一下这个文件
  ⚡ Read (read_only)  path=agent_main.py
  ✓ 完成 (0.02s, 5000 字符)
  ...
```

### 2.2 内置命令

| 命令 | 作用 |
|------|------|
| `exit` / `quit` | 保存对话并退出 |
| `clear` | 清除对话记忆 |
| `model` | 查看可用模型列表 |
| `model <编号>` | 切换模型，如 `model 1` |
| `skills` | 列出所有技能 |
| `skills reload` | 热重载技能文件 |
| `help` | 显示帮助 |

### 2.3 技能触发

技能有两种触发方式：

**手动触发**：输入 `/技能名` + 内容

```
👤 你: /review def login(user, pwd): ...
🤖 助手: [代码审查报告]
  ...
```

**自动匹配**：直接描述需求，模型根据技能描述自动判断是否激活

```
👤 你: 帮我审查一下这段代码
（模型识别意图后自动激活 review 技能）
```

### 2.4 工具使用

agent 内置 7 个工具，模型会自主调用，你只需描述任务：

| 工具 | 用途 | 示例任务 |
|------|------|---------|
| Read | 读文件 | "看一下 main.py" |
| Write | 写文件（覆盖） | "创建一个 utils.py" |
| Edit | 精确替换 | "把变量 foo 改成 bar" |
| Grep | 搜索内容 | "找出所有调用 login 的地方" |
| Glob | 匹配文件名 | "列出所有 .py 文件" |
| Bash | 执行命令 | "跑一下测试" |
| Task | 派生子 agent | "用子 agent 审查这个 PR" |

### 2.5 权限确认

对 `write`/`destructive` 级别的操作（写文件、执行命令），会弹窗确认：

```
⚠️ 权限确认 Write (write)
  原因: 命中 ask 规则
  路径: /path/to/file.py

  允许执行？[y]本次 / [Y]始终允许 / [n]拒绝: Y
```

- `y` - 本次允许
- `Y` - 始终允许（自动写入 `settings.json` 的 allow 列表，下次不再询问）
- `n` - 拒绝

### 2.6 中断操作

按 `Ctrl+C` 中断当前操作（模型请求或工具执行），但**不退出程序**。中断后可继续输入新指令。

### 2.7 子 agent（Task 工具）

通过 Task 工具派生独立上下文的子 agent。模型会自主决定何时使用，你只需要在描述中提到"用子 agent"、"独立审查"等。

子 agent 类型：

| 类型 | 工具集 | 适用场景 |
|------|--------|---------|
| general | 全工具 | 通用子任务 |
| reviewer | 只读（Read/Grep/Glob） | 代码审查 |
| explorer | 只读 + Bash | 代码调研 |

---

## 三、项目结构

```
Agent/
├── agent_main.py              # 入口
├── requirements.txt           # 依赖
├── config/                    # 配置目录
│   ├── config.json            # API、模型、参数
│   ├── settings.json          # 权限规则
│   ├── mcp.json               # MCP Server 配置
│   ├── chat_memory.json       # 对话记忆
│   └── CLAUDE.md              # 项目级指令
├── core/                      # 核心逻辑
│   ├── agent_loop.py          # 主循环
│   ├── context_manager.py     # 上下文压缩
│   └── message.py             # 消息结构
├── tools/                     # 内置工具
│   ├── base.py                # BaseTool 抽象类
│   ├── read.py / write.py / edit.py
│   ├── grep.py / glob.py / bash.py
│   └── task.py                # 子 agent 工具
├── providers/                 # 模型 Provider
│   ├── base.py                # BaseProvider 抽象类
│   ├── openai_compat.py       # OpenAI 兼容
│   └── anthropic.py           # Anthropic 原生
├── permissions/               # 权限引擎
│   └── engine.py
├── skills/                    # 技能系统
│   ├── loader.py              # 技能加载器
│   ├── code_review.md         # 代码审查
│   ├── summarize.md           # 摘要
│   └── translate.md           # 翻译
├── ui/                        # 终端 UI
│   ├── cli.py                 # 输入输出、Markdown
│   └── tool_display.py        # 工具调用展示
└── mcp_servers/               # MCP 服务（保留）
    └── jira/
```

---

## 四、模块说明

### 4.1 `agent_main.py` - 入口

负责：
- 加载所有配置文件
- 初始化各组件（Provider、权限引擎、技能加载器、工具集、UI）
- 拼接 system prompt（基础提示 + CLAUDE.md + 工具说明）
- 启动主循环

### 4.2 `core/` - 核心逻辑

#### `message.py` - 消息结构

统一消息格式，兼容 OpenAI 和 Anthropic：

```python
@dataclass
class Message:
    role: str                              # user / assistant / tool / summary
    content: Optional[str]                 # 文本内容
    tool_calls: Optional[List[ToolCall]]   # assistant 发起的工具调用
    tool_call_id: Optional[str]            # tool 消息对应的 tool_call id
    name: Optional[str]                    # tool 消息对应的工具名
```

提供工厂方法：`Message.user()`、`Message.assistant()`、`Message.tool()`、`Message.summary()`。

#### `agent_loop.py` - 主循环

核心流程：

```
run(user_input)
  ├── 检查技能触发（/skill-name 或自动匹配）
  ├── 加入 user 消息
  ├── 上下文压缩检查（超阈值则压缩）
  ├── 循环调用模型：
  │     ├── provider.chat() 流式返回
  │     ├── 收集 text_delta + tool_calls
  │     ├── 加入 assistant 消息
  │     ├── 若有 tool_calls：逐个执行
  │     │     ├── 权限检查
  │     │     ├── 展示调用过程
  │     │     ├── 执行工具
  │     │     └── 加入 tool 消息
  │     └── 无 tool_calls 则结束本轮
  └── 返回最终文本
```

#### `context_manager.py` - 上下文压缩

- 估算消息 token 数（粗略：字符数/3）
- 超过模型窗口 70% 时触发压缩
- 压缩策略：保留最近 10 轮 + 早期对话调用 Provider 做 summary

### 4.3 `tools/` - 工具层

#### `base.py` - BaseTool 抽象类

```python
class BaseTool(ABC):
    name: str                  # 工具名
    description: str           # 给模型的描述
    parameters: dict           # JSON Schema 参数定义
    risk_level: str            # read_only / write / destructive

    @abstractmethod
    def execute(self, params, context) -> ToolResult: ...

    def to_openai_schema(self): ...    # 转 OpenAI tools 格式
    def to_anthropic_schema(self): ... # 转 Anthropic tools 格式
```

#### 七个内置工具

| 工具 | 文件 | 风险等级 | 实现要点 |
|------|------|---------|---------|
| Read | read.py | read_only | 支持行号、offset/limit，输出带行号 |
| Write | write.py | write | 自动创建父目录，完整覆盖 |
| Edit | edit.py | write | old_string 必须唯一，支持 replace_all |
| Grep | grep.py | read_only | 优先用 ripgrep，无则 Python 回退 |
| Glob | glob.py | read_only | 按修改时间排序，递归匹配 |
| Bash | bash.py | destructive | Windows 用 PowerShell，支持后台运行 |
| Task | task.py | read_only | 派生子 agent，独立上下文 |

### 4.4 `providers/` - Provider 层

#### `base.py` - BaseProvider 抽象类

```python
class BaseProvider(ABC):
    def chat(self, messages, tools, system_prompt) -> Iterator[ProviderEvent]: ...
    def summarize(self, messages) -> str: ...   # 用于上下文压缩
    def list_models(self) -> List[str]: ...
    def get_current_model(self) -> str: ...
```

ProviderEvent 类型：
- `text_delta` - 文本流片段
- `tool_call` - 工具调用（已聚合）
- `done` - 结束
- `error` - 错误

#### `openai_compat.py` - OpenAI 兼容 Provider

- 适用于 ModelScope / DeepSeek / Qwen / GLM 兼容端点 / OpenAI 官方
- 走 `/v1/chat/completions` 流式接口
- tool calling 走标准 `tools` 参数
- **限流自动切换**：某模型返回 429 时标记冷却 60 秒，自动切到下一个模型重试

#### `anthropic.py` - Anthropic 原生 Provider

- 走 `/v1/messages` 接口
- tool_use 走 `tools` 参数，格式与 OpenAI 不同（`input_schema` 而非 `parameters`）
- system 提示单独传 `system=` 字段
- 流式用 `messages.stream()` 上下文

### 4.5 `permissions/` - 权限引擎

#### `engine.py` - PermissionEngine

匹配规则（按顺序）：

1. **危险命令模式**命中 -> 拒绝（不可覆盖）
2. **deny** 命中 -> 拒绝
3. **allow** 命中 -> 放行
4. **ask** 命中 -> 弹窗
5. 默认按 `risk_level`：read_only 放行，write/destructive 弹窗

规则语法：

| 规则 | 含义 |
|------|------|
| `Read` | 匹配工具名 |
| `Bash` | 匹配所有 Bash 调用 |
| `Bash(npm test:*)` | 匹配 Bash 命令前缀，支持 `*` 通配 |
| `Bash(git status)` | 精确匹配命令 |

用户选"始终允许"时，自动生成对应规则写入 `settings.json`。

### 4.6 `skills/` - 技能系统

#### `loader.py` - SkillLoader

技能文件格式（Markdown + frontmatter）：

```markdown
---
name: review
description: 代码审查技能，分析代码质量、安全性和性能
---

（技能 prompt 内容）
```

触发方式：
- **手动**：用户输入 `/review <内容>` -> `match_trigger()` 匹配
- **自动**：每轮对话前，`build_skills_hint()` 把所有技能的 `name + description` 拼成提示注入 system prompt，模型决定激活时在回复首行输出 `[SKILL:xxx]`，`extract_activation()` 解析后注入技能内容

### 4.7 `ui/` - 终端 UI

#### `cli.py` - CLI 类

- `read_input()` - 读取用户输入
- `stream_render()` - 流式渲染模型文本
- `handle_command()` - 处理内置命令（exit/clear/model/skills/help）
- `ask_permission()` - 权限弹窗
- `info/success/warn/error` - 各种日志输出

#### `tool_display.py` - 工具调用展示

- `display_tool_start()` - 展示工具调用开始（工具名、参数、风险等级）
- `display_tool_result()` - 展示工具调用结果（成功/失败、耗时、预览）
- `display_tool_output()` - 折叠展示完整输出

---

## 五、实现原理

### 5.1 整体架构

```
┌────────────────────────────────────────────────┐
│                  agent_main.py                  │
│              （入口、初始化、配置加载）            │
└────────────────────┬───────────────────────────┘
                     │
                     ▼
┌────────────────────────────────────────────────┐
│                   UI 层 (ui/)                   │
│  cli.py ─── 输入输出、命令处理、权限弹窗          │
│  tool_display.py ─── 工具调用实时展示            │
└────────────────────┬───────────────────────────┘
                     │
                     ▼
┌────────────────────────────────────────────────┐
│              核心循环 (core/agent_loop.py)       │
│  ┌──────────────────────────────────────────┐  │
│  │ 1. 技能触发检查                          │  │
│  │ 2. 上下文压缩检查                        │  │
│  │ 3. 调 Provider（带工具定义）             │  │
│  │ 4. 收到 tool_call -> 权限检查 -> 执行    │  │
│  │ 5. 工具结果回灌给模型                    │  │
│  │ 6. 重复 3-5 直到无工具调用               │  │
│  └──────────────────────────────────────────┘  │
└──────┬──────────────┬─────────────┬────────────┘
       │              │             │
       ▼              ▼             ▼
┌──────────┐   ┌──────────┐   ┌──────────┐
│providers/│   │  tools/  │   │permissions│
│ OpenAI   │   │ Read ... │   │ /engine  │
│ Anthropic│   │ Task     │   │  权限引擎 │
└──────────┘   └──────────┘   └──────────┘
       │              │
       ▼              ▼
   模型 API       文件系统/命令
```

### 5.2 Function Calling 工作流程

这是 Claude Code 风格 agent 的核心机制。模型本身不执行任何操作，只决定"调用什么工具 + 传什么参数"，真正干活的是 CLI。

**完整流程**（以"帮我看看 main.py 有什么问题"为例）：

```
1. 用户输入 -> Message.user("帮我看看 main.py 有什么问题")

2. AgentLoop 把消息 + 工具定义 + system prompt 发给 Provider

3. Provider 调用模型 API：
   POST /v1/chat/completions
   {
     "model": "ZhipuAI/GLM-5",
     "messages": [...],
     "tools": [
       {"type": "function", "function": {
         "name": "Read",
         "description": "读取文件内容...",
         "parameters": {...}
       }},
       ...
     ],
     "stream": true
   }

4. 模型流式返回：
   - text_delta: "我先读一下这个文件"
   - tool_call: {id: "call_1", name: "Read", arguments: {path: "main.py"}}

5. AgentLoop 收到 tool_call：
   a. 权限检查：Read 是 read_only，allow 放行
   b. 展示：⚡ Read (read_only) path=main.py
   c. 执行：ReadTool.execute({"path": "main.py"}, context)
      -> 调用 open("main.py").read()
      -> 返回 ToolResult(success=True, output="文件内容...")
   d. 展示：✓ 完成 (0.02s, 5000 字符)
   e. 加入 tool 消息：Message.tool(tool_call_id="call_1", name="Read", content="文件内容...")

6. AgentLoop 再次调 Provider，带上下文（含工具结果）

7. 模型分析文件内容后，可能：
   - 继续调工具（如 Grep 搜索某个函数）
   - 直接返回最终分析文本

8. 无工具调用时，本轮结束，渲染最终回复
```

### 5.3 多模型限流切换

```python
# providers/openai_compat.py
def chat(self, messages, tools, system_prompt):
    tried = set()
    for attempt in range(len(self.models)):
        m = self._pick_model()  # 跳过冷却中的模型
        if m in tried:
            time.sleep(1)
        tried.add(m)
        try:
            yield from self._chat_once(m, ...)
            self.current_model_index = self.models.index(m)  # 成功则更新
            return
        except RateLimitError:
            self._mark_rate_limited(m, cool_seconds=60)  # 标记冷却
            continue
        except BadRequestError as e:
            yield ProviderEvent(type="error", error=str(e))
            return
    yield ProviderEvent(type="error", error="所有模型均不可用")
```

### 5.4 权限引擎匹配算法

```python
def check(self, tool_name, params, risk_level) -> PermissionDecision:
    # 1. 危险命令模式（最高优先级，不可覆盖）
    if tool_name == "Bash":
        for pattern in self.dangerous_patterns:
            if pattern in params["command"]:
                return PermissionDecision("deny", f"命中危险命令模式: {pattern}")

    # 2. deny 规则
    if self._match_rules(tool_name, params, self.permissions["deny"]):
        return PermissionDecision("deny", "命中 deny 规则")

    # 3. allow 规则
    if self._match_rules(tool_name, params, self.permissions["allow"]):
        return PermissionDecision("allow", "命中 allow 规则")

    # 4. ask 规则
    if self._match_rules(tool_name, params, self.permissions["ask"]):
        return PermissionDecision("ask", "命中 ask 规则")

    # 5. 默认按风险等级
    if risk_level == "read_only":
        return PermissionDecision("allow", "read_only 默认放行")
    return PermissionDecision("ask", f"默认 {risk_level} 操作需确认")

def _match_one(self, tool_name, params, rule):
    # "Bash(npm test:*)" 格式：工具名 + 参数过滤
    if "(" in rule and rule.endswith(")"):
        name_part, arg_part = rule.split("(", 1)
        arg_part = arg_part[:-1]
        if name_part != tool_name:
            return False
        if tool_name == "Bash":
            return fnmatch.fnmatch(params["command"], arg_part)
        return True
    # 纯工具名："Read"
    return rule == tool_name
```

### 5.5 上下文压缩

```python
class ContextManager:
    def compress(self, messages):
        # 1. 切分：早期 + 最近 N 轮
        split_idx = self._find_split_index(messages, keep_recent_rounds=10)
        early, recent = messages[:split_idx], messages[split_idx:]

        # 2. 调 Provider 对早期对话做摘要
        summary_text = self.provider.summarize(early)
        summary_msg = Message.summary(summary_text)

        # 3. 用摘要替换早期消息
        return [summary_msg] + recent

    def should_compress(self, messages):
        # 超过模型窗口 70% 触发
        return self.estimate_tokens(messages) > self.max_tokens * 0.7

    def estimate_tokens(self, messages):
        # 粗略估算：字符数 / 3
        total = 0
        for msg in messages:
            if msg.content:
                total += len(msg.content) // 3 + 10
        return total
```

### 5.6 子 agent（Task 工具）

```python
class TaskTool(BaseTool):
    SUBAGENT_TOOLS = {
        "general":   ["Read", "Write", "Edit", "Grep", "Glob", "Bash"],
        "reviewer":  ["Read", "Grep", "Glob"],                    # 只读
        "explorer":  ["Read", "Grep", "Glob", "Bash"],
    }

    def execute(self, params, context):
        # 1. 创建独立 AgentLoop
        sub_loop = AgentLoop(
            provider=context.provider,
            permission_engine=context.permission_engine,
            skill_loader=None,                    # 子 agent 不用技能
            tools_filter=self.SUBAGENT_TOOLS[params["subagent_type"]],
            is_subagent=True,                     # 静默模式（不渲染 UI）
        )

        # 2. 用独立 system prompt 跑一轮
        final_text = sub_loop.run_subagent(
            prompt=params["prompt"],
            system_prompt=self.SUBAGENT_PROMPTS[params["subagent_type"]],
        )

        # 3. 返回最终文本给父 agent
        return ToolResult(success=True, output=final_text)
```

**关键点**：
- 子 agent 有独立的消息列表（不共享父 agent 历史）
- 子 agent 受 `tools_filter` 限制，只能用白名单工具
- 子 agent 静默执行（`is_subagent=True`，不渲染到 UI）
- 子 agent 完成后，最终文本作为工具结果返回给父 agent

### 5.7 技能自动匹配

```python
# 每轮对话前
def _check_skill_trigger(self, user_input):
    # 1. 手动触发
    skill = self.skill_loader.match_trigger(user_input)
    if skill:
        return f"[激活技能: {skill.name}]\n{skill.content}"

    # 2. 自动匹配提示（拼到 system prompt）
    hint = self.skill_loader.build_skills_hint()
    return hint  # 形如：
    # [可用技能] 以下技能可被激活...
    # - review: 代码审查技能...
    # - summary: 内容摘要技能...
    # 若匹配，请在回复首行输出 [SKILL:技能名]

# 模型回复后
def extract_activation(self, text):
    # 解析 [SKILL:xxx] 标记
    m = re.search(r"\[SKILL:(\S+?)\]", text)
    return m.group(1) if m else None
```

### 5.8 Provider 适配层

两个 Provider 把统一的 `Message` 格式转换为各自的 API 格式：

| 统一格式 | OpenAI 格式 | Anthropic 格式 |
|---------|------------|----------------|
| `Message.user(content)` | `{role:"user", content}` | `{role:"user", content}` |
| `Message.assistant(content, tool_calls)` | `{role:"assistant", content, tool_calls:[{id,type:"function",function:{name,arguments}}]}` | `{role:"assistant", content:[{type:"text",text}, {type:"tool_use",id,name,input}]}` |
| `Message.tool(tool_call_id, name, content)` | `{role:"tool", tool_call_id, content}` | `{role:"user", content:[{type:"tool_result",tool_use_id,content}]}` |
| system_prompt | `messages[0]={role:"system",content}` | `system=content` 参数 |
| tools | `tools=[{type:"function",function:{name,description,parameters}}]` | `tools=[{name,description,input_schema}]` |

---

## 六、配置文件说明

### 6.1 `config/config.json` - 主配置

```json
{
  "api": {
    "base_url": "https://api-inference.modelscope.cn/v1",
    "api_key": "ms-xxxxxxxxxxxx"
  },
  "provider": "openai_compat",      // openai_compat / anthropic
  "models": ["ZhipuAI/GLM-5", "deepseek-ai/DeepSeek-V3.2", "Qwen/Qwen3-Coder-480B-A35B-Instruct"],
  "default_model_index": 0,
  "max_rounds": 30,                 // 单次对话最大工具调用轮数
  "command_timeout": 60,
  "system_prompt": "你是一个乐于助人的 AI 助手...",
  "system_os": "Windows (PowerShell)",
  "skills_dir": "skills",
  "anthropic_api_key": "",          // Anthropic Provider 时使用
  "anthropic_models": ["claude-sonnet-4-6", "claude-haiku-4-5-20251001"]
}
```

### 6.2 `config/settings.json` - 权限规则

```json
{
  "permissions": {
    "allow": ["Read", "Grep", "Glob", "Bash(git status)"],
    "deny":  ["Bash(rm -rf:*)"],
    "ask":   ["Write", "Edit", "Bash"]
  },
  "dangerous_patterns": ["rm -rf", "format ", "del /s", "del /q", "rmdir /s", ...]
}
```

- `allow` - 直接放行
- `deny` - 直接拒绝（不可覆盖）
- `ask` - 弹窗确认
- `dangerous_patterns` - 危险命令黑名单（最高优先级）

用户选"始终允许"时，自动追加到 `allow` 列表。

### 6.3 `config/CLAUDE.md` - 项目级指令

Markdown 格式，会作为 system prompt 的一部分注入给模型。用于告诉模型：
- 项目概述
- 编码规范
- 工具使用约定
- 任何你希望模型记住的项目特定信息

### 6.4 `config/mcp.json` - MCP 配置

保留原有格式，后续会接入 MCP 工具系统。当前版本未启用 MCP 工具调用。

### 6.5 `config/chat_memory.json` - 对话记忆

自动保存/加载。每次对话结束自动写入，启动时自动加载。

> **注意**：旧版本的 `chat_memory.json` 是自定义协议（` ```exec ` 等）的历史，新 agent 加载后可能不理解。建议第一次运行新版本前删除该文件。

---

## 七、扩展指南

### 7.1 添加新工具

1. 在 `tools/` 下创建新文件，如 `tools/email.py`：

```python
from tools.base import BaseTool, ToolContext, ToolResult

class EmailTool(BaseTool):
    name = "SendEmail"
    description = "发送邮件"
    parameters = {
        "type": "object",
        "properties": {
            "to": {"type": "string", "description": "收件人邮箱"},
            "subject": {"type": "string"},
            "body": {"type": "string"},
        },
        "required": ["to", "subject", "body"],
    }
    risk_level = "write"

    def execute(self, params, context):
        # 实现发送逻辑
        return ToolResult(success=True, output="已发送")
```

2. 在 `agent_main.py` 的 `build_tools()` 注册：

```python
def build_tools() -> dict:
    return {
        "Read": ReadTool(),
        ...
        "SendEmail": EmailTool(),  # 新增
    }
```

### 7.2 添加新技能

在 `skills/` 下创建 `.md` 文件：

```markdown
---
name: refactor
description: 代码重构技能，改善代码结构而不改变行为
---

你是一个代码重构专家。请遵循以下原则：
1. 保持行为不变
2. 小步重构
3. ...
```

输入 `skills reload` 热加载，无需重启。

### 7.3 添加新 Provider

1. 在 `providers/` 下创建新文件，继承 `BaseProvider`
2. 实现四个方法：`chat`、`summarize`、`list_models`、`get_current_model`
3. 在 `agent_main.py` 的 `build_provider()` 添加分支

### 7.4 调整权限规则

编辑 `config/settings.json`，添加/删除 allow/deny/ask 规则。常见模式：

```json
{
  "permissions": {
    "allow": [
      "Read", "Grep", "Glob",
      "Bash(npm test:*)",
      "Bash(git status)",
      "Bash(git diff:*)",
      "Bash(git log:*)"
    ],
    "deny": [
      "Bash(rm -rf:*)",
      "Bash(git push --force:*)"
    ],
    "ask": ["Write", "Edit", "Bash"]
  }
}
```

---

## 附：与 Claude Code 的对比

| 特性 | Claude Code | 本项目 |
|------|------------|--------|
| 形态 | 终端 CLI | 终端 CLI |
| 协议 | 原生 function calling | 原生 function calling |
| 模型 | Claude 系列 | OpenAI 兼容 + Anthropic |
| 内置工具 | Read/Write/Edit/Grep/Glob/Bash/Task + 更多 | 同左 7 个 |
| 子 agent | 完整版（并行/取消/流式） | 中等版（独立上下文 + 工具子集） |
| 权限 | 细粒度规则 + 始终允许 | 同左 |
| 技能 | name/description 自动匹配 | 同左 |
| 上下文压缩 | 智能压缩 | 同左 |
| Hooks | 支持 | 不支持 |
| 命令历史持久化 | 支持 | 不支持 |
| 多会话管理 | 支持 | 不支持 |
