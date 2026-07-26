# API 必学字段

> Agent 开发必须掌握的 6 个核心字段，每个配实际例子。

---

## 目录

1. [messages](#1-messages)
2. [tools](#2-tools)
3. [tool_choice](#3-tool_choice)
4. [stream](#4-stream)
5. [max_tokens](#5-max_tokens)
6. [temperature](#6-temperature)

---

## 1. messages

### 是什么

对话历史，按顺序排列。模型根据整个历史决定下一步说什么/做什么。

### 四种角色

| 角色 | 谁发的 | 作用 |
|------|--------|------|
| `system` | 开发者 | 告诉模型"你是谁、能干什么、规则是什么" |
| `user` | 用户 | 用户的输入/问题/指令 |
| `assistant` | 模型 | 模型的回复，可能包含文本或工具调用 |
| `tool` | 系统 | 工具执行后的结果，回灌给模型 |

> **注意**：`system`/`user`/`assistant`/`tool` 是 OpenAI 官方定义的四种标准角色。Anthropic 定义了三种（`user`/`assistant`，`system` 单独放在顶层字段）。Agent 框架和应用层可以自定义角色（如 `summary`），但发给模型前必须映射成标准角色。
>
> **角色由谁定**：
> - **模型厂商**（OpenAI、Anthropic）定义标准角色
> - **Agent 框架**（如你的项目）可以自定义角色，发送前映射
> - **应用层**可以扩展语义角色（如 `developer`、`expert`），最终都映射回标准角色

### 各厂商角色支持情况

| 角色 | OpenAI | Anthropic | DeepSeek | Gemini | 小模型/开源 |
|------|--------|-----------|----------|--------|------------|
| `system` | ✅ 在 messages 里 | ✅ 顶层字段 | ✅ 在 messages 里 | ✅ 在 messages 里 | ⚠️ 部分不支持或效果差 |
| `user` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `assistant` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `tool` | ✅ | ❌ 用 `user`+`tool_result` 块 | ✅ | ✅ | ❌ 部分不支持，需 ReAct 模式 |

**关键差异**：
- `user` 和 `assistant` 是所有模型都支持的，这是最基础的
- `system`：Anthropic 不放在 messages 里，单独传顶层字段
- `tool`：Anthropic 没有 `role: "tool"`，而是 `role: "user"` 里包 `tool_result` 块
- 小模型可能只认 `user`/`assistant`，不支持 `system` 或 `tool`，需要降级到 ReAct 模式（prompt 里写工具定义，模型输出 JSON 文本，自己解析）

**OpenAI 格式是事实标准**：Qwen、Llama、GLM 等开源模型都走 OpenAI 兼容格式，所以四种角色都支持。

### OpenAI 格式

```json
{
  "messages": [
    {"role": "system", "content": "你是一个代码助手，可以帮助用户读写文件。"},
    {"role": "user", "content": "帮我看看 main.py"},
    {"role": "assistant", "content": null, "tool_calls": [
      {"id": "call_1", "type": "function", "function": {"name": "Read", "arguments": "{\"path\": \"main.py\"}"}}
    ]},
    {"role": "tool", "tool_call_id": "call_1", "content": "import os\n..."},
    {"role": "assistant", "content": "main.py 是一个入口文件..."}
  ]
}
```

### Anthropic 格式

```json
{
  "system": "你是一个代码助手...",
  "messages": [
    {"role": "user", "content": "帮我看看 main.py"},
    {"role": "assistant", "content": [
      {"type": "text", "text": "我来查看一下"},
      {"type": "tool_use", "id": "toolu_1", "name": "Read", "input": {"path": "main.py"}}
    ]},
    {"role": "user", "content": [
      {"type": "tool_result", "tool_use_id": "toolu_1", "content": "import os\n..."}
    ]},
    {"role": "assistant", "content": "main.py 是一个入口文件..."}
  ]
}
```

### 关键差异

| | OpenAI | Anthropic |
|--|--------|-----------|
| system 位置 | `messages[0]` | 顶层 `system` 字段 |
| 工具调用 | `tool_calls` 数组 | `content` 里的 `tool_use` 块 |
| 工具结果 | `role: "tool"` | `role: "user"` + `tool_result` 块 |
| 参数格式 | JSON 字符串 | JSON 对象 |

### 对 Agent 的影响

- **顺序很重要**：模型只能看到它前面的消息，新消息追加到末尾
- **tool 消息必须对应**：每个 `tool_call` 必须有对应的 `tool`/`tool_result` 消息
- **上下文膨胀**：messages 越长，token 越多，成本越高，效果越差（见 Q3）
- **自定义角色要映射**：框架自定义的 `summary` 等角色，发送给 Provider 前必须映射成 `assistant` 等标准角色

### 常见问题

**Q: 只有这四种角色吗？**

A: OpenAI 官方四种，Anthropic 三种。但框架可以自定义（如 `summary`），发送前映射成标准角色即可。

**Q: 可以自定义角色吗？**

A: 可以。自定义角色是语义标记，帮助框架理解消息意图。但最终发给模型时必须映射回标准角色。例如你的项目里的 `summary` 角色，发送前映射成 `assistant`。

**Q: 为什么需要 summary 角色？**

A: 上下文压缩时，把早期对话摘要成一条消息。用 `summary` 标记，让框架知道"这是历史摘要，不是新内容"，便于后续管理和调试。

---

## 2. tools

### 是什么

告诉模型"你现在有哪些工具可以用"。模型根据工具描述决定要不要调用。

### OpenAI 格式

```json
{
  "tools": [
    {
      "type": "function",
      "function": {
        "name": "Read",
        "description": "读取文件内容，支持指定行号范围",
        "parameters": {
          "type": "object",
          "properties": {
            "path": {"type": "string", "description": "文件路径"},
            "offset": {"type": "integer", "description": "起始行号"},
            "limit": {"type": "integer", "description": "读取行数"}
          },
          "required": ["path"]
        }
      }
    },
    {
      "type": "function",
      "function": {
        "name": "Write",
        "description": "写入文件内容，会覆盖原有内容",
        "parameters": {
          "type": "object",
          "properties": {
            "path": {"type": "string"},
            "content": {"type": "string"}
          },
          "required": ["path", "content"]
        }
      }
    }
  ]
}
```

### Anthropic 格式

```json
{
  "tools": [
    {
      "name": "Read",
      "description": "读取文件内容...",
      "input_schema": {
        "type": "object",
        "properties": {
          "path": {"type": "string", "description": "文件路径"},
          "offset": {"type": "integer"},
          "limit": {"type": "integer"}
        },
        "required": ["path"]
      }
    }
  ]
}
```

### 关键差异

| | OpenAI | Anthropic |
|--|--------|-----------|
| 包装 | `{"type": "function", "function": {...}}` | 直接 `{name, description, input_schema}` |
| 参数字段 | `parameters` | `input_schema` |
| 参数类型 | JSON Schema | JSON Schema（相同） |

### 对 Agent 的影响

- **description 是 prompt**：模型根据 description 决定调哪个工具，写清楚很重要
- **schema 必须合法**：参数类型、required 字段，模型会严格按 schema 填参数
- **工具越多越慢**：每轮请求都要传全部工具定义，占 token

---

## 3. tool_choice

### 是什么

控制模型是否必须调工具、自动决定、还是禁止调工具。

### 取值

| 值 | 含义 | 场景 |
|----|------|------|
| `"auto"` | 模型自己决定调不调 | 默认，大多数情况 |
| `"none"` | 禁止调工具 | 纯聊天，不需要工具 |
| `"required"` | 必须调至少一个工具 | 强制模型行动 |
| `{"type": "function", "function": {"name": "Read"}}` | 强制调指定工具 | 已知该调什么 |

### 例子

```json
// 默认，模型自己决定
{"tool_choice": "auto"}

// 纯聊天模式
{"tool_choice": "none"}

// 强制模型必须做点什么
{"tool_choice": "required"}

// 强制查天气
{"tool_choice": {"type": "function", "function": {"name": "get_weather"}}}
```

### 对 Agent 的影响

- **Agent 默认用 `"auto"`**：让模型自己判断要不要调工具
- **遇到模型"偷懒"时**：切到 `"required"` 强制它行动
- **不需要工具的场景**：如总结对话、生成文本，用 `"none"` 省 token

---

## 4. stream

### 是什么

控制响应是**一次性返回**还是**流式逐字返回**。

### 对比

| | `stream: false` | `stream: true` |
|--|-----------------|----------------|
| 用户体验 | 等全部生成完才显示 | 逐字显示，像打字 |
| 响应格式 | 完整 JSON | SSE 流，每行一个 chunk |
| 中断 | 不能中断 | 可以中断（Ctrl+C） |
| 成本 | 相同 | 相同 |

### 流式响应示例

```
data: {"choices": [{"delta": {"content": "我"}}]}
data: {"choices": [{"delta": {"content": "来"}}]}
data: {"choices": [{"delta": {"content": "查"}}]}
data: {"choices": [{"delta": {"content": "看"}}]}
data: [DONE]
```

### 对 Agent 的影响

- **Agent 必须用流式**：用户需要实时看到模型在思考、在调工具
- **你的项目已经用了**：`agent_loop.py` 里 `provider.chat()` 返回的是 `Iterator[ProviderEvent]`
- **流式时不能边收边解析 JSON**：工具调用要等收完再解析

---

## 5. max_tokens

### 是什么

控制模型**单次最多输出多少 token**。

### 注意

- 是**输出上限**，不是输入上限
- 超过会被截断，输出不完整
- 不同模型上限不同（Claude Sonnet 8192，GPT-4o 16384）

### 例子

```json
// 限制短回复
{"max_tokens": 256}

// 允许长回复
{"max_tokens": 4096}

// 生成代码，需要更长
{"max_tokens": 8192}
```

### 对 Agent 的影响

- **工具调用结果太长**：如果 `max_tokens` 太小，模型可能截断工具结果的分析
- **代码生成**：写长文件时需要大的 `max_tokens`
- **摘要任务**：可以设小一点，强制简洁

---

## 6. temperature

### 是什么

控制模型输出的**随机性/创造性**。

| 值 | 效果 | 场景 |
|----|------|------|
| 0.0 | 最确定，几乎固定 | 代码生成、结构化输出 |
| 0.3-0.5 | 有点灵活 | 一般对话 |
| 0.7-1.0 | 很随机 | 创意写作、头脑风暴 |
| >1.0 | 很发散 | 少见，容易失控 |

### 例子

```json
// 代码生成：稳定、可复现
{"temperature": 0.1}

// 一般对话：自然流畅
{"temperature": 0.7}

// 创意写作：多样化
{"temperature": 1.0}
```

### 对 Agent 的影响

- **Agent 通常设低（0.1-0.3）**：工具调用需要稳定、可预测，不能今天调 Read 明天调 Write
- **你的项目已经用了**：`config.json` 里可以配，默认建议 0.3
- **创意任务可以调高**：如生成文案、起变量名

---

## 快速对照表

| 字段 | OpenAI | Anthropic | Agent 常用值 |
|------|--------|-----------|-------------|
| messages | `role/content/tool_calls` | `role/content/tool_use/tool_result` | 四种角色循环 |
| tools | `function.parameters` | `input_schema` | 7-10 个内置工具 |
| tool_choice | `auto/none/required` | `auto/none/any/tool` | `auto` |
| stream | `true/false` | `true/false` | `true` |
| max_tokens | 整数 | 整数 | 4096-8192 |
| temperature | 0.0-2.0 | 0.0-1.0 | 0.1-0.3 |
