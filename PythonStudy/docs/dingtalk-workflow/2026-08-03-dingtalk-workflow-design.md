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
业务 @助手 → 监听 @消息（常驻线程1）
    ↓
[用户锁检查] 同一用户同一时间只能有一个活跃会话
    ↓
┌─ 有活跃会话 → 回复"您有正在处理的任务，请先完成或取消"
└─ 无活跃会话 → 继续
    ↓
[意图识别] Ollama 小模型提取意图 + 参数，匹配已知场景
    ↓
┌─ 匹配到已知场景 → 创建会话，设置用户锁，进入确认流程
└─ 未匹配到（新问题）→ 回复"暂无法自动处理"，记录到待审核队列
    ↓
[第一轮确认] 回复："识别到需求：XXX，参数：YYY，是否正确？"
    ↓
[监听群消息]（常驻线程2，按群+发送人路由到对应会话）
    ↓
┌─ 确认 → 进入第二轮
├─ 修正 → 重新识别
└─ 取消 → 释放用户锁，结束
    ↓
[第二轮确认] 回复："即将执行：XXX，是否执行？"
    ↓
[监听群消息]（同上，状态机驱动）
    ↓
┌─ 确认 → 执行工作流
├─ 取消 → 释放用户锁，结束
└─ 超时 → 提醒业务，释放用户锁
    ↓
[执行工作流] 调用已注册的工作流
    ↓
[反馈结果] 回复执行结果，释放用户锁

注：多个用户可同时走流程，互不阻塞
    每一步操作都记录到 Redis（状态）+ SQLite（日志）
    聊天上下文保存在 Redis，会话结束后写入 SQLite 汇总
```

## 3. 系统架构

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  dws event  │────→│  编排脚本     │────→│   Ollama    │
│  consume    │     │  (Python)    │     │  qwen2.5:3b │
│  (线程1:@)  │     │  事件驱动    │     └─────────────┘
│  (线程2:群) │     │  状态机      │
└─────────────┘     └──────┬───────┘
       ↑                   │
       │            ┌──────┴───────┐
       │            │              │
       │     ┌──────▼──────┐ ┌────▼─────┐
       │     │  dws chat   │ │ 场景注册表 │
       │     │  message    │ │ (YAML)   │
       │     │  send       │ └────┬─────┘
       │     └─────────────┘      │
       │                   ┌──────▼───────┐
       │                   │  工作流执行器  │
       │                   └──────┬───────┘
       │                          │
       │            ┌─────────────┴─────────────┐
       │            │                           │
       │     ┌──────▼──────┐           ┌────────▼───────┐
       └────→│    Redis    │           │    SQLite      │
             │ 会话状态     │           │ 操作日志       │
             │ 聊天上下文   │           │ 会话汇总       │
             │ 用户锁      │           │ 待审核队列     │
             └─────────────┘           └────────────────┘
```

### 组件职责

| 组件 | 职责 | 技术 |
|------|------|------|
| **dws event consume** | 监听 @消息（线程1）、监听群消息（线程2） | dws CLI |
| **编排脚本** | 事件驱动、状态机、消息收发 | Python |
| **Ollama** | 意图提取 + 场景匹配 + 参数抽取 | qwen2.5:3b |
| **场景注册表** | 定义可处理场景、参数、工作流映射 | YAML |
| **dws chat message send** | 向群内发送确认/结果消息 | dws CLI |
| **Redis** | 会话状态、聊天上下文、用户锁（TTL 自动过期） | Redis |
| **SQLite** | 操作日志、会话汇总、待审核队列（持久化查询） | SQLite |
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

## 6. 存储设计

### 6.1 存储分工

| 存储 | 职责 | 数据特点 |
|------|------|---------|
| **Redis** | 会话状态（进行中的）、聊天上下文、用户锁 | 高频读写、需要 TTL 自动过期、进程重启后仍可用 |
| **SQLite** | 操作日志（持久化）、执行结果、历史查询 | 需要复杂查询、统计分析、长期保留 |

