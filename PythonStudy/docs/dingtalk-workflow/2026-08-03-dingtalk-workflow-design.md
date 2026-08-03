# 钉钉消息驱动工作流 — 需求设计文档

> 状态：草稿，持续迭代中
> 创建日期：2026-08-03

## 1. 背景与目标

业务人员在钉钉群里通过 @助手 发送自然语言消息，系统自动识别意图，经两轮确认后触发对应工作流执行。

**核心目标**：
- 业务用自然语言沟通，无需记忆固定指令格式
- 两轮确认确保理解正确、操作明确
- 场景可扩展，新增业务场景只需注册配置

**核心原则**：
- 业务只负责提出问题，不关心后面流程怎么走
- dws 只负责和用户交互（监听消息、发确认、收回复）
- 小模型只负责意图识别，不做 function calling / MCP / 工作流执行

## 2. 整体流程

```
业务 @助手 → 监听 @消息
    ↓
[意图识别] Ollama 小模型提取意图 + 参数，匹配已知场景
    ↓
┌─ 匹配到已知场景 → 进入确认流程
└─ 未匹配到（新问题）→ 回复"暂无法自动处理"，记录到待审核队列
    ↓
[第一轮确认] 回复："识别到需求：XXX，参数：YYY，是否正确？"
    ↓
[监听群消息] 等待业务在同一群内回复
    ↓
┌─ 确认 → 进入第二轮
├─ 修正 → 重新识别
└─ 取消 → 结束
    ↓
[第二轮确认] 回复："即将执行：XXX，是否执行？"
    ↓
[监听群消息] 等待业务确认
    ↓
┌─ 确认 → 执行工作流
├─ 取消 → 结束
└─ 超时 → 提醒业务
    ↓
[执行工作流] 调用已注册的工作流
    ↓
[反馈结果] 回复执行结果
```

## 3. 系统架构

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  dws event  │────→│  编排脚本     │────→│   Ollama    │
│  consume    │     │  (Python)    │     │  qwen3:8b   │
└─────────────┘     └──────┬───────┘     └─────────────┘
       ↑                   │                    ↑
       │            ┌──────┴───────┐            │
       │            │              │            │
       │     ┌──────▼──────┐ ┌────▼─────┐      │
       │     │  dws chat   │ │ 场景注册表 │      │
       │     │  message    │ │ (YAML)   │──────┘
       │     │  send       │ └────┬─────┘
       │     └─────────────┘      │
       │                   ┌──────▼───────┐
       └───────────────────│  工作流执行器  │
                           └──────────────┘
```

### 组件职责

| 组件 | 职责 | 技术 |
|------|------|------|
| **dws event consume** | 监听 @消息、监听群消息 | dws CLI |
| **编排脚本** | 流程控制、状态管理、消息收发 | Python |
| **Ollama** | 意图提取 + 场景匹配 + 参数抽取 | qwen3:8b |
| **场景注册表** | 定义可处理场景、参数、工作流映射 | YAML |
| **dws chat message send** | 向群内发送确认/结果消息 | dws CLI |
| **工作流执行器** | 调用具体工作流 | TBD |

## 4. 意图识别（Ollama 小模型）

### 4.1 小模型职责

小模型**只负责**：
1. 从自然语言中提取意图
2. 匹配到已知场景（或判断为"未识别"）
3. 抽取结构化参数

小模型**不负责**：
- 不做 function calling
- 不做 MCP 调用
- 不做工作流执行
- 不做消息收发

### 4.2 场景识别策略：两阶段

**第一阶段（当前实现）**：人工预定义场景，小模型做"选择题"
- 场景列表由人工维护，写入 YAML 配置
- 小模型从已知选项中匹配，匹配不到则输出"未识别"
- 准确可控，适合起步

**第二阶段（后续扩展）**：新场景发现 + 人工审核
- 未识别的问题自动记录到待审核队列
- 人工审核后注册为新场景
- 下次同类问题就能自动匹配
- 场景库持续增长，无需改代码

### 4.3 场景注册表

场景按**业务问题类型**划分，不是按操作类型（查询/修改/通知）划分。
业务只提问题，不关心后面流程怎么走。

```yaml
# scenarios.yaml
scenarios:
  - id: bite_alignment
    name: 对咬合
    description: 病例需要对咬合处理
    keywords: [对咬合, 咬合调整, 咬合]
    parameters:
      - name: case_id
        description: 病例编号
        required: true
        pattern: "\\d{2}[A-Z]\\w+"
        examples: [22P6X3]
    workflow: bite_alignment_flow

  - id: clinical_design_confirm
    name: 临床确认设计
    description: 需要临床确认设计方案
    keywords: [确认设计, 临床确认, 确认方案]
    parameters:
      - name: case_id
        description: 病例编号
        required: true
        examples: [22P6X3]
    workflow: clinical_design_confirm_flow

  - id: qc_process
    name: 质检流程处理
    description: 质检任务单的流程处理
    keywords: [质检, 质检流程, 质检任务单]
    parameters:
      - name: case_id
        description: 病例编号
        required: true
        examples: [22P6X3]
      - name: detail
        description: 具体问题描述
        required: false
    workflow: qc_process_flow
