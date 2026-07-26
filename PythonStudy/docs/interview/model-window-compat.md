# 模型窗口兼容性专题

> 这是面试题整理的专题文档之一，专门收录"不同模型窗口差异下 Agent 框架如何兼容"相关题目。
>
> ## 维护方式
>
> - 题号延续主文档全局编号（如主文档到 Q4，本专题从 Q5 开始）
> - 新增题目：在「题目索引」表加一行，并在文件末尾追加详情
> - 主文档 `README.md` 顶部「专题文档」节会指向本文件
>
> ## 题目索引
>
> | 题号 | 题目 | 分类 | 难度 | 状态 |
> |------|------|------|------|------|
> | Q5 | 不同模型窗口差异巨大（如 GLM-5.2 1M vs 5.1 200K），ClaudeCode/Cursor 如何处理 | 上下文管理 | ⭐⭐⭐ | ✅ 已整理 |
>
> ---
>
> ## 题目详情

## Q5：不同模型窗口差异巨大（如 GLM-5.2 1M vs 5.1 200K），ClaudeCode/Cursor 如何处理

**分类**：上下文管理　**难度**：⭐⭐⭐

### 考察点

考察**「不同模型窗口差异巨大时，Agent 框架如何兼容」**——本质是**模型抽象层**的设计问题。窗口从 200K 到 1M 差 5 倍，但 Agent 框架不能写死，必须考虑：不同模型窗口不同、同模型不同版本窗口可能变、用户随时切模型、同一会话里也可能切。框架必须**根据当前模型的窗口动态调整所有上下文策略**。

### 答题思路

#### 一、问题本质

窗口从 200K 到 1M 差 5 倍，但 Agent 框架不能写死，必须考虑：

1. **不同模型窗口不同**：GLM-5.2 是 1M、GLM-5.1 是 200K、Claude Sonnet 4.6 是 200K、Gemini 2.5 是 1M、GPT-4o 是 128K
2. **同模型不同版本窗口可能变**：GLM-5.1 → 5.2 升级后窗口从 200K 涨到 1M
3. **用户随时切模型**：Claude Code 的 `/model` 命令、Cursor 的模型选择器，切换是即时的
4. **同一个会话里也可能切**：中途切到更强或更便宜的模型

所以框架必须**根据当前模型的窗口动态调整所有上下文策略**。

#### 二、Claude Code 怎么处理

**1. Provider 层暴露窗口元信息**

每个 Provider 在初始化时声明该模型的窗口大小和上下文策略参数：

```python
# Claude Code 的 Provider 抽象（示意）
class BaseProvider:
    def get_model_info(self) -> ModelInfo:
        return ModelInfo(
            name="claude-sonnet-4-6",
            context_window=200_000,
            max_output=8192,
            supports_prompt_cache=True,
            cache_threshold=1024,  # 超过 1024 token 才走 cache
        )
```

切换模型时，框架**重新读取这个元信息**，所有依赖窗口的逻辑都拿到新值。

**2. 上下文策略与模型绑定**

Claude Code 的压缩阈值不是写死 92%，而是**按当前模型窗口的百分比计算**：

```python
def get_compress_threshold(model_info) -> int:
    # 基础阈值 92%，但小窗口模型会更保守
    ratio = 0.92 if model_info.context_window >= 200_000 else 0.80
    return int(model_info.context_window * ratio)
```

- 200K 窗口 → 阈值 ~184K
- 128K 窗口 → 阈值 ~102K（保守一点，留更多缓冲）
- 1M 窗口 → 阈值 ~920K

**3. 模型能力差异的适配**

不光是窗口大小，还有**能力差异**：

| 能力 | 处理方式 |
|------|---------|
| 上下文窗口 | 阈值按百分比算 |
| 是否支持 prompt cache | 不支持就降级到普通请求 |
| 是否支持 vision | 不支持就把图片转文本描述 |
| 是否支持 tool calling | 不支持就走 ReAct 模式（文本解析） |
| max_output | 控制单次输出长度上限 |

