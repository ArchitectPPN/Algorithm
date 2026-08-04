# Q1：如何保证大模型输出可以解析的 JSON

**分类**：工程认知　**难度**：⭐⭐

## 考察点

表面问 JSON 解析，实际考察**「大模型输出是不可靠的」这个工程认知**。模型会漏字段、加 markdown 代码块、多输出解释文字、字符串里引号转义错误。所以核心思路是**多层防御**。

## 答题思路

### 一、先识别失败模式

模型输出 JSON 常见的坑：

1. **被 markdown 包裹**：` ```json\n{...}\n``` `
2. **前后带解释**：`好的，这是你要的：{...} 希望对你有帮助`
3. **字段缺失/类型错误**：声明 `age: int`，输出 `"age": "25"`
4. **多余的尾逗号**：`{"a": 1,}`（标准 JSON 不允许，但模型常犯）
5. **截断**：长输出被 max_tokens 截断，JSON 不完整
6. **引号/转义错误**：字符串里有未转义的双引号、换行
7. **幻觉字段**：编一个 schema 里没有的字段
8. **嵌套错误**：数组套对象套数组时括号不匹配

### 二、防御层次（从底到上）

**第 1 层：用原生 Function Calling，别自己解析**

最关键的答案。如果模型支持 tool calling / function calling，**就别让模型输出 JSON 文本**，走结构化 API：

```python
# OpenAI 兼容
tools = [{"type": "function", "function": {
    "name": "create_user",
    "parameters": {"type": "object", "properties": {...}}
}}]
# 返回的 tool_call.arguments 已经是解析好的 dict
```

完全绕过 JSON 解析问题。

**面试加分**：明确说「能用 function calling 就不要自己 parse JSON」，体现工程经验。

**注意**：本地部署的小模型（如 Ollama qwen2.5:3b）通常不支持 function calling，这时需要退到下面的 grammar + 容错解析方案。

**如何判断模型是否支持 function calling？**

不能假设模型支持，需要主动判断。三种方式：

1. **查文档**（最可靠）：主流厂商会明确标注。OpenAI GPT-4o/4.1、Claude、Gemini、通义千问 API、DeepSeek V3 都支持；Ollama 本地模型取决于具体模型，不能一概而论。

1. **运行时探测**（工程上最实用）：不确定时发一个带 `tools` 参数的测试请求，看返回结果：

```python
def supports_function_calling(model: str, base_url: str) -> bool:
    """探测模型是否支持 function calling"""
    try:
        resp = requests.post(f"{base_url}/api/chat", json={
            "model": model,
            "messages": [{"role": "user", "content": "test"}],
            "tools": [{
                "type": "function",
                "function": {
                    "name": "test_func",
                    "parameters": {"type": "object", "properties": {"x": {"type": "integer"}}}
                }
            }],
            "stream": False
        }, timeout=10)

        if resp.status_code == 400:
            return False
        # 兼容 Ollama 和 OpenAI 两种返回格式
        msg = data.get("message", data.get("choices", [{}])[0].get("message", {}))
        return "tool_calls" in msg
    except Exception:
        return False
```

1. **Ollama 特殊情况**：Ollama 有三种可能——报错（明确不支持）、**静默忽略**（最危险，你以为走了 function calling 实际没有）、部分支持。所以不仅要判断是否支持，还要验证是否真的生效。

**第 2 层：Grammar 约束（本地模型的核心手段）**

Ollama / llama.cpp 支持 `grammar` 参数（GBNF 语法），在**解码阶段**就限制 token 生成——比 Pydantic 更前置，从根源上拦截不合法输出。

**原理**：语言模型生成文本是逐 token 预测的，每一步模型输出所有 token 的概率分布，然后选概率最高的作为下一个 token。Grammar 在这个过程中插入一步**过滤**——把语法上不合法的 token 概率直接置零，模型只能在合法选项中选择。

举个例子，假设当前已生成 `{"scenario_id": "`，下一个 token：

| token    | prob | grammar check                            | result        |
|----------|------|------------------------------------------|---------------|
| `bite`   | 0.6  | ✅ `bite_alignment` starts with bite     | keep          |
| `22P6X3` | 0.3  | ❌ not in any scenario enum              | **filtered**  |
| `the`    | 0.05 | ❌ not in any scenario enum              | **filtered**  |

没有 grammar 时，模型有 30% 概率选到 `22P6X3`（把病例号当 scenario_id）；有 grammar 后，这个选项直接被过滤，模型只能在合法选项中选。

**类比**：没有 grammar 像给白纸让你自己写（可能写错）；`format: json` 像给模板要求写成 JSON（字段名随便填）；grammar 像**带下拉框的表单**——字段名只能从列表选，没有的字段根本不存在输入框。

