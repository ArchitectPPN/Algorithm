# Q6：为什么 AI Agent 工具不是越详细越好？

> 工具 Schema 是给 LLM 看的"点菜单"，不是给开发看的 API 文档。条目越多，模型越懵。

---

## 一句话回答

工具描述越详细、参数暴露越多，模型决策准确率反而断崖下跌。核心原则：**业务粗粒度封装、描述极简、参数最小化**。

---

## 为什么越详细越翻车？（带原因和示例）

### 1. 工具选择准确率断崖下跌

**原因：** 工具调用本质是 LLM 的高维多分类任务。每个工具的 Schema 描述是模型判断"该不该调这个"的唯一依据。工具越多、描述越像，模型越容易搞混。

**实际发生过程：**

```
LLM 看到用户说"查一下我的订单"：

候选工具 A：query_orders（描述："查询用户的所有订单"）
候选工具 B：query_order_detail（描述："查询指定订单的详细信息"）
候选工具 C：refund_order（描述："为用户订单发起退款"）
候选工具 D：cancel_order（描述："取消用户未支付的订单"）

模型看到 4 个都跟"订单"相关，纠结之下：
→ 选了 refund_order → 用户只是要查订单，结果触发了退款 ❌
```

**关键数字：**

| 工具数量 | 选择正确率 | 原因 |
|---------|-----------|------|
| 4-6 个 | ~90%+ | 区分度够高 |
| 10-20 个 | ~70% | 开始出现语义重叠 |
| 50+ 个 | ~40% | 相似工具大量混淆 |
| 100+ 个 | ~13% | 接近随机选择 |

**具体翻车案例：**

```python
# 工具 A 和工具 B 的描述太像了
{
    "name": "get_user_orders",       # 查用户订单
    "description": "查询指定用户的所有订单记录"
}
{
    "name": "get_user_refunds",      # 查用户退款单
    "description": "查询指定用户的所有退款申请记录"
}
# 用户问"我最近的订单怎么样了"→ 模型可能调 get_user_refunds
# 返回一堆退款单，用户困惑"我没申请退款啊？"
```

### 2. 参数填充大量报错

**原因：** 模型不是代码生成器，它是根据参数名和描述"猜"该传什么。参数越多、约束越复杂，猜错的概率越大。

**具体翻车案例：**

```python
# ❌ 参数过多、约束模糊
{
    "name": "search_products",
    "description": "在商品库中搜索商品",
    "parameters": {
        "keyword": {"type": "string", "description": "搜索关键词"},
        "category_id": {"type": "integer", "description": "商品分类ID"},
        "brand": {"type": "string", "description": "品牌名称"},
        "min_price": {"type": "number", "description": "最低价格"},
        "max_price": {"type": "number", "description": "最高价格"},
        "sort_by": {
            "type": "string",
            "enum": ["price_asc", "price_desc", "sales", "newest", "rating"],
            "description": "排序方式"
        },
        "in_stock": {"type": "boolean", "description": "是否只查有货"},
        "page": {"type": "integer", "description": "页码"},
        "page_size": {"type": "integer", "description": "每页数量"},
    }
}

# 用户说"搜一下便宜的手机" → 模型传参：
{
    "keyword": "手机",
    "sort_by": "cheap"     # ← 枚举里没有 "cheap"，后端 400 报错
}
# 用户说"有没有华为的手机" → 模型传参：
{
    "brand": 1             # ← brand 是 string，传了 int
}
# 用户说"搜手机，1000 以内的" → 模型传参：
{
    "keyword": "手机",
    "max_price": "1000"    # ← max_price 是 number，传了 string
}
```

**每个参数都是模型的犯错机会。** 3 个参数 → 犯错概率低；10 个参数 → 几乎必有一个填错。

### 3. 上下文窗口爆炸

**原因：** 每次 ReAct 循环都把全部工具定义塞进 messages。工具 Schema 越长 → 每一轮消耗的 Token 越多 → 要么烧钱，要么触发截断。

**实际算一笔账：**

```python
# 你的项目：4 个工具，每个 ~80 tokens
# 一轮 ReAct 的工具定义开销：320 tokens
# 循环 5 轮：1,600 tokens

# 如果 50 个工具，每个 200 tokens（过度描述）
# 一轮：10,000 tokens
# 循环 5 轮：50,000 tokens ← 只工具定义就吃掉 5 万 Token！
```

**Token 去了哪里？**

