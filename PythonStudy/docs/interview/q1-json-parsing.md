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

**第 2 层：必须自己解析时，用 Schema 强约束**

```python
from pydantic import BaseModel, Field, ValidationError

class User(BaseModel):
    name: str
    age: int = Field(ge=0, le=150)

data = json.loads(text)
user = User(**data)  # 类型/范围错误这里抛
```

**第 3 层：容错解析**

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

**第 4 层：失败重试 + 自修复**

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

**第 5 层：流式场景的特殊处理**

- **攒完再 parse**（简单，但失去流式体验）
- **增量 JSON 解析器**（如 `ijson`、`partial-json`）
- **结构化流式**：Anthropic/OpenAI 的 tool_use 流式有标准事件

## 答题模板

> 这道题我会从五个层面回答：
>
> 1. **优先用 function calling**：模型 API 返回的 `tool_call.arguments` 已经是结构化对象，完全绕过 JSON 解析问题
> 2. **必须输出文本 JSON 时**：用 `response_format` 强约束 + Pydantic 校验
> 3. **容错清洗**：剥 markdown、定位首尾 `{}`、修复尾逗号
> 4. **失败重试**：把解析错误回喂模型，让它在错误上下文里自修复
> 5. **流式特殊处理**：tool_use 流式走 schema 增量事件
>
> 本质是承认「模型输出不可靠」，所以做多层防御，每层兜底上一层漏掉的情况。

## 加分项

- 主动提到「多层防御」思想
- 区分 function calling 和 response_format 的适用场景
- 提到流式场景的增量解析
- 用 Pydantic 做校验而非手写 if-else

## 追问预案

**Q: 如果模型 hallucinate 了 schema 里没有的字段怎么办？**
A: Pydantic 默认忽略额外字段，或配 `extra="forbid"` 严格拒绝

**Q: function calling 和 response_format 怎么选？**
A: 要触发后续动作用 function calling；只是要结构化数据用 response_format

**Q: 为什么不让模型直接输出「合法 JSON」提示词就够了？**
A: 提示词约束是软约束，模型可能遵守也可能不遵守，工程上不能依赖