### 6.2 Redis 数据结构

```
# 用户锁：同一用户同一时间只能有一个活跃会话
user_lock:{sender_open_dingtalk_id} → session_id
TTL: 30分钟（与会话同步过期）

# 会话状态
session:{session_id} → {
    session_id, conversation_id, sender, sender_open_dingtalk_id,
    original_message, scenario_id, scenario_name, parameters,
    state, created_at, updated_at
}
TTL: 30分钟

# 聊天上下文（消息链，按会话保存）
context:{session_id} → [
    {role: "user", content: "22P6X3 需要对咬合", timestamp: ...},
    {role: "assistant", content: "📋 需求识别结果：...", timestamp: ...},
    {role: "user", content: "确认", timestamp: ...},
    ...
]
TTL: 30分钟
```

### 6.3 SQLite 表结构

```sql
-- 操作日志：记录每一步操作
CREATE TABLE step_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    step_type TEXT NOT NULL,        -- intent / confirm1 / confirm2 / execute / result
    status TEXT NOT NULL,           -- success / failed / timeout / cancelled
    input TEXT,                     -- 输入内容（JSON）
    output TEXT,                    -- 输出内容（JSON）
    error TEXT,                     -- 错误信息
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 会话汇总：一个会话一条记录
CREATE TABLE session_log (
    session_id TEXT PRIMARY KEY,
    conversation_id TEXT NOT NULL,
    sender_id TEXT NOT NULL,
    original_message TEXT NOT NULL,
    scenario_id TEXT,
    scenario_name TEXT,
    parameters TEXT,                -- JSON
    final_status TEXT NOT NULL,     -- completed / cancelled / failed / timeout
    workflow_result TEXT,           -- JSON
    started_at DATETIME NOT NULL,
    finished_at DATETIME
);

-- 未识别消息：待审核队列
CREATE TABLE unknown_message (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    conversation_id TEXT NOT NULL,
    sender_id TEXT NOT NULL,
    content TEXT NOT NULL,
    reviewed INTEGER DEFAULT 0,     -- 0: 未审核, 1: 已审核
    mapped_scenario TEXT,           -- 审核后映射的场景
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### 6.4 操作记录点

每个步骤执行时，同时写入 Redis（更新状态）和 SQLite（记录日志）：

```
[意图识别]
  → Redis: 创建 session + context
  → SQLite step_log: {step_type: "intent", status: "success/failed", input: 用户消息, output: 识别结果}

[第一轮确认发送]
  → Redis: session.state = PENDING_CONFIRM1, context 追加助手消息
  → SQLite step_log: {step_type: "confirm1", status: "pending"}

[第一轮确认收到回复]
  → Redis: session.state 更新, context 追加用户回复
  → SQLite step_log: {step_type: "confirm1", status: "confirmed/cancelled/timeout", input: 用户回复}

[第二轮确认发送]
  → Redis: session.state = PENDING_CONFIRM2, context 追加助手消息
  → SQLite step_log: {step_type: "confirm2", status: "pending"}

[第二轮确认收到回复]
  → Redis: session.state 更新, context 追加用户回复
  → SQLite step_log: {step_type: "confirm2", status: "confirmed/cancelled/timeout", input: 用户回复}

[执行工作流]
  → Redis: session.state = EXECUTING
  → SQLite step_log: {step_type: "execute", status: "success/failed", output: 执行结果}

[会话结束]
  → Redis: 删除 session + context + user_lock
  → SQLite session_log: {final_status: "completed/cancelled/failed/timeout", workflow_result: ...}
```

## 7. 并发控制

### 7.1 核心约束

1. **多用户并发**：多个用户可以同时各自走流程
2. **单用户串行**：同一用户同一时间只能有一个进行中的会话

### 7.2 用户锁机制

新消息进来时，先检查该用户是否有进行中的会话：

```
收到 @消息
    ↓
检查 Redis user_lock:{sender_id}
    ↓