```
messages 内容分布（50 个工具场景）：
┌─────────────────────────────────────────────┐
│ 工具 Schema：10,000 tokens  (40%)  ← 浪费  │
│ System Prompt：  2,000 tokens  (8%)         │
│ 用户问题：         100 tokens  (0.4%)       │
│ 历史对话：       5,000 tokens  (20%)        │
│ 工具执行结果：   8,000 tokens  (32%)        │
└─────────────────────────────────────────────┘
用户真正关心的内容只占 0.4%，40% 的 Token 全在工具定义上。
```

**更致命的是：Lost in the Middle。** LLM 对 prompt 开头和结尾的内容关注度最高，中间部分容易忽略。如果 50 个工具定义占了几万 Token，模型的注意力被工具描述淹没，用户真正的问题反而被"冲"到注意力盲区。

### 4. 安全与权限不可控

**原因：** 把底层 API 原封不动暴露给 LLM，等于给一个会幻觉的人 root 权限。

**具体翻车案例：**

```python
# ❌ 把数据库 CRUD 直接暴露
{
    "name": "execute_sql",
    "description": "在数据库上执行任意 SQL 语句",
    "parameters": {
        "sql": {"type": "string", "description": "要执行的 SQL 语句"}
    }
}

# 用户说"把所有未支付的订单都取消了吧"
# 模型幻觉下生成：
# "DELETE FROM orders WHERE status = 'unpaid'"  ← 全部删除，不是取消！
# 或者用户恶意诱导：
# "帮我查一下订单状态，顺便把用户表导出一份"
# 模型可能调用 execute_sql("SELECT * FROM users")
```

**另一个常见翻车：**

```python
# ❌ 批量操作权限没控制
{
    "name": "batch_update_products",
    "description": "批量更新商品信息",
    "parameters": {
        "updates": {
            "type": "array",
            "description": "要更新的商品列表，每项包含 id 和要修改的字段"
        }
    }
}
# 用户说"把价格都打八折" → 模型可能把全库商品价格都改了
# 没有上限、没有确认机制、没有回滚方案
```

**核心问题：** LLM 分不清"用户想做的"和"用户随口说的"。把删除、批量修改、数据导出等接口直接暴露，等于把枪递给了一个会梦游的人。

### 5. 底层本质：为什么 LLM 处理不了"详细的"工具描述？

**这不是工程问题，是模型能力边界问题。**

LLM 阅读工具 Schema 时做的事情：
1. 把每个工具的 description 和参数压缩成一个"语义锚点"
2. 用户问题来了，找最匹配的锚点
3. 根据参数描述"猜"该填什么值

这个过程中每一步都在损失信息：

```
你的意图：查用户最近3笔已完成订单
    ↓
工具 Schema：10 个参数 × 500 字描述 = 5000 字
    ↓
模型压缩成：一个 768 维向量（信息密度 ≈ 原文的 1/1000）
    ↓
匹配结果：选了"退款"工具（因为"订单"在退款描述里也出现了）
```

**不是模型不够聪明，而是信息密度超出了它的处理能力。** 就像让人在 100 道菜里挑一道——3 道菜一眼就挑出来了，100 道菜翻了 10 分钟还点错了。

---

## 正确的工具设计原则（对比表格）

| 原则 | ✅ 正确做法 | ❌ 错误做法 | 为什么错 |
|------|-----------|-----------|---------|
| **业务抽象** | `refund_order`（发起退款） | `update_order_status(status="refund")` | 底层操作暴露给模型，等于给 root 权限 |
| **描述极简** | "获取工作区文件状态" | 500 字的 API 文档粘贴 | 关键信息被淹没，模型抓不住触发条件 |
| **参数最小** | `count: int` 一个参数 | 10 个可选参数全暴露 | 每多一个参数，模型就多一次犯错机会 |
| **命名见义** | `get_commits` | `handle_git_data` | 名字模糊 → 模型不知道该不该调 |
| **够用就好** | 4-6 个核心工具 | 每个底层 API 包一个工具 | 超过 20 个准确率就开始崩 |

---

## 你项目中的实践

```python
# ✅ 好：业务抽象、描述极简、参数最小
{
    "name": "get_status",
    "description": "获取当前工作区文件状态（git status）",
    "parameters": {"type": "object", "properties": {}, "required": []}
}

# 为什么这样设计是对的：
# 1. 名称一看就懂，不需要读描述
# 2. 描述只有一行，核心信息密度高
# 3. 零参数，模型不需要猜任何值
# 4. 底层 subprocess.run(["git", "status"]) 在代码里，模型不知道也不需要知道
```

