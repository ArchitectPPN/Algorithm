# 错题本 - 开发过程中遇到的问题复盘

> 本文记录 Agent CLI 开发过程中遇到的 10 个典型问题，包括现象、根因、修复方法、教训。
> 用于复盘学习，避免下次踩同样的坑。

---

## 问题 1：CLI.print() 缺少必填参数

### 现象
```
❌ 处理失败: CLI.print() missing 1 required positional argument: 'msg'
```
agent 启动后第一个对话就报错，无法继续。

### 根因
`ui/cli.py` 的 `CLI.print` 方法签名：
```python
def print(self, msg: str, style: str = ""):
    console.print(msg, style=style)
```
`msg` 是必填位置参数。但代码里有 `self.cli.print()` 这样无参数调用（在 `agent_loop.py` 里换行用）。

### 修复
```python
def print(self, msg: str = "", style: str = ""):
    console.print(msg, style=style)
```
`msg` 改为可选，默认空字符串。

### 教训
- **方法签名要考虑所有调用场景**：如果允许无参调用，参数必须有默认值
- **空字符串作默认值比 None 更友好**：调用方不用判断 None，直接传给下游

---

## 问题 2：没有 loading 状态指示

### 现象
用户输入问题后，终端长时间没有任何反馈，不知道 agent 是在思考、卡住了、还是死掉了。工具执行过程也看不见。

### 根因
原实现只做了"模型返回完整文本后一次性渲染"，没有：
1. 模型请求阶段的 spinner
2. 文本流式实时渲染
3. 工具执行阶段的 spinner

### 修复
用 `rich` 库的三种动态展示组件：

| 场景 | 组件 | 用途 |
|------|------|------|
| 模型思考中 | `Status` | 显示 `🤔 思考中...` 旋转 spinner |
| 文本流式输出 | `Live` | 实时渲染累积文本，逐字出现 |
| 工具执行中 | `Live` + `Spinner` | 显示 `执行 xxx 中...` 旋转 spinner |

关键代码：
```python
# 思考中 spinner
status = Status("🤔 思考中...", spinner="dots")
status.start()

# 流式渲染
live = Live(Text(""), refresh_per_second=15)
live.start()
for chunk in text_stream:
    parts.append(chunk)
    live.update(Text("".join(parts)))
live.stop()

# 工具执行 spinner（用线程跑工具，主线程显示 spinner）
spinner = Spinner("dots", text="执行 Bash 中...")
live = Live(spinner)
live.start()
# 工具在子线程执行...
live.stop()
```

### 教训
- **CLI 工具必须有状态反馈**：用户最焦虑的是"它到底在干啥？"
- **思考、流式、工具三阶段要分开处理**：每个阶段有不同的展示需求
- **`rich` 库是 Python CLI 的瑞士军刀**：Status/Live/Spinner/Panel/Markdown 一应俱全

---

## 问题 3：tool_calls.function.arguments 必须是字符串

### 现象
```
❌ 模型 astron-code-latest API 错误: Error code: 500 - {
  'error': {
    'code': 10012,
    'message': 'json: cannot unmarshal object into Go struct field FunctionCall.messages.tool_calls.function.arguments of type string',
    'type': 'server_error'
  }
}
```
工具调用完成后，下一轮把 tool result 发给模型时报 500。

### 根因
OpenAI API 规范要求 `tool_calls.function.arguments` 字段是 **JSON 字符串**（不是 object）：
```json
// 正确
{"arguments": "{\"command\": \"pwd\"}"}

// 错误（我们传的）
{"arguments": {"command": "pwd"}}
```

OpenAI 官方服务器容忍了这个错误（自动转换），但 modelscope 的服务器（astron-code-latest）用 Go 实现，严格按规范反序列化，直接报 500。

`Message.to_dict()` 原实现直接把 dict 放进 arguments：
```python
d["tool_calls"] = [{
    "function": {"name": tc.name, "arguments": tc.arguments}  # dict，不是字符串
}]
```

### 修复
```python
import json

d["tool_calls"] = [{
    "function": {
        "name": tc.name,
        "arguments": tc.arguments if isinstance(tc.arguments, str)
                     else json.dumps(tc.arguments, ensure_ascii=False),
    }
}]
```
把 dict 用 `json.dumps()` 序列化为 JSON 字符串。对 Anthropic Provider 无影响（它的 `input` 字段本就要求 dict）。

