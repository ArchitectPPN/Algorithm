# Token 计算与上下文管理

> Agent 开发中 token 计算的核心知识。从"为什么会超限"到"怎么估算、怎么防、怎么兜底"。

---

## 一、两种 token 计算的用途

这是最重要的认知——**token 计算分两种，用途完全不同**：

| | 事前估算 | 事后统计 |
|---|---|---|
| **时机** | 发请求**之前** | 发请求**之后** |
| **来源** | 自己用 tokenizer 算 | API 返回的 `usage` 字段 |
| **精度** | 有误差（5-30%） | 100% 准确（计费依据） |
| **用途** | 防**超限**（判断要不要压缩） | 算**成本**（花了多少钱） |
| **为什么需要** | 不能先发请求看超不超——超了直接报错 | API 返回的是真实值，用来对账、计费 |

**关键认知**：
- 成本统计 → 用 API 返回的 usage（事后、准确）✅
- 上下文窗口管理 → 必须自己事前估算（不估算就只能等报错）

Claude Code 等 Agent 两种都用——显示的 token 数来自 API 返回值，窗口压缩触发来自自己估算。

---

## 二、Token 和字符的关系（常见误区）

**核心误区**："输入 N 个字符，token 不能超过 N"——**这是错的**。

**真相**：token 是模型的**分词单位**，不是字符单位。一个 token 可能包含多个字符，也可能一个字符拆成多个 token。

| 内容 | 字符数 | token 数（GPT cl100k_base） | token > 字符？ | 说明 |
|------|--------|---------------------------|--------------|------|
| `"hello"` | 5 | 1 | ❌ 少 | 常见英文词，整体一个 token |
| `"hello!"` | 6 | 2 | ❌ 少 | 标点单独成 token |
| `"你好"` | 2 | 2 | — | 常见汉字 1:1 |
| `"人工智能"` | 4 | 5-6 | ✅ **多** | 词被拆分，4字符变5-6 token |
| `"{\"key\":\"val\"}"` | 14 | 6-8 | ❌ 少 | JSON 每个符号占 token |
| 生僻字 `𠮷` | 1 | 2-3 | ✅ **多** | 分词器不认识，1字符拆2-3 token |
| `"囧rz"` | 3 | 4-5 | ✅ **多** | 生僻字+英文混排，拆得更碎 |
| `"🧠💡🚀"` | 3 | 6-9 | ✅ **多** | emoji 每个拆 2-3 token |
| `"a b c d e"` | 9 | 5 | ❌ 少 | 空格+单字母，空格被前词吞 |
| `"https://api.example.com/v1/chat"` | 30 | 8-10 | ❌ 少 | URL 常见模式被合并 |

**经验范围**（GPT/Claude 系）：

| 语言 | token/字符 比值 |
|------|----------------|
| 纯英文常见词 | 0.2-0.3（一个 token 含多个字符） |
| 纯英文生僻词 | 0.5-1.0 |
| 中文 | 0.6-1.5（常见字 1:1，生僻字拆多） |
| JSON/代码 | 0.3-0.5（结构符号多） |
| 混合文本 | 最复杂 |

**结论**：token 数可 > 字符数，也可 < 字符数，没有固定换算关系，取决于分词器。

### 不只是生僻字——常见中文词也会 token > 字符

很多人以为只有生僻字和 emoji 才会 token > 字符数，**不是的**。中文常见词也经常超过，原因是分词器按 UTF-8 字节切分，一个汉字 3 字节，如果词表里没有这个词，就会在字节中间切断。

**实测（tiktoken cl100k_base）**：

#### "人工智能" — 4 字符 → 5 token

```
原文:  "人  工  智     能"
UTF-8: e4ba ba e5b7 a5 e699 ba e883 bd
       ───── ───── ──── ── ──────
token:  人    工    智↗  ↘能
                    (一个字被切成2个token)
```

| token | 对应 | 说明 |
|-------|------|------|
| `人` | e4 ba ba | 词表里有，1 token 完整表示 |
| `工` | e5 b7 a5 | 词表里有，1 token 完整表示 |
| `智` 的前 2 字节 | e6 99 | **词表里没有"智"，在字节中间切断** |
| `智` 的第 3 字节 | ba | 剩余的 1 字节单独成 token |
| `能` | e8 83 bd | 词表里有，1 token 完整表示 |

4 个字符 → 5 个 token，因为"智"字被拆成了 2 个 token。

#### "深度学习" — 4 字符 → 6 token