```

> 场景列表待补充，需要业务方提供常见场景。

### 4.4 Prompt 设计

将场景列表 + 用户消息组合成 prompt，要求小模型输出结构化 JSON。

**选定模型**：`qwen2.5:3b`（1-2秒响应，适合实时交互场景）

**最终 Prompt 模板**（few-shot 格式，3b 小模型必须用示例引导）：

```
根据用户消息选择场景并提取病例号。

场景：
1. bite_alignment - 对咬合
2. clinical_design_confirm - 临床确认设计
3. qc_process - 质检流程
4. unknown - 不匹配

示例：
输入：病例A1B2C3 需要临床确认设计方案
输出：{"scenario_id":"clinical_design_confirm","case_id":"A1B2C3","confidence":0.9}

输入：A1B2C3 质检流程有问题
输出：{"scenario_id":"qc_process","case_id":"A1B2C3","confidence":0.9}

输入：A1B2C3 需要对咬合
输出：{"scenario_id":"bite_alignment","case_id":"A1B2C3","confidence":0.9}

输入：{user_message}
输出：
```

**Ollama 调用参数**：
```json
{
  "model": "qwen2.5:3b",
  "prompt": "...",
  "stream": false,
  "format": "json",
  "options": {"temperature": 0.1}
}
```

**输出格式**：
```json
{"scenario_id": "场景ID", "case_id": "病例号", "confidence": 0.0-1.0}
```

**技术要点**：
- 使用 Ollama `format: json` 参数约束输出为合法 JSON
- `temperature: 0.1` 降低随机性，提高稳定性
- 场景列表动态从 YAML 加载，拼入 prompt
- few-shot 示例也动态生成（每个场景一个示例）
- confidence 低于阈值时走"未识别"分支

### 4.5 Prompt 调优记录

#### 测试用例

| # | 用户消息 | 期望场景 |
|---|---------|---------|
| 1 | 22P6X3 需要对咬合，麻烦处理下 | bite_alignment |
| 2 | 22P6X3 这个不用新模型出设计了，临床需要确认设计 | clinical_design_confirm |
| 3 | 病例 22P6X3 ...质检任务单的流程如何处理呢 | qc_process |

#### qwen3:8b 测试

**Prompt V1**（描述式，无示例）：
```
你是一个意图识别助手。根据用户消息，从以下场景中选择最匹配的：
1. bite_alignment - 病例需要对咬合处理
2. clinical_design_confirm - 需要临床确认设计方案
3. qc_process - 质检任务单的流程处理
0. unknown - 不属于以上任何场景
请严格输出以下JSON格式，不要输出其他内容：
{"scenario_id": "场景ID或unknown", "parameters": {参数键值对}, "confidence": 0到1的置信度, "reason": "匹配理由简述"}
用户消息：22P6X3 需要对咬合，麻烦处理下
```
→ 输出：`{"scenario_id": "unknown", "parameters": {"bite_alignment": "required"}, "confidence": 0.7}`
→ **问题**：scenario_id 输出 unknown，但 reason 里又识别出了 bite_alignment，自相矛盾
→ 耗时：44秒

**Prompt V2**（简化描述式）：
```
从以下场景中选一个匹配用户消息的，只输出JSON：
场景：
1. bite_alignment - 对咬合
2. clinical_design_confirm - 临床确认设计
3. qc_process - 质检流程处理
0. unknown
输出格式：{"scenario_id":"ID","parameters":{},"confidence":0.0}
用户消息：22P6X3 需要对咬合，麻烦处理下
```
→ 输出：`{"scenario_id":"22P6X3","parameters":{"bite_alignment":"corrected"},"confidence":0.0}`
→ **问题**：把病例号当成了 scenario_id
→ 耗时：4.7秒

**Prompt V3**（强调"从列表选择"）：
```
根据用户消息，从场景列表中选择最匹配的场景ID，并提取参数。
场景列表：
- bite_alignment: 对咬合处理
- clinical_design_confirm: 临床确认设计
- qc_process: 质检流程处理
- unknown: 无法匹配以上任何场景
注意：scenario_id必须从上面的列表中选择，不能自己编造。
用户消息：22P6X3 需要对咬合，麻烦处理下
输出JSON：{"scenario_id": "", "parameters": {"case_id": ""}, "confidence": 0.0}
```
→ 输出：`{"scenario_id": "bite_alignment", "parameters": {"case_id": "22P6X3"}, "confidence": 1.0}`
→ **结果**：正确！
→ 耗时：6.1秒

**Prompt V3 测试用例2**：
→ 输出：`{"scenario_id": "clinical_design_confirm", "parameters": {"case_id": "22P6X3"}, "confidence": 1.0}`
→ **结果**：正确！
→ 耗时：4.8秒

**Prompt V3 测试用例3**（复杂消息）：
→ 输出：`{"scenario_id": "bite_alignment", "parameters": {"case_id": "22P6X3"}, "confidence": 0.85}`
→ **问题**：被"咬合"关键词干扰，应该是 qc_process
→ 耗时：40.8秒

**qwen3:8b 结论**：简单消息准确，但复杂消息容易被干扰；速度不稳定（4-44秒），不适合实时交互。

#### qwen2.5:3b 测试

**Prompt V1**（同 qwen3:8b 的描述式）：
→ 输出：`{"scenario_id": "22P6X3", "parameters": {"case_id": ""}, "confidence": 0.5}`
→ **问题**：把病例号当成了 scenario_id
→ 耗时：1.9秒

**Prompt V2**（极简格式）：
```
选择最匹配的场景ID。
场景：bite_alignment=对咬合 clinical_design_confirm=临床确认设计 qc_process=质检流程 unknown=不匹配
用户消息：22P6X3 需要对咬合，麻烦处理下
只输出JSON：{"id":"场景ID","case_id":"病例号","conf":0.0}
```
→ 输出：`{"id": "22P6X3", "case_id": "", "conf": 0.0}`
→ **问题**：仍然把病例号当 id
→ 耗时：1.4秒

**Prompt V3**（few-shot 示例，关键突破）：
```
根据用户消息选择场景并提取病例号。
场景：
1. bite_alignment - 对咬合
2. clinical_design_confirm - 临床确认设计
3. qc_process - 质检流程
4. unknown - 不匹配
示例：
输入：病例A1B2C3 需要临床确认设计方案
输出：{"scenario_id":"clinical_design_confirm","case_id":"A1B2C3","confidence":0.9}
输入：A1B2C3 质检流程有问题
输出：{"scenario_id":"qc_process","case_id":"A1B2C3","confidence":0.9}
输入：22P6X3 需要对咬合，麻烦处理下
输出：
```
→ 输出：`{"scenario_id": "bite_alignment", "case_id": "", "confidence": 0.8}`
→ **结果**：场景正确，但 case_id 未提取
→ 耗时：1.7秒

**Prompt V4**（few-shot + 每个场景都有示例 + temperature=0.1）：
```
根据用户消息选择场景并提取病例号。
场景：
1. bite_alignment - 对咬合
2. clinical_design_confirm - 临床确认设计
3. qc_process - 质检流程
4. unknown - 不匹配
示例：
输入：病例A1B2C3 需要临床确认设计方案
输出：{"scenario_id":"clinical_design_confirm","case_id":"A1B2C3","confidence":0.9}
输入：A1B2C3 质检流程有问题
输出：{"scenario_id":"qc_process","case_id":"A1B2C3","confidence":0.9}
输入：A1B2C3 需要对咬合
输出：{"scenario_id":"bite_alignment","case_id":"A1B2C3","confidence":0.9}
输入：22P6X3 需要对咬合，麻烦处理下
输出：
```
→ 输出：`{"scenario_id": "bite_alignment", "case_id": "22P6X3", "confidence": 0.9}`
→ **结果**：完全正确！
→ 耗时：1.9秒

**Prompt V4 测试用例2**（临床确认设计）：
→ 输出：`{"scenario_id": "clinical_design_confirm", "case_id": "A1B2C3", "confidence": 0.9, "action": "confirm"}`
→ **问题**：场景正确，但 case_id 用了示例中的 A1B2C3 而非实际的 22P6X3；多了额外字段 action
→ 耗时：1.3秒

**Prompt V4 测试用例3**（质检流程，简化版）：
输入：22P6X3 质检任务单的流程如何处理呢
→ 输出：`{"scenario_id": "qc_process", "case_id": "22P6X3", "confidence": 0.9}`
→ **结果**：完全正确！
→ 耗时：1.1秒

**qwen2.5:3b 结论**：
- 速度稳定在 1-2 秒，适合实时交互
- few-shot 是必须的，3b 模型没有示例就无法正确理解输出格式
- 每个场景都需要一个示例，否则模型可能混淆
- 复杂消息（含多个关键词）需要进一步优化，可能需要拆分为"先提取关键词，再匹配场景"两步
- 偶尔输出额外字段（如 action），代码侧需要做字段过滤

#### 模型对比总结

| 模型 | 速度 | 简单消息准确率 | 复杂消息准确率 | 结论 |
|------|------|--------------|--------------|------|
| qwen3:8b | 4-44秒 | 高 | 低（被关键词干扰） | 太慢，不适合实时交互 |
| qwen2.5:3b | 1-2秒 | 高 | 中（需优化） | 速度快，few-shot 后基本可用 |

**最终选择**：qwen2.5:3b + few-shot prompt + temperature=0.1

**待优化**：
- 复杂消息（含多个场景关键词）的识别策略
- case_id 偶尔被示例值污染的问题
- 输出额外字段的过滤

## 5. 消息交互设计

### 5.1 监听策略

| 阶段 | 事件类型 | 说明 |
|------|----------|------|
| 触发 | `user_im_message_receive_at` | 常驻监听 @消息 |
| 确认等待 | `user_im_message_receive_group` | 临时监听目标群消息 |

**技术要点**：
- @消息监听是常驻进程，群消息监听是按需启动的临时进程
- 需要确认 dws 是否支持同时运行两个 event consume（共用一个 bus）

**待验证**：
- 两个 consume 能否同时运行
- 临时 consume 的启动/停止生命周期管理

### 5.2 确认回复匹配策略

**问题**：业务回复确认时不需要 @助手，如何判断回复是针对确认请求的？

**方案**：时间窗口 + 发送人关联

1. 发出确认消息后，记录：`{群ID, 发送人ID, 时间戳, 确认类型}`
2. 启动群消息监听，匹配规则：
   - 同一群
   - 同一发送人（即最初 @你的那个人）
   - 在时间窗口内（默认 5 分钟）
3. 关键词匹配确认意图：
   - 确认：确认/好的/是/OK/对/没错/执行/可以
   - 否定/修正：不对/不是/取消/错了/修改/等等
   - 超时：提醒业务

**待讨论**：
- 时间窗口多长合适？
- 如果业务在确认窗口内发了无关消息怎么处理？
- 是否需要支持"部分修正"（如"订单号不对，应该是 ORD-456"）？

### 5.3 消息模板

**第一轮确认**：
```
📋 需求识别结果：
  场景：{scenario_name}
  参数：{参数列表}