### 教训
- **API 规范要严格遵循**：不要依赖服务器的宽容，不同实现宽容度不同
- **类型不一致是 OpenAI 兼容服务器的常见坑**：字符串 vs 对象、整数 vs 字符串、null vs 缺失字段
- **数据结构设计要明确字段类型**：`ToolCall.arguments` 内部用 dict（方便操作），序列化时转字符串（符合 API 规范）

---

## 问题 4：对话记录里出现循环片段

### 现象
模型输出反复摇摆，看起来像在循环。比如先输出一段 ` ```exec pwd ` 代码块（agent 不识别），然后又调 function call，下一轮又输出 ` ```exec `...

### 根因
`chat_memory.json` 第一条是 `role=system` 的旧版 system prompt，内容包含：
- ` ```exec ` 代码块协议（旧版自定义协议）
- ` ```subtasks ` 任务拆分协议
- ` ```tool_call ` MCP 调用协议
- 旧版 MCP 工具列表

模型看到 system prompt 教它用 ` ```exec ` 输出命令，但新版 agent 只识别 function calling。模型在两种协议之间摇摆，产生"循环"。

**这个 system 消息是怎么混进来的**：旧版 agent_main.py 把 system_prompt 拼接后塞到 messages[0]，被保存到 chat_memory.json。新版加载这个文件时，把 system 消息也加载了，导致污染。

### 修复
两步：
1. **清空 chat_memory.json**：删除被污染的历史
2. **`load_memory` 跳过 system 消息**：system_prompt 由 `build_system_prompt()` 单独构造，单独传给 Provider，**不应出现在 messages 列表里**

```python
def load_memory() -> list:
    msgs = []
    skipped_system = 0
    for m in data:
        if m.get("role") == "system":
            skipped_system += 1
            continue
        # ... 反序列化
    if skipped_system:
        console.print(f"⚠️ 已跳过 {skipped_system} 条旧版 system 消息（避免协议污染）")
    return msgs
```

### 教训
- **system prompt 不应进入 messages 历史**：它由配置生成，每次启动重新构造，不需要持久化
- **协议升级时要清理旧数据**：旧协议的指令会让模型在新协议下行为混乱
- **加载历史数据时要过滤校验**：不要盲目信任磁盘上的数据，跳过不符合当前规范的消息

---

## 问题 5：不希望启动就加载上次记忆

### 现象
每次 `python agent_main.py` 都自动加载上次的对话历史，但用户大多数时候想开新会话。

### 根因
原实现默认加载记忆：
```python
history = load_memory()
if history:
    agent_loop.load_history(history)
```

### 修复
改为默认不加载，加 `--resume` 才加载：
```python
if "--resume" in sys.argv or "-r" in sys.argv:
    history = load_memory()
    if history:
        agent_loop.load_history(history)
        cli.info(f"已加载 {len(history)} 条历史对话（--resume）")
```

同时让 `clear` 命令删除磁盘文件（彻底清空）。

### 教训
- **默认行为要符合多数场景**：开新会话比恢复旧会话更常见，应该默认
- **CLI 工具用 flag 控制可选行为**：`--resume` / `-r` 是标准做法
- **clear 命令要彻底**：只清内存不够，磁盘文件也要删，不然下次 `--resume` 又加载了

---

## 问题 6：所有会话堆在一个文件里

### 现象
所有对话历史都堆在 `config/chat_memory.json` 一个文件里，时间一长文件巨大，无法区分不同会话。

### 根因
单一文件存储设计：
```python
MEMORY_FILE = os.path.join(CONFIG_DIR, "chat_memory.json")
```

### 修复
建立 `config/chat_history/` 目录，每次会话单独存一个文件，按时间戳命名：
```
config/
└── chat_history/
    ├── 2026-07-08_22-30-15.json
    ├── 2026-07-08_23-09-09.json
    └── ...
```