```python
# 动态生成 grammar，约束 scenario_id 只能是预定义值
def build_grammar(scenario_ids: list[str]) -> str:
    scenario_options = " | ".join(f'"\\"{sid}\\""' for sid in scenario_ids + ["unknown"])
    return f'''
root ::= "{{" ws "\\"scenario_id\\"" ws ":" ws scenario ws "," ws "\\"case_id\\"" ws ":" ws string ws "," ws "\\"confidence\\"" ws ":" ws number ws "}}" ws
scenario ::= {scenario_options}
string ::= "\\"" [^\\"]* "\\""
number ::= [0-9]+ "." [0-9]+
ws ::= [ \\t\\n]*
'''

# 调用时传入
resp = requests.post(OLLAMA_URL, json={
    "model": "qwen2.5:3b",
    "prompt": prompt,
    "stream": False,
    "format": "json",
    "grammar": build_grammar(["bite_alignment", "qc_process"])  # 从 YAML 动态生成
})
```

**效果**：
- `scenario_id` 只能是预定义值，不可能输出病例号
- 只能有这 3 个字段，不可能多出 `action` 之类
- `confidence` 只能是 `0.0`-`1.0` 格式

**与 `format: json` 的区别**：`format: json` 只保证输出是合法 JSON；`grammar` 进一步约束 key 名、value 枚举、字段数量。

**各约束手段对比**：

1. **Prompt 提示词**（生成前，弱约束）：软约束，模型可能不遵守
1. **`format: json`**（解码时，中约束）：保证合法 JSON，不限制内容
1. **`grammar`**（解码时，强约束）：限制 key 名、value 枚举、字段数量
1. **Pydantic 校验**（生成后，强约束）：校验类型/范围，但已经生成了，只能报错或降级

Grammar 的独特价值：**错误在生成阶段就被阻止了，而不是生成完了再校验发现不对**。就像防患于未然 vs 事后检查。

**局限性**：

- 只适用于 llama.cpp / Ollama 等支持 grammar 的推理引擎，OpenAI API 不支持
- grammar 写错了会导致模型输出异常（卡死、重复），需要仔细测试
- 复杂嵌套结构的 grammar 写起来繁琐，维护成本高

**第 3 层：Schema 强约束（Pydantic 校验）**

```python
from pydantic import BaseModel, Field, ValidationError

class User(BaseModel):
    name: str
    age: int = Field(ge=0, le=150)

data = json.loads(text)
user = User(**data)  # 类型/范围错误这里抛
```

**第 4 层：容错解析**

```python
import json, re

def extract_json(text: str) -> dict:
    # 1. 剥 markdown 代码块
    text = re.sub(r"^```(?:json)?\s*", "", text.strip(), flags=re.M)
    text = re.sub(r"\s*```$", "", text.strip())
    # 2. 找第一个 { 到最后一个 }（容忍前后解释文字）
    start = text.find("{")
    end = text.rfind("}")
    if start == -1 or end == -1:
        raise ValueError("no JSON object found")
    raw = text[start:end+1]
    # 3. 尝试解析，失败则修复
    try:
        return json.loads(raw)
    except json.JSONDecodeError:
        raw = re.sub(r",\s*([}\]])", r"\1", raw)
        return json.loads(raw)
```

**第 5 层：失败重试 + 自修复（带终止策略）**

解析失败时，**把错误信息回喂给模型**让它修：

```python
for attempt in range(3):
    text = provider.chat(prompt)
    try:
        return parse_and_validate(text)
    except (json.JSONDecodeError, ValidationError) as e:
        prompt = f"上一次输出无法解析：{e}\n请只输出合法 JSON，不要任何解释。"
        continue
```

**重试必须有限制，不能无限循环**。重试 100 次还是失败，说明问题不在随机性，而是系统性错误——prompt 有问题、模型能力不够、或输入本身有歧义。继续重试只是浪费 token 和时间。

**终止策略三件套**：

1. **硬上限**：最多重试 N 次（通常 2-3 次），超过直接退出
2. **早停检测**：连续多次输出同样的错误，说明模型"卡住了"，立即停止
3. **降级出口**：重试失败后不是报错崩溃，而是走降级路径

```python
def identify_intent_with_fallback(user_message, scenarios, max_retries=2):
    last_error = None
    last_output = None
    repeat_count = 0

    for attempt in range(max_retries + 1):
        raw = call_ollama(user_message, scenarios)
        try:
            return parse_and_validate(raw)
        except (json.JSONDecodeError, ValidationError) as e:
            # 早停：连续输出相同结果，说明模型卡住了
            if raw == last_output:
                repeat_count += 1
                if repeat_count >= 2:
                    break
            else:
                repeat_count = 0
            last_output = raw
            last_error = e

            # 重试时把错误信息喂回去
            user_message = f"上一次输出解析失败：{e}\n原始消息：{user_message}"

    # 重试耗尽 → 降级
    return fallback(user_message, last_error)

