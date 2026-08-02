# Python import 机制

> 函数带着家跑，到哪都能找到自己的东西。

---

## 一句话

`import` 不是把代码粘贴进来，而是创建一个**模块对象**，函数通过 `__globals__` 记住自己出生的模块，调用依赖时永远回"老家"找。

---

## import vs PHP require

| | PHP `require` | Python `import` |
|---|---|---|
| 作用方式 | 把文件内容"粘贴"到当前位置 | 创建一个模块对象 |
| 可见性 | 被引入文件里所有东西都可用 | 只暴露你导入的那个名字 |
| 重复引入 | 可能报重复定义错误 | 自动跳过（只加载一次） |
| 命名空间 | 没有，全在全局 | 模块名作为前缀隔离 |

```php
// PHP: 全摊在一张桌子上
require 'agent_service.php';
run_agent(...);  // ✅
call_llm(...);   // ✅ 也能用，全暴露了
```

```python
# Python: 只拿你要的那把钥匙
from .agent_service import run_agent
run_agent(...)   # ✅
call_llm(...)    # ❌ NameError，没导入
```

---

## 函数带着家跑

```python
# agent_service.py
TOOLS_SCHEMA = [...]                    # 模块级变量

def call_llm(messages, config):         # 模块内函数
    ...

def run_agent(question, repo_path):     # 入口函数
    resp = call_llm(messages, config)   # 调用同模块的 call_llm
    ...
```

```python
# main.py
from .agent_service import run_agent    # 只导入 run_agent

run_agent("这个仓库有什么功能？", ".")   # ✅ 正常运行
# run_agent 内部调用 call_llm，call_llm 引用 TOOLS_SCHEMA
# 这些依赖都在 agent_service.py 的命名空间里，不需要 main.py 操心
```

**原理：** 每个函数对象有一个 `__globals__` 属性，指向它被定义时所在模块的全局命名空间。

```
main.py                            agent_service.py
┌─────────────────┐                ┌─────────────────────────┐
│                 │    import     │ run_agent ──────┐       │
│  run_agent() ───┼──────────────→│   .__globals__ = agent_service 的命名空间
│                 │                │                 │       │
│                 │                │ call_llm() ←────┘       │
│                 │                │ TOOLS_SCHEMA            │
│                 │                │ execute_tool()          │
└─────────────────┘                └─────────────────────────┘
```

无论 `run_agent` 被谁调用、从哪里调用，它找依赖时永远回 `agent_service.py` 那个"老家"找。

---

## 三种 import 写法

```python
# 1. 导入整个模块 —— 通过模块名访问
from . import agent_service
agent_service.run_agent(...)
agent_service.call_llm(...)      # 都能用

# 2. 导入指定名字 —— 最常用，明确依赖
from .agent_service import run_agent
run_agent(...)

# 3. 导入所有公开名字 —— 不推荐，容易命名冲突
from .agent_service import *
```

---

## 相对导入 vs 绝对导入

```python
# 相对导入（用 . 表示当前包）
from .agent_service import run_agent     # 同目录下的 agent_service.py
from ..tools import git_tools            # 上级目录的 tools/git_tools.py

# 绝对导入（从项目根开始）
from myagent.api.agent_service import run_agent
```

包内模块之间推荐用相对导入，因为移动整个包时路径不会断。

---

## import 只执行一次

同一个模块不管被 import 多少次，Python 只加载并执行一次，后续 import 直接返回缓存：

```python
# a.py
print("a 被加载了")

# b.py
import a   # 打印 "a 被加载了"
import a   # 不打印（缓存命中）
```

模块级代码（类定义、函数定义、模块变量赋值）只在第一次 import 时执行。

---

## 常见 import 错误

| 错误 | 原因 | 解决 |
|------|------|------|
| `ModuleNotFoundError: No module named 'xxx'` | 模块不在 `sys.path` 里 | 检查路径，或 `sys.path.insert` |
| `ImportError: attempted relative import with no known parent package` | 直接 `python xxx.py` 运行了包内模块 | 用 `python -m 包.模块` 方式运行 |
| `AttributeError: module 'xxx' has no attribute 'yyy'` | 导入的名字不存在 | 检查拼写，或该名字定义在别处 |

---

## 回顾

- `import` ≠ 粘贴代码，是创建模块对象
- 函数记住出生地，不记住调用地
- 只导入你需要的，不要 `import *`