关键函数：
```python
def generate_session_id() -> str:
    return datetime.datetime.now().strftime("%Y-%m-%d_%H-%M-%S")

def list_sessions(limit=20):
    # 按修改时间倒序列出
    files.sort(key=lambda f: os.path.getmtime(...), reverse=True)
    return files[:limit]

def get_session_preview(session_file):
    # 取第一条 user 消息作为预览
    for m in msgs:
        if m.role == "user" and m.content:
            return m.content[:60]
```

`--resume` 时列出最近 10 个会话，每个显示第一条 user 消息预览，用户选编号恢复。

新增 `/sessions` 命令，运行中也能查看历史会话。

### 教训
- **会话是天然的数据边界**：不同对话互不污染，应该分开存储
- **时间戳是天然的会话 ID**：可读、有序、唯一
- **预览帮助用户选择**：列表里每个会话显示首条消息预览，比只显示文件名有用得多
- **空会话不保存**：避免产生空 json 文件

---

## 问题 7：Task 工具派生子 agent 缺少 tools 参数

### 现象
```
⚡ Task (read_only) desc=审查核心模块实现 type=reviewer
✗ 失败 (0.00s) 工具执行异常: AgentLoop.__init__() missing 1 required positional argument: 'tools'
```
模型调用 Task 工具派生子 agent 时立即失败。

### 根因
`TaskTool.execute()` 创建子 `AgentLoop` 时漏传必填的 `tools` 参数：
```python
# 错误：没有 tools 参数
sub_loop = AgentLoop(
    provider=context.provider,
    permission_engine=context.permission_engine,
    skill_loader=None,
    tools_filter=tool_names,  # 这是白名单，不是 tools 本身
    work_dir=context.work_dir,
    is_subagent=True,
)
```

`tools_filter` 是白名单（限制子 agent 可用工具），`tools` 才是工具字典本身。两个都缺一不可。

### 修复
三步：
1. **`ToolContext` 增加 `tools` 字段**：让工具能访问主 agent 的工具字典
   ```python
   @dataclass
   class ToolContext:
       ...
       tools: Dict[str, Any]  # 新增
   ```

2. **`AgentLoop._execute_tool_call` 构造 ctx 时传入 tools**：
   ```python
   ctx = ToolContext(
       ...
       tools=self.tools,
   )
   ```

3. **`TaskTool.execute()` 从 context.tools 取工具字典并传入**：
   ```python
   tools_dict = getattr(context, "tools", None) or {}
   sub_loop = AgentLoop(
       ...
       tools=tools_dict,  # 共享主 agent 的工具实例
       tools_filter=tool_names,  # 用白名单限制可用工具
   )
   ```

### 教训
- **必填参数不要漏传**：这是低级错误，但容易在重构时遗漏
- **工具上下文要完整**：工具可能需要访问其他工具（如 Task 需要 tools 字典派生子 agent）
- **集成测试要覆盖真实调用链路**：单元测试 import 通过不代表运行时参数齐全，要实际跑一次 Task 工具派生子 agent 的完整流程
- **`tools` vs `tools_filter` 容易混淆**：命名要区分清楚，前者是工具池，后者是白名单

---

## 问题 8：多轮工具调用时重复打印 "🤖 助手:" 前缀

### 现象
模型一轮对话里调用了多个工具（比如先 Glob 再 Read 再 Edit），每调完一个工具回到模型继续输出文本时，终端就会再打印一次 `🤖 助手:` 前缀，并且会清空之前的流式文本显示。一轮对话下来屏幕上出现好几个 `🤖 助手:`，前面的文本也被冲掉了。

### 根因
`AgentLoop._call_model()` 把渲染状态（累积文本列表 `_all_text_parts`、Live 实例 `_live`、Status 实例 `_status`）都做成了**局部变量**：

```python
def _call_model(self, system_prompt, tools_schema):
    all_text_parts = []          # 局部变量
    live = None                  # 局部变量
    status = None                # 局部变量
    ...
```

主循环 `run()` 里每一轮都调一次 `_call_model`，每次都拿到**全新的空状态**。于是：
- 上一轮已经打印过 `🤖 助手:` 前缀并启动了 Live，本轮又重新走"第一个 text_delta 到达时打印前缀 + 启动 Live"的分支
- 上一轮累积的文本丢失（新 `all_text_parts` 是空的），Live 重新从空字符串开始显示