**4. 模型切换时的上下文继承**

切换模型时，Claude Code 会：

1. 评估当前上下文是否超过新模型窗口 → 超了先压缩
2. 转换历史消息格式（不同 Provider 的 tool_call 格式不同，需要重写）
3. 提示用户"已切换到 X 模型，部分历史可能丢失"

Agent 项目 `providers/base.py` 已经有 `BaseProvider` 抽象，但**没把窗口大小作为 Provider 元信息暴露**——这是可以补的点。

#### 三、Cursor 怎么处理

**1. 模型分层使用**

Cursor 不是"一个模型干所有事"，而是**按任务选模型**：

- **Tab 补全**：用小模型（Cursor Tab、GPT-4o-mini），快、便宜
- **Chat 短问答**：中等窗口模型（128K-200K）
- **Chat 长上下文**：用户选大窗口模型（Gemini 2.5 的 1M、Claude 的 200K）
- **Agent 模式**：默认用 Claude Sonnet（200K），遇到长上下文需求让用户切

**2. 按窗口大小路由**

Cursor 内部有**模型路由**机制：

```
用户问题 + 上下文预估 → 选模型
- 上下文 < 30K → 小模型（快、便宜）
- 30K-200K → 中等模型（Claude/GPT-4o）
- > 200K → 大窗口模型（Gemini 2.5、GLM-5.2）
```

这就是为什么 Cursor 能"无缝"切换——用户感觉不到模型切换，框架按需路由。

**3. 上下文预评估**

Cursor 在发送前**预估上下文 token 数**，如果超过当前模型窗口：

1. 提示用户"当前上下文 X token，需要切到 Y 模型才能处理"
2. 或自动启用压缩
3. 或拒绝执行并提示用户清理

**4. 仓库索引的窗口无关性**

Cursor 的**向量检索代码库**机制让它**不受单模型窗口限制**：

- 仓库做 embedding 索引（独立存储，不占模型上下文）
- 不管用哪个模型，都先检索 top-k 相关代码
- 模型只需要处理"问题 + 检索结果"，上下文始终小

所以 Cursor 在 GLM-5.1（200K）和 GLM-5.2（1M）下**代码库理解能力差不多**——因为不靠把仓库塞进上下文。

#### 四、关键设计原则

总结 Claude Code 和 Cursor 处理窗口差异的核心原则：

**1. 元信息驱动，不写死**

所有依赖窗口的参数（阈值、保留轮数、工具结果大小上限）都**从模型元信息推导**，不在代码里写死数字。

**2. 百分比而非绝对值**

阈值用百分比（如 70%、92%）而不是绝对 token 数，这样自动适配不同窗口。

**3. 能力探测而非版本判断**

不要写 `if model == "glm-5.2"`，而是检查 `model_info.context_window >= 1_000_000`。这样**新模型接入时不用改代码**。

**4. 分层路由**

- **大模型做难的事**（长上下文、复杂推理）
- **小模型做简单的事**（补全、短问答、摘要）
- 路由层根据任务特征选模型

**5. 上下文与模型解耦**

历史消息、工具结果、文件缓存这些**上下文数据**独立存储，切换模型时只重新组装，不丢失。

#### 五、对比表

| 维度 | Claude Code | Cursor |
|------|-------------|--------|
| 模型窗口元信息 | Provider 层暴露 | 路由层维护 |
| 阈值策略 | 按窗口百分比（92%） | 按任务路由不同模型 |
| 模型切换 | 用户手动 `/model` | 框架自动路由 |
| 长上下文方案 | 子 agent + 压缩 | 向量检索 + 模型路由 |
| 切模型时上下文 | 评估并转换格式 | 重新组装 |
| 多模型并行 | ❌（一次一个） | ✅（不同任务不同模型） |
| 能力探测 | 检查 flags | 检查能力矩阵 |