def fallback(user_message, error):
    """降级策略：根据业务场景选择"""
    # 策略1：返回安全默认值（适合非关键场景）
    return IntentResult(scenario_id="unknown", case_id="", confidence=0.0)

    # 策略2：转人工（适合关键业务）
    # notify_human(f"意图识别失败：{user_message}，错误：{error}")

    # 策略3：换大模型重试（适合有备用模型的场景）
    # return call_bigger_model(user_message)
```

**降级策略怎么选**，取决于业务容忍度：

| 降级策略 | 适用场景 | 代价 |
|---------|---------|------|
| 返回安全默认值 | 非关键场景，漏识别可接受 | 用户需重新描述需求 |
| 转人工处理 | 关键业务，不能漏 | 需要人工介入机制 |
| 换大模型重试 | 有备用模型资源 | 延迟增加、成本增加 |

**面试加分**：主动说「重试不是目的，降级才是工程解」。重试是在争取成功，降级是承认失败后保证系统不崩。两者缺一不可。

**第 6 层：流式场景的特殊处理**

- **攒完再 parse**（简单，但失去流式体验）
- **增量 JSON 解析器**（如 `ijson`、`partial-json`）
- **结构化流式**：Anthropic/OpenAI 的 tool_use 流式有标准事件

## 答题模板

> 这道题我会从六个层面回答：
>
> 1. **优先用 function calling**：模型 API 返回的 `tool_call.arguments` 已经是结构化对象，完全绕过 JSON 解析问题。但本地小模型通常不支持，需要退到下面的方案
> 2. **Grammar 约束**：Ollama/llama.cpp 的 `grammar` 参数在解码阶段就限制 key 名和 value 枚举，从根源拦截不合法输出
> 3. **Schema 强约束**：Pydantic 校验字段类型、范围、枚举，`extra="forbid"` 拒绝幻觉字段
> 4. **容错清洗**：剥 markdown、定位首尾 `{}`、修复尾逗号
> 5. **失败重试 + 自修复**：把解析错误回喂模型，让它在错误上下文里自修复。但重试必须有硬上限 + 早停检测，不能无限循环
> 6. **降级出口**：重试耗尽后走降级——返回安全默认值、转人工、或换大模型。重试是争取成功，降级是保证系统不崩
>
> 本质是承认「模型输出不可靠」，所以做多层防御，每层兜底上一层漏掉的情况。重试不是目的，降级才是工程解。

## 加分项

- 主动提到「多层防御」思想
- 区分 function calling 和 response_format 的适用场景
- 提到 grammar 约束（本地模型场景的加分项，体现部署深度）
- 提到流式场景的增量解析
- 用 Pydantic 做校验而非手写 if-else
- 主动提到重试终止策略（硬上限 + 早停）和降级出口
- 说「重试不是目的，降级才是工程解」

## 追问预案

**Q: 如果模型 hallucinate 了 schema 里没有的字段怎么办？**
A: Pydantic 默认忽略额外字段，或配 `extra="forbid"` 严格拒绝

**Q: function calling 和 response_format 怎么选？**
A: 要触发后续动作用 function calling；只是要结构化数据用 response_format

**Q: 为什么不让模型直接输出「合法 JSON」提示词就够了？**
A: 提示词约束是软约束，模型可能遵守也可能不遵守，工程上不能依赖

**Q: 本地部署的小模型不支持 function calling，怎么办？**
A: 退到 grammar + pydantic + 容错解析的组合。grammar 在解码阶段约束输出格式，pydantic 做运行时校验兜底，容错解析处理边界情况。这是本地模型场景下的标准方案。

**Q: 怎么判断模型是否支持 function calling？**
A: 三种方式——查文档（最可靠）、运行时探测（发带 tools 的测试请求看返回）、注意 Ollama 等本地部署可能静默忽略 tools 参数（最危险，你以为走了 function calling 实际没有）。工程上建议启动时做一次探测，缓存结果。

**Q: 重试多次还是失败怎么办？**
A: 重试必须有终止条件——硬上限（最多 2-3 次）+ 早停检测（连续输出相同错误就停）。重试耗尽后走降级：非关键场景返回安全默认值，关键场景转人工，有条件的话换大模型重试。核心原则是「重试是争取成功，降级是保证系统不崩」。