本质上是**渲染状态的归属错了**：跨轮次需要复用的状态被当成单次调用内的局部状态。

### 修复 - `core/agent_loop.py`

把渲染状态提到实例属性，跨轮复用：

```python
class AgentLoop:
    def __init__(self, ...):
        ...
        # 渲染状态：跨 _call_model 轮次复用，避免重复打印 "🤖 助手:" 前缀
        self._all_text_parts: List[str] = []
        self._live: Optional[Live] = None
        self._status: Optional[Status] = None

    def run(self, user_input: str) -> str:
        ...
        # 每次会话开始时初始化（不是每轮）
        self._all_text_parts = []
        self._live = None
        self._status = None
        if not self.is_subagent:
            self._status = Status("[bold cyan]🤔 思考中...[/bold cyan]", ...)
            self._status.start()

        try:
            for round_idx in range(self.max_rounds):
                text_len_before = len(self._all_text_parts)
                _, tool_calls = self._call_model(effective_system, tools_schema)
                # 本轮新增文本 = 累积文本中从 text_len_before 开始的部分
                round_text = "".join(self._all_text_parts[text_len_before:])
                ...
        finally:
            if self._live:
                self._live.stop()
                self._live = None
            if self._status:
                self._status.stop()
                self._status = None
```

`_call_model` 里改成读写 `self._all_text_parts` / `self._live` / `self._status`：

```python
def _call_model(self, ...):
    for event in events:
        if event.type == "text_delta":
            # 第一个文本片段到达：只启动一次 Live
            if self._live is None and not self.is_subagent:
                if self._status:
                    self._status.stop()
                    self._status = None
                console.print("[bold magenta]🤖 助手:[/bold magenta]")
                self._live = Live(Text(""), ...)
                self._live.start()
            self._all_text_parts.append(event.text or "")
            full_text = "".join(self._all_text_parts)
            if self._live:
                self._live.update(Text(full_text))
        elif event.type == "tool_call":
            # 工具调用前停 live（工具阶段由 tool_display 单独显示）
            if self._live:
                self._live.stop()
                self._live = None
            ...
```

**关键点**：
- `self._live is None` 判断保证一次会话里只打印一次 `🤖 助手:` 前缀
- 工具调用时停 Live 但**不清空** `_all_text_parts`，下一轮回到模型时继续累积
- `text_len_before` 切片精确取出"本轮新增文本"，避免跨轮重复
- `finally` 里统一清理 live/status，防止异常残留

### 教训
- **跨轮次状态要用实例属性，不要用局部变量**：渲染状态、累积文本这类需要跨多次方法调用的状态，必须挂在实例上
- **"只启动一次"用 None 判断**：`if self._live is None` 比维护额外的 `started` 标志更简洁
- **切片取增量**：`parts[len_before:]` 是跨轮累积场景下取本轮增量的标准模式
- **状态归属是常见的设计错误**：局部 vs 实例 vs 类属性，选错了就会出现"重复初始化"或"状态泄漏"的 bug

---

## 问题 9：模型输出疯狂重复

### 现象
用户问"看看当前项目是干什么的"，模型先调了几个工具（Glob/Read），然后开始输出文本回复。文本里同一段话重复了几十次：

```
让我先看看项目的整体结构和关键文件。好的，我已经全面了解了这个项目。下面给你一个清晰的总结：
让我先看看项目的整体结构和关键文件。好的，我已经全面了解了这个项目。下面给你一个清晰的总结：
让我先看看项目的整体结构和关键文件。好的，我已经全面了解了这个项目。下面给你一个清晰的总结：
...（重复几十次）
```

模型似乎陷入循环，无法停止。

### 根因
**模型退化重复（degenerate repetition）**。astron-code-latest 这类模型在长上下文 + 多轮工具调用后，容易陷入"重复同一个短语"的退化模式。这是模型本身的问题，但 agent 层没做任何检测和兜底，任由模型一直输出。

OpenAI API 提供了 `frequency_penalty` 参数可以预防，但我们没启用。

### 修复（三层防御）

#### 1. 重复检测（兜底） - `core/agent_loop.py`

新增 `detect_repetition()` 函数，在流式接收文本时实时检测：