### 答题模板

> 这个问题的本质是**模型抽象层设计**。Claude Code 和 Cursor 都遵循几个原则：
>
> 1. **元信息驱动**：Provider 层暴露模型的 `context_window`、`max_output`、`supports_cache` 等能力，所有依赖窗口的逻辑都从元信息推导，不写死
>
> 2. **百分比阈值**：压缩阈值用百分比（如 92%）而非绝对 token，自动适配 200K 和 1M 模型
>
> 3. **能力探测而非版本判断**：不写 `if model == "glm-5.2"`，而是检查能力标志，新模型接入零改动
>
> 4. **切换时评估**：切模型时检查当前上下文是否超新模型窗口，超了先压缩再切换
>
> 差异：Claude Code 是**单模型 + 用户手动切换**模式，更依赖压缩和子 agent；Cursor 是**多模型路由**模式，按任务特征自动选模型，并靠向量检索让代码库理解不受单模型窗口限制。
>
> 本质是：把"窗口大小"作为模型能力的一部分抽象出来，业务代码只跟抽象打交道，不跟具体模型耦合。

### 加分项

- 明确点出这是「模型抽象层设计」问题，而非单纯的上下文问题
- 区分 Claude Code 的「单模型手动切换」 vs Cursor 的「多模型自动路由」两种范式
- 提到能力探测优于版本判断——新模型接入零改动
- 提到 Cursor 的向量检索让代码库理解不受窗口限制，体现对架构差异的深度理解
- 提到切换模型时历史消息格式转换（统一抽象层）

### 追问预案

**Q: 切模型时历史消息格式不一样怎么办？**

A: 不同 Provider 的 tool_call 格式不同（OpenAI 用 `function`，Anthropic 用 `tool_use`），切换时要**统一抽象层转换**：

- 内部维护**统一 Message 格式**（Agent 项目 `core/message.py` 就是）
- Provider 在发送时把统一格式转成自己的 API 格式
- 切换模型时，历史消息已经是统一格式，新 Provider 自己转换

**Q: 如果当前模型不支持 prompt cache，切到支持的就省钱吗？**

A: 是的，但要看历史是否还能被 cache：
- 历史消息格式相同 → cache 命中
- 历史里有上次模型特有的字段 → cache 失效
- 所以切模型后**前几次请求不命中 cache**，之后稳定下来才省钱

**Q: 用户在 1M 窗口模型下塞了 800K，切到 200K 模型怎么办？**

A: 三种策略：
1. **拒绝切换**：提示用户"当前上下文 800K 超过新模型 200K 窗口，请先压缩或清理"
2. **自动压缩后切换**：调一次摘要把历史压到 150K，再切
3. **截断切换**：保留最近 N 轮，丢弃早期（最差体验）

生产实践通常选 1 或 2。

**Q: 多模型并行处理同一任务，结果怎么合并？**

A: Cursor 不真的并行处理同一任务，而是**任务分发**：
- 补全任务 → 小模型
- 问答任务 → 中模型
- 长上下文任务 → 大模型
- 各做各的，不存在合并问题

真正的多模型协同（如 Mixture of Agents）那是另一个话题。

**Q: 不同模型 tool calling 兼容性怎么处理？**

A: 三层兜底：
1. **首选**：原生 function calling（OpenAI/Anthropic 都支持）
2. **次选**：模型不支持原生 tool calling 时，用 ReAct 模式——把工具定义写到 prompt 里，让模型输出 JSON，自己解析
3. **末选**：连 JSON 都输出不稳定的模型，限定为"只读"工具（Read/Grep/Glob），不让它执行写操作

### 代码示例

模型能力元信息 + 动态阈值 + 切换适配的完整实现：