├─ 有活跃会话 → 回复"您有正在处理的任务，请先完成或取消"
└─ 无活跃会话 → 创建新会话，设置 user_lock
```

### 7.3 状态机

```
                    ┌──────────────────────────────────┐
                    │ 修正（重新识别）                    │
                    ▼                                  │
  [IDLE] ──@消息──→ [PENDING_CONFIRM1] ──确认──→ [PENDING_CONFIRM2]
                    │       │                           │       │
                 取消/超时  │                        取消/超时  │
                    │       │                           │       │
                    ▼       │                           ▼       │
              [CANCELLED]   │                     [EXECUTING]   │
                    │       │                           │       │
                    │       │                       失败/成功    │
                    │       │                           │       │
                    │       │                           ▼       ▼
                    │       │                      [DONE]  [FAILED]
                    │       │                           │       │
                    └───────┴───────────────────────────┘       │
                    释放用户锁                                   │
                    释放用户锁 ◄────────────────────────────────┘
```

状态转换规则：

| 当前状态 | 事件 | 目标状态 | 动作 |
|---------|------|---------|------|
| IDLE | 收到 @消息（识别成功） | PENDING_CONFIRM1 | 创建会话、设置用户锁、发送确认1 |
| IDLE | 收到 @消息（识别失败） | IDLE | 回复未识别、记录到待审核队列 |
| PENDING_CONFIRM1 | 用户确认 | PENDING_CONFIRM2 | 发送确认2 |
| PENDING_CONFIRM1 | 用户修正 | PENDING_CONFIRM1 | 重新识别、重新发送确认1 |
| PENDING_CONFIRM1 | 用户取消 | CANCELLED | 释放用户锁 |
| PENDING_CONFIRM1 | 超时 | CANCELLED | 提醒用户、释放用户锁 |
| PENDING_CONFIRM2 | 用户确认 | EXECUTING | 执行工作流 |
| PENDING_CONFIRM2 | 用户取消 | CANCELLED | 释放用户锁 |
| PENDING_CONFIRM2 | 超时 | CANCELLED | 提醒用户、释放用户锁 |
| EXECUTING | 执行成功 | DONE | 反馈结果、释放用户锁 |
| EXECUTING | 执行失败 | FAILED | 反馈错误、释放用户锁 |

### 7.4 事件驱动架构

原方案用 `wait_for_reply` 阻塞等待，无法支持多用户并发。改为**事件驱动**：

```
@消息监听（常驻进程）
    ↓ 收到消息
检查用户锁 → 有活跃会话 → 提示"请先完成当前任务"
            → 无活跃会话 → 意图识别 → 创建会话 → 推进状态机

群消息监听（常驻进程）
    ↓ 收到消息
查找该群+发送人对应的活跃会话
    ↓
根据会话当前状态 → 推进状态机（confirm1→confirm2→execute→done）
```

关键变化：
- **不再用 `wait_for_reply` 阻塞等待**，改为常驻群消息监听 + 状态机驱动
- 收到消息后，根据会话当前状态决定下一步动作
- 多个用户的会话可以同时推进，互不阻塞

## 8. 代码实现方案

### 8.1 项目结构

```
dingtalk-workflow/
├── main.py              # 入口，启动事件监听
├── intent.py            # Ollama 意图识别
├── session.py           # 会话状态管理（Redis）
├── store.py             # 持久化存储（SQLite）
├── messenger.py         # dws 消息收发
├── workflow.py          # 工作流执行器
├── state_machine.py     # 状态机驱动
├── config.yaml          # 全局配置（超时、Ollama地址、Redis地址等）
├── scenarios.yaml       # 场景注册表
└── workflows/           # 工作流脚本目录
    ├── bite_alignment.py
    ├── clinical_design_confirm.py
    └── qc_process.py
```

### 8.2 意图识别（intent.py）

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

### 8.3 消息监听与发送（messenger.py）

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

def listen_group_messages(callback):
    """常驻监听群消息，每收到一条调用 callback(event)"""
    proc = subprocess.Popen(
        ["dws", "event", "consume", "user_im_message_receive_group",
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
```