```
原文:  "深     度  学  习"
UTF-8: e6b7 b1 e5ba a6 e5ad a6 e4b9 a0
       ──── ── ───── ───── ──── ──
token: 深↗  ↘度   学    习↗   ↘
       (2个字被拆碎)
```

4 个字符 → 6 个 token，"深"和"习"都被拆碎了。

#### "你好" — 2 字符 → 2 token（正常）

```
原文:  "你  好"
UTF-8: e4bda0 e5a5bd
       ───── ─────
token:  你    好
```

常见字在词表里有，1:1。

#### "囧" — 1 字符 → 2 token

```
原文:  "囧"
UTF-8: e59b a7
       ──── ──
token: 囧↗  ↘
       (生僻字，词表没有，字节中间切断)
```

#### "hello" — 5 字符 → 1 token

```
原文:  "hello"
UTF-8: 68 65 6c 6c 6f
       ─────────────
token:   hello
       (常见英文词，整体一个token)
```

**规律总结**：

| 情况 | token vs 字符 | 原因 |
|------|--------------|------|
| 英文常见词 | 远少于 | 一个 token 包含多个字符（如 "hello" 5字符1token） |
| 英文生僻词 | 接近或略多 | 拆成更小片段 |
| 中文常见字 | 大致 1:1 | 每个字在词表里有（如 "你""好""的"） |
| 中文常见词 | **经常多于** | 词不在词表，某些字在 UTF-8 字节中间被切断 |
| 中文生僻字 | 明显多于 | 一个字拆 2-3 个 token（如 "囧" 1字符2token） |
| emoji/特殊符号 | 远多于 | 编码复杂，拆很多片段 |

**核心原因**：tiktoken 的词表是按**字节对**（byte pairs）训练的，不是按字符。中文一个字 = 3 个 UTF-8 字节，如果词表没收录这个字的完整 3 字节组合，就会在字节中间切断——**1 个汉字变 2-3 个 token**。中文天然"费 token"。

**关键区分**：分词器不是在"拆字符"，而是在"切字节"。它根本不知道什么是"字符"——只认字节，碰巧切在字符中间而已。比如"智"的 UTF-8 是 `e6 99 ba` 三个字节，分词器在 `e6 99` 和 `ba` 之间切断，不是因为它知道"智"是一个字符然后故意拆开，而是词表里恰好有 `e6 99` 这个字节对，就切了。

---

## 三、三种估算方案

| 方案 | 怎么算 | 精度 | 成本 |
|------|--------|------|------|
| **1. tokenizer 库** | 用模型分词器本地算 | 高（95%+） | 免费、本地 |
| **2. 字符数估算** | 中文字数 × 系数 + 英文 × 系数 | 中（80%） | 零依赖 |
| **3. 调 API 算** | 发给 count_tokens 接口 | 100% | 慢、费钱 |

### 方案1：tokenizer 库（生产推荐）

```python
import tiktoken
enc = tiktoken.get_encoding("cl100k_base")  # GPT-4o 编码
token_count = len(enc.encode("你好，世界"))   # → 4 左右
```

**tiktoken 是什么**：OpenAI 官方开源的 tokenization 库（GitHub: `openai/tiktoken`），Python + Rust 实现，专门给 GPT 系列模型做分词。因为是自己家的分词器，对 GPT-4o/GPT-4/GPT-3.5 精度 100%。但别的模型（DeepSeek、Claude 等）用的是各自的分词器，tiktoken 只能近似。

**⚠️ 不是所有模型都适合 tiktoken**：每个模型有自己的分词器（tokenizer），tiktoken 只是 OpenAI 家的那一个。用 tiktoken 算非 OpenAI 模型，就像用英文字典去查中文字——能查到近似结果，但不是真实值。

**tiktoken 适合的场景**：

| 场景 | 适合？ | 说明 |
|------|--------|------|
| 只用 OpenAI 模型（GPT-4o 等） | ✅ 完美 | 原生分词器，100% 准确 |
| 用 OpenAI 兼容模型（DeepSeek/通义）做**防超限估算** | ✅ 可用 | 有 5-10% 误差，但配合 1.3 系数 + 70% 阈值 buffer，足够安全 |
| 用 OpenAI 兼容模型做**精确计费** | ❌ 不行 | 5-10% 误差对计费不可接受，必须用 API 返回的 usage |
| 用 Claude / 智谱 / 本地模型 | ❌ 不适合 | 分词器完全不同，误差 15-20%+，即使乘系数也不可靠 |
| 不知道背后跑什么模型（中转站） | ⚠️ 凑合用 | 用最保守系数（1.3-1.5），靠阈值 buffer 和报错兜底补 |