```python
from dataclasses import dataclass, field
from typing import List, Optional


@dataclass
class ModelInfo:
    """模型能力元信息——所有窗口相关策略都从这里推导"""
    name: str
    context_window: int
    max_output: int
    supports_prompt_cache: bool = False
    supports_vision: bool = False
    supports_tool_calling: bool = True
    cache_threshold: int = 1024  # 超过这个 token 数才走 cache


# 模型注册表——新增模型只改这里，业务代码零改动
MODEL_REGISTRY = {
    "glm-5.2": ModelInfo(
        name="glm-5.2", context_window=1_000_000, max_output=8192,
        supports_prompt_cache=True, supports_vision=True,
    ),
    "glm-5.1": ModelInfo(
        name="glm-5.1", context_window=200_000, max_output=8192,
        supports_prompt_cache=True, supports_vision=False,
    ),
    "claude-sonnet-4-6": ModelInfo(
        name="claude-sonnet-4-6", context_window=200_000, max_output=8192,
        supports_prompt_cache=True, supports_vision=True,
    ),
    "gpt-4o": ModelInfo(
        name="gpt-4o", context_window=128_000, max_output=16384,
        supports_prompt_cache=False, supports_vision=True,
    ),
}


class ContextPolicy:
    """上下文策略——所有参数从 ModelInfo 推导，不写死"""

    def __init__(self, model_info: ModelInfo):
        self.model = model_info

    @property
    def compress_threshold(self) -> int:
        """压缩阈值：按百分比算，小窗口更保守"""
        ratio = 0.92 if self.model.context_window >= 200_000 else 0.80
        return int(self.model.context_window * ratio)

    @property
    def warn_threshold(self) -> int:
        """预警阈值"""
        return int(self.model.context_window * 0.6)

    @property
    def max_tool_result_tokens(self) -> int:
        """单次工具结果最大 token——大窗口可以放宽"""
        if self.model.context_window >= 500_000:
            return 10_000
        return 2_000

    @property
    def keep_recent_rounds(self) -> int:
        """压缩时保留最近 N 轮——大窗口多保留"""
        if self.model.context_window >= 500_000:
            return 30
        return 10

    @property
    def use_cache(self) -> bool:
        return self.model.supports_prompt_cache


class ModelSwitcher:
    """模型切换器：切模型时评估上下文、转换格式"""

    def __init__(self, current_model: str):
        self.current = MODEL_REGISTRY[current_model]
        self.policy = ContextPolicy(self.current)

    def switch(self, new_model: str, messages: list) -> tuple[bool, str, list]:
        """切换模型，返回 (是否成功, 提示, 处理后的 messages)"""
        if new_model not in MODEL_REGISTRY:
            return False, f"未知模型: {new_model}", messages

        new_info = MODEL_REGISTRY[new_model]
        new_policy = ContextPolicy(new_info)

        # 1. 评估当前上下文 token 数
        current_tokens = self._estimate_tokens(messages)

        # 2. 超过新窗口 → 必须先压缩
        if current_tokens > new_policy.compress_threshold:
            if current_tokens > new_info.context_window:
                # 超过新模型窗口，必须压缩
                messages = self._compress(messages, new_policy)
                msg = (f"当前上下文 {current_tokens} 超过 {new_model} 窗口 "
                       f"{new_info.context_window}，已自动压缩")
            else:
                msg = (f"已切换到 {new_model}，建议清理早期对话")

        # 3. 格式转换（不同 Provider 的 tool_call 格式不同）
        # 实际由各 Provider 在发送时转换，这里只更新策略
        self.current = new_info
        self.policy = new_policy

        # 4. 能力降级提示
        warnings = []
        if not new_info.supports_vision and self._has_images(messages):
            warnings.append("新模型不支持图片，已过滤图片消息")
            messages = self._strip_images(messages)
        if not new_info.supports_tool_calling:
            warnings.append("新模型不支持原生 tool calling，降级为 ReAct 模式")

        full_msg = msg + ("；" + "；".join(warnings) if warnings else "")
        return True, full_msg, messages

    def _estimate_tokens(self, messages: list) -> int:
        return sum(len(str(m)) // 3 for m in messages)

    def _compress(self, messages: list, policy: ContextPolicy) -> list:
        """调用 provider 做摘要（这里简化）"""
        keep = policy.keep_recent_rounds * 2
        return messages[-keep:]

    def _has_images(self, messages: list) -> bool:
        return any("image" in str(m) for m in messages)

    def _strip_images(self, messages: list) -> list:
        return [m for m in messages if "image" not in str(m)]


# 使用示例
switcher = ModelSwitcher("glm-5.2")  # 1M 窗口
# 用户在 1M 模型下积累了大量上下文
messages = [...]  # 假设 800K tokens

# 切到 200K 模型
ok, msg, messages = switcher.switch("glm-5.1", messages)
# → 触发压缩，msg 提示 "已自动压缩"
```