### 8.4 会话状态管理（session.py）

```python
import redis
import json
import uuid
from datetime import datetime

r = redis.Redis(host="localhost", port=6379, db=0, decode_responses=True)
SESSION_TTL = 1800  # 30分钟

class SessionState:
    PENDING_CONFIRM1 = "pending_confirm1"
    PENDING_CONFIRM2 = "pending_confirm2"
    EXECUTING = "executing"
    DONE = "done"
    CANCELLED = "cancelled"
    FAILED = "failed"

def create_session(event, intent_result) -> str:
    """创建会话，设置用户锁，返回 session_id"""
    session_id = str(uuid.uuid4())
    sender_id = event["sender_open_dingtalk_id"]

    # 设置用户锁
    r.set(f"user_lock:{sender_id}", session_id, ex=SESSION_TTL)

    # 保存会话状态
    session_data = {
        "session_id": session_id,
        "conversation_id": event["conversation_id"],
        "sender": event["sender"],
        "sender_open_dingtalk_id": sender_id,
        "original_message": event["content"],
        "scenario_id": intent_result["scenario_id"],
        "scenario_name": intent_result.get("scenario_name", ""),
        "parameters": json.dumps(intent_result.get("parameters", {})),
        "state": SessionState.PENDING_CONFIRM1,
        "created_at": datetime.now().isoformat(),
        "updated_at": datetime.now().isoformat(),
    }
    r.hset(f"session:{session_id}", mapping=session_data)
    r.expire(f"session:{session_id}", SESSION_TTL)

    # 初始化聊天上下文
    r.rpush(f"context:{session_id}", json.dumps({
        "role": "user",
        "content": event["content"],
        "timestamp": datetime.now().isoformat()
    }))
    r.expire(f"context:{session_id}", SESSION_TTL)

    return session_id

def get_session(session_id: str) -> dict | None:
    """获取会话状态"""
    data = r.hgetall(f"session:{session_id}")
    return data if data else None

def update_session_state(session_id: str, state: str):
    """更新会话状态"""
    r.hset(f"session:{session_id}", "state", state)
    r.hset(f"session:{session_id}", "updated_at", datetime.now().isoformat())

def append_context(session_id: str, role: str, content: str):
    """追加聊天上下文"""
    r.rpush(f"context:{session_id}", json.dumps({
        "role": role,
        "content": content,
        "timestamp": datetime.now().isoformat()
    }))

def get_context(session_id: str) -> list[dict]:
    """获取完整聊天上下文"""
    raw_list = r.lrange(f"context:{session_id}", 0, -1)
    return [json.loads(item) for item in raw_list]

def check_user_lock(sender_id: str) -> str | None:
    """检查用户是否有活跃会话，返回 session_id 或 None"""
    return r.get(f"user_lock:{sender_id}")

def release_user_lock(sender_id: str):
    """释放用户锁"""
    r.delete(f"user_lock:{sender_id}")

def end_session(session_id: str, sender_id: str):
    """结束会话：释放用户锁，清理 Redis 数据"""
    release_user_lock(sender_id)
    r.delete(f"session:{session_id}")
    r.delete(f"context:{session_id}")

def find_active_session_by_sender(conversation_id: str, sender_id: str) -> str | None:
    """根据群ID+发送人查找活跃会话"""
    lock_session_id = check_user_lock(sender_id)
    if not lock_session_id:
        return None
    session = get_session(lock_session_id)
    if session and session["conversation_id"] == conversation_id:
        return lock_session_id
    return None
```

### 8.5 持久化存储（store.py）