请确认是否正确？回复「确认」继续，或补充修改。
```

**第二轮确认**：
```
⚡ 即将执行：
  操作：{scenario_name}
  详情：{参数列表}

确认执行？回复「确认」开始执行，回复「取消」终止。
```

**执行结果**：
```
✅ 执行完成：
  {执行结果摘要}
```

**未识别**：
```
❓ 暂时无法识别您的需求，请补充说明。
当前支持的场景：{场景列表}
```

## 6. 代码实现方案

### 6.1 项目结构

```
dingtalk-workflow/
├── main.py              # 入口，启动 @消息监听
├── intent.py            # Ollama 意图识别
├── session.py           # 会话状态管理
├── messenger.py         # dws 消息收发
├── workflow.py          # 工作流执行器
├── config.yaml          # 全局配置（超时、Ollama地址等）
├── scenarios.yaml       # 场景注册表
└── workflows/           # 工作流脚本目录
    ├── bite_alignment.py
    ├── clinical_design_confirm.py
    └── qc_process.py
```

### 6.2 意图识别（intent.py）

```python
import requests
import yaml
import json

OLLAMA_URL = "http://localhost:11434/api/generate"
MODEL = "qwen2.5:3b"

def load_scenarios(path="scenarios.yaml"):
    with open(path) as f:
        return yaml.safe_load(f)["scenarios"]