**总结**：tiktoken 的价值不是"精确"，而是"快速、免费、本地"——在防超限场景下，**估算有误差没关系，buffer 兜得住就行**。真要精确值，永远看 API 返回的 usage。

### 方案2：字符数估算（应急用）

```python
def estimate_tokens(text):
    chinese = sum(1 for c in text if '一' <= c <= '鿿')
    other = len(text) - chinese
    return int(chinese * 0.7 + other * 0.3)
```

零依赖，但结构化内容（工具定义、JSON）算不准。

### 方案3：调 API 算（不推荐）

每次都要调 API，慢且费钱，多数模型没这接口。

---

## 四、为什么需要"模型系数"

tiktoken 不能覆盖所有模型，但生产中要支持多模型。解法：**tiktoken 基础值 × 模型系数**。

```python
TOKEN_MULTIPLIERS = {
    "deepseek-v4-flash": 1.1,     # DeepSeek 比 OpenAI 稍多
    "gpt-4o-mini": 1.0,           # tiktoken 原生
    "claude-sonnet-5": 1.3,       # Claude 分词器不同，token 偏多
}

def estimate_tokens(text, model):
    base_count = len(enc.encode(text))
    multiplier = TOKEN_MULTIPLIERS.get(model, 1.2)  # 未知用 1.2 保守
    return int(base_count * multiplier)
```

**系数怎么来**：实测——
```
1. tiktoken 算 100
2. 调真实 API 看 usage.prompt_tokens
3. 系数 = usage / base_count
4. 多测几次取平均（或取最大值更保守）
```

---

## 五、系数 1.3 是怎么来的（关键认知）

**1.3 不是科学值，是经验值**——覆盖三类误差叠加：

| 误差来源 | 量级 |
|---------|------|
| tokenizer 差异 | +10-20% |
| 结构化开销（工具定义、JSON） | +5-10% |
| 中转站不确定性 | +5-10% |

最坏叠加可能偏低 20-40%，取中间保守值 1.3。

**为什么是 1.3 不是 1.0 或 2.0**：
- 1.0（不放大）→ 估算偏低 → 可能发出去超限 → 报错
- 2.0（放大太多）→ 没到上限就压缩 → 浪费上下文、频繁压缩影响质量
- 1.3 → 在"别超限"和"别浪费"间平衡

**更关键的是阈值 buffer**：
```
if 估算 token > 窗口 × 0.7:   # 70% 阈值触发压缩
```
70% 阈值 + 1.3 系数 = 实际约 91% 才触发，还留 9% 给极端误差。**系数不需要准，buffer 吸收误差**。

---

## 六、中转站的额外问题

```
你的代码 → 中转站（one-api/new-api）→ 实际模型 API
```

中转站引入三个变数：

| 问题 | 说明 |
|------|------|
| 模型名被改 | 中转站内部映射，你不知道背后实际跑哪个模型 |
| tokenizer 不确定 | 以为调 GPT，可能路由到别的模型，系数白调 |
| usage 可能不准 | 有些中转站改写 usage（虚报/按自己计费返回） |

**应对**：既然底层不可控，从最保守方式防御——
1. 事前估算用最保守系数（不知道背后模型就用 1.3）
2. 事后统计不盲信 API 返回值，自己也记
3. **终极兜底**：API 报错就压缩重试

---

## 七、完整防御策略（事前防 + 事后兜）

```
发请求前：
  1. 估算 messages 总 token（tiktoken × 系数）
  2. 估算 > 窗口 × 0.7 → 触发压缩（摘要旧消息）
  3. 用压缩后的 messages 发请求

发请求后：
  4. 如果 400 context_length_exceeded → 压缩 → 重试（兜底）
  5. 成功 → 记录 API 返回的真实 usage（用于成本统计）
```

**核心哲学**：估算用来"尽量防"，报错用来"兜底救"。两头堵，不怕模型/中转站换。

---

## 八、适配器中的实现位置

```python
class OpenAIAdapter(LLMAdapter):
    def chat(self, messages, tools=None, temperature=0, **kwargs):
        # 1. 事前估算（保守）
        est = estimate_messages_tokens(messages, multiplier=1.3)
        if est > self.max_context * 0.7:
            messages = compress_messages(messages)

        # 2. 发请求
        resp = requests.post(...)

        # 3. 超限兜底
        if resp.status_code == 400 and "context_length" in resp.text:
            messages = compress_messages(messages)
            resp = requests.post(...)  # 重试

        # 4. 记录真实 usage
        usage = extract_usage(resp)
        self._total_usage += usage
        return LLMResponse(message=..., usage=usage, cost=...)
```