```python
import sqlite3
import json
from datetime import datetime

DB_PATH = "dingtalk_workflow.db"

def get_db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    """初始化数据库表"""
    conn = get_db()
    conn.executescript("""
        CREATE TABLE IF NOT EXISTS step_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT NOT NULL,
            step_type TEXT NOT NULL,
            status TEXT NOT NULL,
            input TEXT,
            output TEXT,
            error TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS session_log (
            session_id TEXT PRIMARY KEY,
            conversation_id TEXT NOT NULL,
            sender_id TEXT NOT NULL,
            original_message TEXT NOT NULL,
            scenario_id TEXT,
            scenario_name TEXT,
            parameters TEXT,
            final_status TEXT NOT NULL,
            workflow_result TEXT,
            started_at DATETIME NOT NULL,
            finished_at DATETIME
        );
        CREATE TABLE IF NOT EXISTS unknown_message (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            conversation_id TEXT NOT NULL,
            sender_id TEXT NOT NULL,
            content TEXT NOT NULL,
            reviewed INTEGER DEFAULT 0,
            mapped_scenario TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    """)
    conn.commit()
    conn.close()

def log_step(session_id: str, step_type: str, status: str,
             input_data=None, output_data=None, error=None):
    """记录操作步骤"""
    conn = get_db()
    conn.execute(
        "INSERT INTO step_log (session_id, step_type, status, input, output, error) "
        "VALUES (?, ?, ?, ?, ?, ?)",
        (session_id, step_type, status,
         json.dumps(input_data, ensure_ascii=False) if input_data else None,
         json.dumps(output_data, ensure_ascii=False) if output_data else None,
         error)
    )
    conn.commit()
    conn.close()

def log_session(session_id: str, session_data: dict, final_status: str,
                workflow_result=None):
    """记录会话汇总"""
    conn = get_db()
    conn.execute(
        "INSERT OR REPLACE INTO session_log "
        "(session_id, conversation_id, sender_id, original_message, "
        "scenario_id, scenario_name, parameters, final_status, "
        "workflow_result, started_at, finished_at) "
        "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        (session_id, session_data["conversation_id"],
         session_data["sender_open_dingtalk_id"],
         session_data["original_message"],
         session_data.get("scenario_id"),
         session_data.get("scenario_name"),
         session_data.get("parameters"),
         final_status,
         json.dumps(workflow_result, ensure_ascii=False) if workflow_result else None,
         session_data.get("created_at"),
         datetime.now().isoformat())
    )
    conn.commit()
    conn.close()

def log_unknown_message(conversation_id: str, sender_id: str, content: str):
    """记录未识别消息到待审核队列"""
    conn = get_db()
    conn.execute(
        "INSERT INTO unknown_message (conversation_id, sender_id, content) "
        "VALUES (?, ?, ?)",
        (conversation_id, sender_id, content)
    )
    conn.commit()
    conn.close()
```

### 8.6 状态机驱动（state_machine.py）