def build_prompt(user_message, scenarios):
    # 场景列表
    scenario_lines = []
    for i, s in enumerate(scenarios, 1):
        scenario_lines.append(f"{i}. {s['id']} - {s['name']}")
    scenario_lines.append(f"{len(scenarios)+1}. unknown - 不匹配")

    # few-shot 示例（每个场景一个）
    examples = []
    for s in scenarios:
        example_case = s["parameters"][0]["examples"][0] if s["parameters"][0].get("examples") else "XXX"
        examples.append(
            f'输入：{example_case} {s["keywords"][0]}\n'
            f'输出：{{"scenario_id":"{s["id"]}","case_id":"{example_case}","confidence":0.9}}'
        )

    prompt = f"""根据用户消息选择场景并提取病例号。

场景：
{chr(10).join(scenario_lines)}

示例：
{chr(10).join(examples)}

输入：{user_message}
输出："""

    return prompt

def identify_intent(user_message, scenarios):
    prompt = build_prompt(user_message, scenarios)

    resp = requests.post(OLLAMA_URL, json={
        "model": MODEL,
        "prompt": prompt,
        "stream": False,
        "format": "json",
        "options": {"temperature": 0.1}
    })

    result = json.loads(resp.json()["response"])

    # 过滤额外字段，只保留需要的
    return {
        "scenario_id": result.get("scenario_id", "unknown"),
        "case_id": result.get("case_id", ""),
        "confidence": result.get("confidence", 0.0)
    }