---

## 九、面试要点

1. **token 计算分两种**：事前估算（防超限）、事后统计（算成本）——讲清这个区分就证明懂
2. **token ≠ 字符**：token 是分词单位，可多于或少于字符数
3. **tiktoken 局限**：只准 OpenAI 系，别的模型要乘系数
4. **1.3 系数来源**：经验值，覆盖 tokenizer 差异+结构化开销+中转站不确定性
5. **不追精确，追可靠**：估算 + 阈值 buffer + 报错兜底，多重防御
6. **中转站风险**：模型名映射、usage 改写——用保守系数 + 兜底重试应对

---

## 十、实战：`model_context_window_exceeded` 错误

### 错误长什么样

当你发给模型的 messages 总 token 超过模型上下文窗口时，API 返回 400 错误。

**真实报错（Claude Code 实际遇到的）**：

```
⏺ API Error: 400 error, status code: 400, status: 400 Bad Request,
  message: invalid character 'd' looking for beginning of value, body:
  data:{"error":{"code":"ModelArts.81001",
    "message":"Inference failed: Prompt length exceeds:
               the prompt length 200439 must less than the maximum input length 196608.
               Request failed with status: 400 BAD_REQUEST.",
    "param":null,"type":"BadRequest"},
    "error_code":"ModelArts.81001",
    "error_msg":"Inference failed: Prompt length exceeds:
                 the prompt length 200439 must less than the maximum input length 196608.
                 Request failed with status: 400 BAD_REQUEST.",
    "span_id":"eab7faf51fff2353e9618dc501223faa"}
```

逐行解读：

| 字段 | 值 | 含义 |
|------|-----|------|
| `status code` | 400 | HTTP 层面：请求不合法 |
| `code` / `error_code` | `ModelArts.81001` | 华为云 ModelArts 的错误码（说明中转站背后是华为云） |
| `prompt length` | 200439 | 你实际发了 200,439 token |
| `maximum input length` | 196608 | 模型窗口上限 196,608 token（192K） |
| `span_id` | `eab7faf5...` | 链路追踪 ID，排查用 |
| `invalid character 'd'` | — | 中转站返回的不是纯 JSON（前面多了 `data:` 前缀），解析器报错 |

**关键认知**：
- 超了 **3,831 token**（200439 - 196608），只超了约 2%，但就是不行——**窗口是硬上限，超 1 个 token 都报错**
- `ModelArts.81001` 暴露了中转站背后的云厂商——你以为是调 DeepSeek，实际请求跑在华为云 ModelArts 上
- `invalid character 'd'` 是中转站的副作用：它返回的 body 前面加了 `data:` 前缀（SSE 流式格式残留），导致 JSON 解析失败——**中转站不仅改模型路由，还改响应格式**

---

**另一种报错形式**（同一次会话中遇到的）：

```
⏺ API Error: 400 error, status code: 400, status: 400 Bad Request,
  message: The request is invalid: provider error:
  finish_reason=model_context_window_exceeded.
  Please check the request body, required fields, and request format.
```

这个报错和上面是**同一个问题，不同中转站的包装方式**：

| 对比 | 第一种报错 | 第二种报错 |
|------|-----------|-----------|
| **谁报的** | 底层模型（华为云 ModelArts） | 中转站自己 |
| **错误码** | `ModelArts.81001` | 无具体错误码 |
| **关键信息** | 给了精确的 prompt length 和 maximum | 只说 `finish_reason=model_context_window_exceeded` |
| **调试价值** | 高（知道超了多少） | 低（只知道超了，不知道超多少） |

**关键认知**：
- `finish_reason=model_context_window_exceeded` 是模型返回的**结束原因**，正常应该是 `stop`（正常结束）或 `tool_calls`（要调工具），出现这个说明模型还没开始生成就被截断了
- 中转站把这个 finish_reason 包装成 400 错误抛出来——**你看到的错误信息取决于中转站怎么包装，不取决于模型本身**
- 这就是为什么第3层兜底要用**模糊匹配**（检查 `context` 关键词），不能写死匹配某个错误码——不同中转站报错格式不同

### 为什么会超

Agent 的 messages 列表是**只增不减**的：