```python
from session import (SessionState, get_session, update_session_state,
                     append_context, end_session, find_active_session_by_sender)
from store import log_step, log_session
from messenger import send_message
from workflow import execute_workflow

def is_confirmed(text: str) -> bool:
    keywords = ["确认", "好的", "是", "OK", "对", "没错", "执行", "可以"]
    return any(kw in text for kw in keywords)

def is_cancelled(text: str) -> bool:
    keywords = ["取消", "不要", "算了", "放弃"]
    return any(kw in text for kw in keywords)

def is_correction(text: str) -> bool:
    keywords = ["不对", "不是", "错了", "修改", "应该是"]
    return any(kw in text for kw in keywords)

def advance_state(session_id: str, user_reply: str):
    """根据当前状态和用户回复，推进状态机"""
    session = get_session(session_id)
    if not session:
        return

    conversation_id = session["conversation_id"]
    sender_id = session["sender_open_dingtalk_id"]
    state = session["state"]

    # 记录用户回复到上下文
    append_context(session_id, "user", user_reply)

    if state == SessionState.PENDING_CONFIRM1:
        handle_confirm1(session_id, session, user_reply, conversation_id, sender_id)

    elif state == SessionState.PENDING_CONFIRM2:
        handle_confirm2(session_id, session, user_reply, conversation_id, sender_id)

def handle_confirm1(session_id, session, user_reply, conversation_id, sender_id):
    """处理第一轮确认的回复"""
    if is_confirmed(user_reply):
        # 确认 → 进入第二轮
        update_session_state(session_id, SessionState.PENDING_CONFIRM2)
        log_step(session_id, "confirm1", "success", input_data={"reply": user_reply})

        params = json.loads(session.get("parameters", "{}"))
        params_text = "\n  ".join(f"{k}: {v}" for k, v in params.items())
        scenario_name = session.get("scenario_name") or session.get("scenario_id", "")
        msg = (f"⚡ 即将执行：\n"
               f"  操作：{scenario_name}\n"
               f"  详情：{params_text}\n\n"
               f"确认执行？回复「确认」开始执行，回复「取消」终止。")
        send_message(conversation_id, msg)
        append_context(session_id, "assistant", msg)

    elif is_correction(user_reply):
        # 修正 → 重新识别（保持同一会话）
        log_step(session_id, "confirm1", "correction", input_data={"reply": user_reply})
        # TODO: 调用意图识别重新处理 user_reply，更新会话参数，重新发送确认1

    elif is_cancelled(user_reply):
        # 取消 → 结束
        update_session_state(session_id, SessionState.CANCELLED)
        log_step(session_id, "confirm1", "cancelled", input_data={"reply": user_reply})
        log_session(session_id, session, "cancelled")
        send_message(conversation_id, "已取消。")
        end_session(session_id, sender_id)

def handle_confirm2(session_id, session, user_reply, conversation_id, sender_id):
    """处理第二轮确认的回复"""
    if is_confirmed(user_reply):
        # 确认 → 执行工作流
        update_session_state(session_id, SessionState.EXECUTING)
        log_step(session_id, "confirm2", "success", input_data={"reply": user_reply})
        send_message(conversation_id, "正在执行...")
        append_context(session_id, "assistant", "正在执行...")

        # 执行工作流
        params = json.loads(session.get("parameters", "{}"))
        try:
            result = execute_workflow(session.get("scenario_id"), params)
            update_session_state(session_id, SessionState.DONE)
            log_step(session_id, "execute", "success", output_data=result)
            log_session(session_id, session, "completed", workflow_result=result)
            send_message(conversation_id, f"✅ 执行完成：\n  {result}")
            append_context(session_id, "assistant", f"✅ 执行完成：\n  {result}")
        except Exception as e:
            update_session_state(session_id, SessionState.FAILED)
            log_step(session_id, "execute", "failed", error=str(e))
            log_session(session_id, session, "failed")
            send_message(conversation_id, f"❌ 执行失败：{str(e)}")
            append_context(session_id, "assistant", f"❌ 执行失败：{str(e)}")

        end_session(session_id, sender_id)

    elif is_cancelled(user_reply):
        # 取消 → 结束
        update_session_state(session_id, SessionState.CANCELLED)
        log_step(session_id, "confirm2", "cancelled", input_data={"reply": user_reply})
        log_session(session_id, session, "cancelled")
        send_message(conversation_id, "已取消。")
        end_session(session_id, sender_id)
```

### 8.7 主流程编排（main.py）

