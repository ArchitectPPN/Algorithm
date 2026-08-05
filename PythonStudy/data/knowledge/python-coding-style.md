# Python 编码规范

## 命名规范

- 变量和函数：使用 snake_case，如 `user_name`、`get_user_info()`
- 类名：使用 PascalCase，如 `UserService`、`HttpRequest`
- 常量：全大写 + 下划线，如 `MAX_RETRY_COUNT`、`DEFAULT_TIMEOUT`
- 私有属性：单下划线前缀，如 `_internal_cache`
- 避免使用单字母变量名（循环计数器 i/j/k 除外）

## 缩进与格式

- 统一使用 4 个空格缩进，禁止 Tab
- 每行不超过 120 个字符
- 函数之间空 2 行，类方法之间空 1 行
- 导入顺序：标准库 → 第三方库 → 本地模块，各组之间空 1 行

## 注释规范

- 函数必须有 docstring，说明参数、返回值、可能抛出的异常
- 复杂逻辑必须加行内注释，解释"为什么"而不是"做了什么"
- 避免无意义的注释，如 `# 赋值` 或 `# 循环`
- TODO 注释格式：`# TODO(作者): 描述`

## 类型注解

- Python 3.6+ 项目必须使用类型注解
- 函数参数和返回值都要标注类型
- 使用 `Optional[X]` 表示可能为 None 的参数
- 复杂类型用 `TypedDict` 或 `dataclass` 代替裸 dict

## 错误处理

- 不要用裸 `except:`，至少写 `except Exception:`
- 捕获具体异常，如 `except FileNotFoundError:`
- 异常信息要包含上下文，如 `raise ValueError(f"无效的用户ID: {user_id}")`
- 使用 `logging` 记录异常，不要只 print

## 代码质量

- 使用 `flake8` 或 `ruff` 做静态检查
- 使用 `black` 做自动格式化
- 提交前必须通过 lint 检查
- 测试覆盖率不低于 80%