```
第1轮: system(200) + user(50) + assistant(100) = 350 token
第2轮: 350 + user(50) + tool_result(2000) + assistant(150) = 2550 token
第3轮: 2550 + user(50) + tool_result(3000) + assistant(200) = 5800 token
...
第N轮: 工具返回的文件内容、diff、代码越积越多 → 爆了
```

**罪魁祸首通常是工具返回值**——`read_file` 返回几千字符、`get_diff` 返回大段代码，每轮都 append 进 messages，永远不删。

### 不同模型的窗口上限

| 模型 | 上下文窗口 | 约 token 数 |
|------|-----------|------------|
| DeepSeek-V4-Flash | 192K | 196,608 |
| GPT-4o-mini | 128K | 131,072 |
| GPT-4o | 128K | 131,072 |
| Claude Sonnet 5 | 200K | 204,800 |
| Claude Haiku 4.5 | 200K | 204,800 |

⚠️ 中转站可能把你的请求路由到不同模型，窗口上限可能和你以为的不一样。

---

## 十一、三层防御：预估 → 检查 → 兜底

```
         ┌─────────────┐
         │  第1层：预估  │  发请求前估算，超 70% 就压缩
         └──────┬──────┘
                │ 估算没超（或压缩后）
         ┌──────▼──────┐
         │  第2层：检查  │  发送前再算一遍，确认没超
         └──────┬──────┘
                │ 确认 OK
         ┌──────▼──────┐
         │  发请求      │
         └──────┬──────┘
                │ 如果还是 400？
         ┌──────▼──────┐
         │  第3层：兜底  │  捕获报错 → 强制压缩 → 重试
         └─────────────┘
```

### 第1层：事前预估 + 主动压缩

```python
def should_compress(messages, model_window, threshold=0.7):
    """估算当前 messages 总 token，超阈值就压缩"""
    est = estimate_messages_tokens(messages, multiplier=1.3)
    return est > model_window * threshold

# 在 adapter.chat() 里：
if should_compress(messages, self.max_context):
    messages = compress_messages(messages)
```

**为什么用 70% 而不是 90% 或 100%**：
- 估算有误差（1.3 系数覆盖大部分，但不是全部）
- 模型还需要空间生成输出（输出也占窗口）
- 70% 阈值 + 1.3 系数 = 实际约 91% 才触发，留了 9% buffer

### 第2层：发送前最终检查

```python
def final_check(messages, model_window):
    """发送前的最后一道检查，用更保守的估算"""
    est = estimate_messages_tokens(messages, multiplier=1.5)  # 更保守
    if est > model_window * 0.9:
        messages = compress_messages(messages, aggressive=True)
    return messages
```

第2层用更保守的系数（1.5），因为这是最后机会——过了这关就真发请求了。

### 第3层：报错兜底 + 重试

```python
def chat_with_fallback(self, messages, tools=None, **kwargs):
    """带兜底的 chat：报错就压缩重试"""
    try:
        return self._raw_chat(messages, tools, **kwargs)
    except requests.exceptions.HTTPError as e:
        if e.response.status_code == 400 and "context" in e.response.text.lower():
            # 上下文超限 → 强制压缩 → 重试
            messages = compress_messages(messages, aggressive=True)
            return self._raw_chat(messages, tools, **kwargs)
        raise  # 其他错误不处理，往上抛
```

**为什么第3层必须存在**：不管第1、2层多保守，总有漏网情况——
- 中转站路由到窗口更小的模型
- 系数估算偏低
- 工具返回值突然很大

第3层是**安全网**，保证永远不会因为超限而直接崩溃。

---

## 十二、压缩策略：怎么压缩 messages

### 策略1：摘要压缩（推荐）

把旧对话轮次让模型总结成一段摘要，替换掉原始消息：

```python
def compress_messages(messages, keep_recent=4):
    """
    压缩 messages：
    - 保留 system prompt（第一条）
    - 保留最近 keep_recent 轮（不能压缩模型刚说的）
    - 中间的旧消息让模型总结成一段摘要
    """
    if len(messages) <= keep_recent + 1:  # system + 最近几轮，没东西可压缩
        return messages

    system = messages[0]  # system prompt
    recent = messages[-keep_recent:]  # 最近几轮
    old = messages[1:-keep_recent]    # 中间的旧消息

    # 让模型总结旧消息
    summary_prompt = {
        "role": "user",
        "content": f"请用2-3句话总结以下对话的关键信息（保留重要结论和决策，忽略细节）：\n\n"
                   + format_messages(old)
    }
    summary_response = call_model([system, summary_prompt])
    summary_msg = {
        "role": "assistant",
        "content": f"[历史对话摘要] {summary_response.message['content']}"
    }

    return [system, summary_msg] + recent
```