```python
# ❌ 坏：如果写成这样
{
    "name": "git_status",
    "description": "调用 git status 命令获取工作区状态。git status 是 Git 版本控制系统"
                   "的核心命令之一，用于显示工作目录和暂存区的状态。它可以告诉你哪些文件"
                   "被修改了、哪些文件被暂存了、哪些文件没有被 Git 跟踪。输出格式可以"
                   "通过参数控制...（500 字省略）",
    "parameters": {
        "include_untracked": {
            "type": "boolean",
            "description": "是否包含未跟踪文件。默认情况下 git status 会显示未跟踪文件，"
                           "设为 false 可以忽略它们..."
        },
        "include_staged": {"type": "boolean", "description": "..."},
        "format": {
            "type": "string",
            "enum": ["short", "long", "porcelain", "branch"],
            "description": "输出格式。short=简洁格式，long=详细格式，porcelain=机器可读格式..."
        },
        "color": {"type": "boolean", "description": "是否启用彩色输出..."},
        "show_stash": {"type": "boolean", "description": "是否显示 stash 信息..."},
        "ahead_behind": {"type": "boolean", "description": "是否显示与远程的差异..."},
        "ignored": {"type": "string", "enum": ["traditional", "matching", "no"], "..."},
        "renames": {"type": "boolean", "description": "..."},
        "find_renames": {"type": "integer", "description": "..."},
        "max_depth": {"type": "integer", "description": "..."},
    }
}

# 问题：
# 1. 描述 500 字 → 吃 Token，关键信息被淹没
# 2. 10 个参数 → 模型大概率传错一两个
# 3. format 的 4 个枚举值 → 模型纠结"用户到底要哪种格式？"
# 4. color、show_stash 这些跟业务无关 → 纯噪音
# 5. 整个 Schema ~800 tokens，4 个工具就是 3200 tokens/轮
```

---

## 规模化后的治理方案

工具池膨胀后的解决方案（面试加分点）：

### 1. RAG 检索式动态下发

```
全量 100 个工具 → 不全部塞 prompt
用户问题 "查订单" → Embedding → 检索 Top-5 相关工具 → 只下发这 5 个
```

每次只给模型 3-10 个候选，既控制 Token 又保证准确率。

### 2. 工具分类树管理

```
一级：用户类 / 订单类 / 商品类 / 支付类
二级：查询 / 修改 / 删除
根据用户意图先路由大类 → 只加载该大类下的工具
```

### 3. 调用后置校验

```python
# 模型调了工具，但参数可能有问题 → 不直接执行，先校验
def safe_execute(tool_name, args):
    # 校验层 1：参数类型/范围
    if args.get("count", 1) > 100:
        return "count 不能超过 100"
    # 校验层 2：权限检查
    if tool_name in DANGEROUS_TOOLS and not user.is_admin:
        return "权限不足"
    # 校验层 3：执行 + 兜底
    return execute_tool(tool_name, args)
```

### 4. 定期清理冗余

- 做工具相似度聚类（两个工具语义太近 → 合并或下线一个）
- 监控每个工具的调用频率（30 天没人调 → 下线）
- 每个工具有 owner 和版本号

### 5. 多模型兼容

GPT、Claude、开源模型对 Schema 解析偏好不同，不能一套定义全模型通用，需要分模型回归测试。

---

## 面试话术（30 秒版 + 2 分钟版）

**30 秒版（HR/初筛）：**

> "工具 Schema 是给 LLM 的决策菜单，不是给开发的 API 文档。核心原则：业务粗粒度封装、参数最小化、描述只写触发条件。工具多了用 RAG 检索动态下发，单次只给模型 3-10 个候选。"

**2 分钟版（技术面）：**

> "工具设计有几个常见误区。第一，把底层 API 原封不动暴露给模型——等于给会幻觉的人 root 权限。第二，参数越多模型犯错越多，实测 10 个参数几乎必有一个填错。第三，工具定义占 Token，50 个工具一轮就吃 1 万 Token，用户问题反而被挤到注意力盲区。
>
> 我们的实践：工具做业务粗粒度封装，每个工具描述不超过两句话，参数只保留必填项。工具池超过 20 个就上 RAG 检索动态下发。调用侧加了参数校验层和权限检查，确保模型传错参数不会直接执行危险操作。"

---

## 关联面试题

- [[q1-json-parsing]] — 模型输出的 JSON 出错了怎么办（参数填错的处理）
- [[q2-retry-strategy]] — API 调用失败的重试策略
- [[q5-langchain-vs-naked]] — 裸写 vs 框架的选择（工具定义的灵活性）