```

### 6.3 消息监听与发送（messenger.py）

```python
import subprocess
import json

def listen_at_messages(callback):
    """常驻监听 @消息，每收到一条调用 callback(event)"""
    proc = subprocess.Popen(
        ["dws", "event", "consume", "user_im_message_receive_at",
         "--flatten", "-f", "ndjson"],
        stdout=subprocess.PIPE, stderr=subprocess.PIPE,
        text=True
    )

    for line in proc.stdout:
        event = json.loads(line)
        callback(event)

def send_message(conversation_id, text):
    """向群内发送消息"""
    subprocess.run([
        "dws", "chat", "message", "send",
        "--group", conversation_id,
        "--text", text
    ], capture_output=True)

def wait_for_reply(conversation_id, sender_id, timeout=300):
    """监听群消息，等待指定发送人的回复"""
    proc = subprocess.Popen(
        ["dws", "event", "consume", "user_im_message_receive_group",
         "--group", conversation_id,
         "--flatten", "-f", "ndjson",
         "--duration", f"{timeout}s"],
        stdout=subprocess.PIPE, stderr=subprocess.PIPE,
        text=True
    )

    for line in proc.stdout:
        event = json.loads(line)
        if event["sender_open_dingtalk_id"] == sender_id:
            return event["content"]

    return None  # 超时
```

### 6.4 会话状态管理（session.py）

```python
from dataclasses import dataclass, field
from datetime import datetime
from enum import Enum
import uuid

class SessionState(Enum):
    PENDING_CONFIRM1 = "pending_confirm1"   # 等待第一轮确认
    PENDING_CONFIRM2 = "pending_confirm2"   # 等待第二轮确认
    EXECUTING = "executing"                 # 执行中
    DONE = "done"                           # 完成
    CANCELLED = "cancelled"                 # 已取消

@dataclass
class Session:
    session_id: str = field(default_factory=lambda: str(uuid.uuid4()))
    conversation_id: str = ""
    sender: str = ""
    sender_open_dingtalk_id: str = ""
    original_message: str = ""
    scenario_id: str = ""
    scenario_name: str = ""
    parameters: dict = field(default_factory=dict)
    state: SessionState = SessionState.PENDING_CONFIRM1
    created_at: datetime = field(default_factory=datetime.now)
    updated_at: datetime = field(default_factory=datetime.now)

# 内存存储（后续可换 SQLite）
sessions: dict[str, Session] = {}

def create_session(event, intent_result) -> Session:
    session = Session(
        conversation_id=event["conversation_id"],
        sender=event["sender"],
        sender_open_dingtalk_id=event["sender_open_dingtalk_id"],
        original_message=event["content"],
        scenario_id=intent_result["scenario_id"],
        parameters=intent_result["parameters"],
    )
    sessions[session.session_id] = session
    return session
```

### 6.5 主流程编排（main.py）

```python
from intent import load_scenarios, identify_intent
from messenger import listen_at_messages, send_message, wait_for_reply
from session import create_session, SessionState

scenarios = load_scenarios()