```python
def detect_repetition(text: str, min_phrase_len: int = 5, min_repeats: int = 3) -> tuple:
    """检测文本末尾是否陷入重复循环。
    检查 text 末尾是否有某个长度 >= min_phrase_len 的短语连续重复 >= min_repeats 次。
    返回 (是否重复, 截断索引)。
    """
    if len(text) < min_phrase_len * min_repeats:
        return False, len(text)

    # 只检查最后 800 字符，避免大文本导致计算量大
    tail = text[-800:]
    tail_len = len(tail)

    # 从短到长尝试不同短语长度
    max_phrase_len = min(80, tail_len // min_repeats)
    for phrase_len in range(min_phrase_len, max_phrase_len + 1):
        phrase = tail[-phrase_len:]
        repeats = 1
        pos = tail_len - phrase_len
        while pos - phrase_len >= 0 and tail[pos - phrase_len:pos] == phrase:
            repeats += 1
            pos -= phrase_len
            if repeats >= min_repeats:
                cut_in_tail = pos
                cut_in_text = len(text) - tail_len + cut_in_tail
                return True, cut_in_text
    return False, len(text)
```

在 `_call_model` 流式循环里调用：
```python
for event in events:
    if event.type == "text_delta":
        self._all_text_parts.append(event.text or "")
        full_text = "".join(self._all_text_parts)
        if self._live:
            self._live.update(Text(full_text))
        # 重复检测：检测到则截断并中断
        is_rep, cut_idx = detect_repetition(full_text)
        if is_rep:
            repetition_detected = True
            self._all_text_parts = [full_text[:cut_idx]]
            if self._live:
                self._live.update(Text(full_text[:cut_idx]))
            break
```

检测到后：
- 截断重复部分（保留第一次出现的内容）
- `break` 中断流式接收
- 提示用户："检测到模型输出陷入重复，已自动截断"

**算法关键点**：
- `min_phrase_len=5`：太短会误报（如"的"、","），太长会漏检
- `min_repeats=3`：连续 3 次就触发，避免重复过多才检测到
- 只检查最后 800 字符：大文本性能可控
- 从短到长尝试：优先匹配短短语（更激进的检测）

#### 2. frequency_penalty（预防） - `providers/openai_compat.py` + `config.json`

```python
class OpenAICompatProvider:
    def __init__(self, ..., frequency_penalty: float = 0.0):
        self.frequency_penalty = frequency_penalty

    def _chat_once(self, ...):
        kwargs = {...}
        if self.frequency_penalty > 0:
            kwargs["frequency_penalty"] = self.frequency_penalty
```

config 默认 `frequency_penalty: 0.3`，惩罚已出现过的 token，减少重复倾向。

#### 3. 子 agent 也受保护

重复检测对子 agent 同样生效（虽然不显示 UI，但会 break 流式）。

### 教训
- **模型退化重复是常见问题**：弱模型在长上下文 + 工具调用后容易陷入，必须做兜底检测
- **frequency_penalty 是预防手段但非万能**：不同模型敏感度不同，重复检测作为兜底更可靠
- **流式输出要实时检测**：不能等模型自己停下来，它可能永远不停
- **检测算法要平衡灵敏度和误报**：`min_phrase_len=5, min_repeats=3` 是经验值，避免误报列表项、排比句等正常重复
- **只检查末尾**：开头重复可能是引用上下文，末尾重复才是退化

---

## 问题 10：Task 工具执行 125 秒不返回

### 现象
模型调用 Task 工具派生 reviewer 子 agent 审查代码，子 agent 跑了 125.91 秒才返回。期间用户看着 spinner 干等，无法中断，也不知道还要等多久。

### 根因
`TaskTool.execute()` 没有任何超时和轮数限制：
```python
# 原实现
final_text = sub_loop.run_subagent(prompt, system_prompt)
return ToolResult(success=True, output=final_text)
```

子 agent 用的是主 agent 的 `max_rounds=30`，每轮都要调模型 + 可能执行多个工具，累积起来时间很长。子 agent 内部如果也陷入重复或工具循环，更是无底洞。

### 修复 - `tools/task.py`

加超时和轮数双重限制：