```python
import json
import threading
from intent import load_scenarios, identify_intent
from session import (create_session, check_user_lock, get_session,
                     append_context, find_active_session_by_sender)
from store import init_db, log_step, log_session, log_unknown_message
from messenger import listen_at_messages, listen_group_messages, send_message
from state_machine import advance_state

scenarios = load_scenarios()

def handle_at_message(event):
    """处理 @消息：意图识别 + 创建会话"""
    content = event["content"]
    conversation_id = event["conversation_id"]
    sender_id = event["sender_open_dingtalk_id"]

    # 检查用户锁：同一用户同一时间只能有一个活跃会话
    active_session_id = check_user_lock(sender_id)
    if active_session_id:
        send_message(conversation_id,
            f"⚠️ 您有正在处理的任务，请先完成或取消后再发起新请求。")
        return

    # 意图识别
    result = identify_intent(content, scenarios)

    if result["scenario_id"] == "unknown":
        send_message(conversation_id,
            "❓ 暂时无法识别您的需求，请补充说明。\n"
            f"当前支持的场景：{', '.join(s['name'] for s in scenarios)}")
        log_unknown_message(conversation_id, sender_id, content)
        return

    # 记录意图识别步骤
    session_id = create_session(event, result)
    log_step(session_id, "intent", "success",
             input_data={"message": content}, output_data=result)

    # 发送第一轮确认
    scenario_name = next(
        (s["name"] for s in scenarios if s["id"] == result["scenario_id"]),
        result["scenario_id"]
    )
    params_text = "\n  ".join(f"{k}: {v}" for k, v in result.get("parameters", {}).items())
    msg = (f"📋 需求识别结果：\n"
           f"  场景：{scenario_name}\n"
           f"  参数：{params_text}\n\n"
           f"请确认是否正确？回复「确认」继续，或补充修改。")
    send_message(conversation_id, msg)
    append_context(session_id, "assistant", msg)
    log_step(session_id, "confirm1", "pending")

def handle_group_message(event):
    """处理群消息：查找对应会话，推进状态机"""
    conversation_id = event["conversation_id"]
    sender_id = event["sender_open_dingtalk_id"]
    content = event["content"]

    # 查找该用户在当前群的活跃会话
    session_id = find_active_session_by_sender(conversation_id, sender_id)
    if not session_id:
        return  # 不是确认回复，忽略

    # 推进状态机
    advance_state(session_id, content)

def start_at_listener():
    """启动 @消息监听线程"""
    listen_at_messages(handle_at_message)

def start_group_listener():
    """启动群消息监听线程"""
    listen_group_messages(handle_group_message)

if __name__ == "__main__":
    init_db()

    # 两个监听线程并行运行
    t1 = threading.Thread(target=start_at_listener, daemon=True)
    t2 = threading.Thread(target=start_group_listener, daemon=True)
    t1.start()
    t2.start()

    # 主线程保持运行
    t1.join()
```

### 8.8 工作流执行器（workflow.py）

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

## 9. 待验证技术点

| # | 验证项 | 方法 | 状态 |
|---|--------|------|------|
| 1 | qwen2.5:3b + `format: json` 能否稳定输出合法 JSON | 本地测试 | ✅ 已验证，需 few-shot |
| 2 | qwen2.5:3b 复杂消息识别准确率 | 本地测试 | ⚠️ 部分通过，需优化 |
| 3 | 两个 dws event consume 能否同时运行 | 本地测试 | 待验证 |
| 4 | dws event consume `--duration` 参数是否支持 | `dws schema "event consume"` | 待验证 |
| 5 | 群消息监听能否按 `--group` 过滤 | `dws event consume --help` | 待验证 |
| 6 | Redis 连接稳定性 + TTL 过期清理 | 本地测试 | 待验证 |
| 7 | 两个监听线程能否稳定并行运行 | 本地测试 | 待验证 |
| 8 | 群消息监听能否区分不同群的消息 | `dws event consume` 事件结构 | 待验证 |

## 10. 待讨论问题清单

| # | 问题 | 优先级 |
|---|------|--------|
| 1 | 场景注册表——需要业务方提供常见场景列表 | 高 |
| 2 | 确认回复中"部分修正"如何处理 | 高 |
| 3 | 工作流执行的具体形式（当前方案：独立 Python 文件） | 高 |
| 4 | 多场景匹配时的处理策略 | 中 |
| 5 | confidence 阈值设定 | 中 |
| 6 | 超时时间窗口长度 | 中 |
| 7 | 超时后如何通知用户（主动发消息 vs 等用户再 @） | 中 |
| 8 | Redis 不可用时的降级方案（退回内存存储？） | 中 |
| 9 | 并发会话数上限（防止 Redis 内存溢出） | 低 |
| 10 | 日志与监控 | 低 |
| 11 | 部署方案 | 低 |