def handle_message(event):
    content = event["content"]
    conversation_id = event["conversation_id"]
    sender_id = event["sender_open_dingtalk_id"]

    # 1. 意图识别
    result = identify_intent(content, scenarios)

    if result["scenario_id"] == "unknown":
        send_message(conversation_id,
            "❓ 暂时无法识别您的需求，请补充说明。\n"
            f"当前支持的场景：{', '.join(s['name'] for s in scenarios)}")
        # 记录到待审核队列
        log_unknown_message(content)
        return

    # 2. 创建会话
    session = create_session(event, result)

    # 3. 第一轮确认
    params_text = "\n  ".join(f"{k}: {v}" for k, v in result["parameters"].items())
    send_message(conversation_id,
        f"📋 需求识别结果：\n"
        f"  场景：{result.get('scenario_name', result['scenario_id'])}\n"
        f"  参数：{params_text}\n\n"
        f"请确认是否正确？回复「确认」继续，或补充修改。")

    # 4. 等待第一轮确认
    reply = wait_for_reply(conversation_id, sender_id)
    if not reply or not is_confirmed(reply):
        if reply and is_cancelled(reply):
            send_message(conversation_id, "已取消。")
        return

    # 5. 第二轮确认
    session.state = SessionState.PENDING_CONFIRM2
    send_message(conversation_id,
        f"⚡ 即将执行：\n"
        f"  操作：{result.get('scenario_name', result['scenario_id'])}\n"
        f"  详情：{params_text}\n\n"
        f"确认执行？回复「确认」开始执行，回复「取消」终止。")

    # 6. 等待第二轮确认
    reply = wait_for_reply(conversation_id, sender_id)
    if not reply or not is_confirmed(reply):
        if reply and is_cancelled(reply):
            send_message(conversation_id, "已取消。")
        return

    # 7. 执行工作流
    session.state = SessionState.EXECUTING
    send_message(conversation_id, "正在执行...")
    result = execute_workflow(session.scenario_id, session.parameters)

    # 8. 反馈结果
    session.state = SessionState.DONE
    send_message(conversation_id, f"✅ 执行完成：\n  {result}")

def is_confirmed(text):
    keywords = ["确认", "好的", "是", "OK", "对", "没错", "执行", "可以"]
    return any(kw in text for kw in keywords)

def is_cancelled(text):
    keywords = ["取消", "不要", "算了", "放弃"]
    return any(kw in text for kw in keywords)

# 启动
listen_at_messages(handle_message)
```

### 6.6 工作流执行器（workflow.py）

```python
import importlib

def execute_workflow(scenario_id, parameters):
    """动态加载并执行对应工作流脚本"""
    try:
        module = importlib.import_module(f"workflows.{scenario_id}")
        return module.run(parameters)
    except ModuleNotFoundError:
        return f"工作流 {scenario_id} 尚未实现"
    except Exception as e:
        return f"工作流执行失败：{str(e)}"
```

每个工作流是一个独立 Python 文件，只需实现 `run(parameters)` 函数：

```python
# workflows/bite_alignment.py
def run(parameters):
    case_id = parameters["case_id"]
    # 具体业务逻辑...
    return f"已为病例 {case_id} 安排对咬合处理"
```

## 7. 待验证技术点

| # | 验证项 | 方法 | 状态 |
|---|--------|------|------|
| 1 | qwen2.5:3b + `format: json` 能否稳定输出合法 JSON | 本地测试 | ✅ 已验证，需 few-shot |
| 2 | qwen2.5:3b 复杂消息识别准确率 | 本地测试 | ⚠️ 部分通过，需优化 |
| 3 | 两个 dws event consume 能否同时运行 | 本地测试 | 待验证 |
| 4 | dws event consume `--duration` 参数是否支持 | `dws schema "event consume"` | 待验证 |
| 5 | 群消息监听能否按 `--group` 过滤 | `dws event consume --help` | 待验证 |

## 8. 待讨论问题清单

| # | 问题 | 优先级 |
|---|------|--------|
| 1 | 场景注册表——需要业务方提供常见场景列表 | 高 |
| 2 | 确认回复中"部分修正"如何处理 | 高 |
| 3 | 工作流执行的具体形式（当前方案：独立 Python 文件） | 高 |
| 4 | 状态存储方式（当前方案：内存，后续换 SQLite） | 中 |
| 5 | 多场景匹配时的处理策略 | 中 |
| 6 | confidence 阈值设定 | 中 |
| 7 | 超时时间窗口长度 | 低 |
| 8 | 并发会话处理 | 低 |
| 9 | 日志与监控 | 低 |
| 10 | 部署方案 | 低 |