**优点**：保留语义，模型还能参考之前的结论
**缺点**：需要额外调一次模型做摘要（花 token + 延迟）

### 策略2：截断工具返回值（零成本，立竿见影）

工具返回值是膨胀主因，直接截断：

```python
def truncate_tool_results(messages, max_chars=1000):
    """截断工具返回值，只保留前 max_chars 字符"""
    result = []
    for msg in messages:
        if msg.get("role") == "tool" and len(msg.get("content", "")) > max_chars:
            truncated = msg["content"][:max_chars]
            result.append({**msg, "content": truncated + "\n...(已截断)"})
        else:
            result.append(msg)
    return result
```

**优点**：零成本、零延迟、立竿见影
**缺点**：丢失信息，模型可能看不到完整内容

### 策略3：滑动窗口（最简单）

只保留最近 N 轮对话，更早的直接丢弃：

```python
def sliding_window(messages, max_rounds=10):
    """只保留 system + 最近 max_rounds 轮"""
    system = messages[0]
    # 按轮次切分（一轮 = user + assistant/tool）
    rounds = split_into_rounds(messages[1:])
    if len(rounds) <= max_rounds:
        return messages
    kept = rounds[-max_rounds:]
    return [system] + flatten(kept)
```

**优点**：最简单，不需要额外调模型
**缺点**：完全丢失早期上下文，模型可能"失忆"

### 生产推荐：组合使用

```
1. 先截断工具返回值（零成本，减掉大头）
2. 如果还超 → 滑动窗口丢弃最旧轮次
3. 如果还超 → 摘要压缩（花 token 但保语义）
4. 如果还超 → 报错兜底，aggressive 压缩重试
```

---

## 十三、完整代码：带上下文管理的适配器

```python
class OpenAIAdapter(LLMAdapter):
    # 模型上下文窗口
    MODEL_WINDOWS = {
        "deepseek-v4-flash": 196608,
        "deepseek-chat": 196608,
        "gpt-4o-mini": 131072,
        "gpt-4o": 131072,
    }

    def __init__(self, api_key, model, base_url=None):
        # ... 原有初始化 ...
        self.max_context = self.MODEL_WINDOWS.get(model, 131072)  # 未知用 128K 保守

    def chat(self, messages, tools=None, temperature=0, **kwargs):
        # ── 第1层：事前预估 ──
        est = estimate_messages_tokens(messages, multiplier=1.3)
        if est > self.max_context * 0.7:
            messages = truncate_tool_results(messages)  # 先截断工具返回值
            est = estimate_messages_tokens(messages, multiplier=1.3)
            if est > self.max_context * 0.7:
                messages = compress_messages(messages)  # 还超就摘要压缩

        # ── 第2层：发送前检查 ──
        est = estimate_messages_tokens(messages, multiplier=1.5)
        if est > self.max_context * 0.9:
            messages = compress_messages(messages, aggressive=True)

        # ── 发请求 ──
        body = {"model": self.model, "messages": messages, "temperature": temperature}
        if tools:
            body["tools"] = tools
        resp = requests.post(self.url, headers=self.headers, json=body)

        # ── 第3层：报错兜底 ──
        if resp.status_code == 400 and "context" in resp.text.lower():
            messages = compress_messages(messages, aggressive=True)
            messages = truncate_tool_results(messages, max_chars=500)
            body["messages"] = messages
            resp = requests.post(self.url, headers=self.headers, json=body)

        resp.raise_for_status()
        # ... 后续处理同原版 ...
```

---

## 十四、面试要点（续）

7. **上下文超限错误**：`model_context_window_exceeded`，messages 只增不减导致，工具返回值是主因
8. **三层防御**：预估防（70%阈值）→ 发送前检查（90%阈值+更保守系数）→ 报错兜底（捕获400压缩重试）
9. **压缩三策略**：截断工具返回值（零成本）→ 滑动窗口（简单）→ 摘要压缩（保语义但花token）
10. **生产组合拳**：先截断 → 再滑动窗口 → 再摘要 → 最后兜底重试，逐级升级
11. **70%阈值设计**：不是拍脑袋——1.3系数×70%≈91%实际触发率，留9%给极端误差+输出空间