```python
class TaskTool(BaseTool):
    SUBAGENT_TIMEOUT = 120   # 子 agent 最大执行时间（秒）
    SUBAGENT_MAX_ROUNDS = 15  # 主 agent 是 30 轮，子 agent 减半

    def execute(self, params, context):
        sub_loop = AgentLoop(
            ...
            max_rounds=self.SUBAGENT_MAX_ROUNDS,
            is_subagent=True,
        )

        # 在子线程执行，主线程等待有超时
        result_holder = {"text": "", "error": None}
        done_event = threading.Event()

        def run_subagent():
            try:
                result_holder["text"] = sub_loop.run_subagent(prompt, system_prompt)
            except Exception as e:
                result_holder["error"] = e
            finally:
                done_event.set()

        t = threading.Thread(target=run_subagent, daemon=True)
        t.start()

        # 等待完成或超时
        finished = done_event.wait(timeout=self.SUBAGENT_TIMEOUT)
        if not finished:
            return ToolResult(
                success=False,
                error=f"子 agent 执行超时（{self.SUBAGENT_TIMEOUT}s 已强制终止）",
            )
        ...
```

**关键点**：
- 子 agent 用 `threading.Thread` + `daemon=True` 跑，主线程用 `Event.wait(timeout=)` 等待
- 超时返回错误，不让用户干等
- `daemon=True` 确保主进程退出时子线程不会阻塞
- `max_rounds` 减半（15 轮），子 agent 任务应该比主 agent 简单

### 教训
- **子任务必须有超时**：无超时的子任务会拖垮整个 agent，用户无法中断
- **子 agent 轮数应该比主 agent 少**：子任务是局部任务，不需要那么多轮
- **threading.Event 是简单的超时等待机制**：比 `Thread.join(timeout=)` 更灵活
- **daemon 线程避免僵尸**：主进程退出时 daemon 线程自动结束，不会卡住
- **用户反馈很重要**：超时后明确告诉用户"已强制终止"，而不是默默失败

---

## 总结：10 个问题的共性教训

### 1. API 规范要严格遵循（问题 3、9）
不要依赖服务器的宽容。不同 OpenAI 兼容服务器（OpenAI 官方 / modelscope / DeepSeek / Qwen）宽容度不同，严格按规范来最安全。模型本身的退化行为也要做兜底，不能假设模型永远正常。

### 2. 数据边界要清晰（问题 4、6、8）
- system prompt 不进 messages 历史
- 不同会话不混在一个文件
- 显示用累积，历史用切片

### 3. 状态管理要明确归属（问题 7、8）
- 实例属性 vs 局部变量：跨方法共享用实例属性
- 必填参数不要漏传：重构时容易遗漏
- 工具上下文要完整：工具可能需要访问其他工具

### 4. 用户体验要反馈（问题 1、2、5、10）
- CLI 必须有状态反馈（spinner / 流式 / 工具进度）
- 默认行为要符合多数场景（新会话比恢复旧会话常见）
- 错误信息要可读（不要让用户看到 stack trace）
- 长时间操作必须有超时和中断机制（问题 10）

### 5. 测试要覆盖真实链路（问题 7、9）
单元测试 import 通过不代表运行时参数齐全。集成测试要实际跑完整调用链路（如 Task 工具派生子 agent）。模型退化行为要用真实日志场景验证检测算法。

### 6. 防御式编程（问题 3、4、9、10）
- 加载数据时过滤校验（跳过 system 消息）
- 流式输出实时检测（重复检测兜底）
- 子任务加超时（避免无底洞）
- API 参数严格按规范（arguments 用字符串）

---

## 附：调试技巧

1. **看磁盘数据**：`chat_memory.json` / `chat_history/*.json` 里能看到真实保存的消息，判断是数据层还是渲染层问题
2. **加日志**：在关键路径加 `print` 或 `console.log`，看事件流是否符合预期
3. **最小复现**：用 `echo "input" | python agent_main.py` 管道输入，快速复现问题
4. **隔离测试**：把可疑函数单独拿出来跑（如 `python -c "from xxx import yyy; yyy()"`），排除其他因素干扰
5. **用真实日志测试算法**：重复检测算法要用用户实际遇到的重复文本测试，避免纸上谈兵（问题 9）