---

## 补充：窗口大小的「标称」与「实际可用」

### 一、模型厂商确实会"声明"窗口大小

每个模型发布时都会公布一个 context window 数字，这是**官方标称值**：

| 模型 | 标称窗口 |
|------|---------|
| GLM-5.2 | 1M |
| GLM-5.1 | 200K |
| Claude Sonnet 4.6 | 200K |
| Gemini 2.5 Pro | 1M |
| GPT-4o | 128K |

这个数字写在模型文档和 API 说明里。Agent 框架接入新模型时，**先查文档把这个数字填进模型注册表**（`MODEL_REGISTRY`）。

### 二、但"标称"不等于"实际可用"

这是关键坑。标称值是**硬上限**，但实际能用多少受几个因素限制：

1. **厂商限流**：很多平台对单次请求有更低的 token 限制，比如 ModelScope/OpenRouter 可能限到 32K/64K，即使底层模型支持 1M
2. **max_output 占用**：1M 窗口里要给输出留位置，输入实际能用的约 = 窗口 - max_output
3. **效果衰减**：标称 1M 不代表 1M 范围内效果一致，长上下文召回精度会下降（参考 Q3）
4. **价格档位**：有些模型厂商把上下文分级收费，1M 窗口可能要额外申请或付费

所以 Agent 框架在用模型时，**实际策略阈值**通常比标称窗口保守：

```python
# 标称 1M，实际压缩阈值设到 70%-92%
compress_threshold = int(1_000_000 * 0.85)  # ~850K
```

### 三、获取窗口大小的几种方式（按可靠度排序）

| 方式 | 可靠度 | 说明 |
|------|--------|------|
| **API 文档** | ⭐⭐⭐ | 厂商官方公布的数字，硬上限 |
| **API `/models` 接口** | ⭐⭐⭐ | OpenAI/Anthropic 提供 `GET /v1/models` 返回每个模型的 `context_window` |
| **配置文件硬编码** | ⭐⭐ | 框架自己维护模型注册表，新模型要手动加 |
| **API 响应的 usage 字段** | ⭐ | 后知后觉，每次响应才告诉实际用了多少 token |

**最佳实践**：配置文件硬编码 + API `/models` 校验。框架启动时拉一次 `/models`，对比本地注册表，不一致就告警。

### 四、所以 Agent 框架怎么用这个信息

**正因为模型配置已经指明了窗口，框架才能做到「元信息驱动」**：

1. 启动时加载模型注册表（含每个模型的 `context_window`）
2. 当前模型的窗口 → 推导出压缩阈值、工具结果上限、保留轮数等策略
3. 切模型时 → 重新读新模型的窗口，所有策略自动调整
4. 业务代码**不写任何具体数字**，全从元信息推导

这就是为什么 Q5 强调「不要写 `if model == "glm-5.2"`」——应该写 `if model.context_window >= 1_000_000`。这样框架和具体模型解耦，新模型来了只要在注册表加一行，**业务逻辑零改动**。

