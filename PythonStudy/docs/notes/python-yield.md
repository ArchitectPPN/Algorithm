# Python yield 与多语言对比

> yield 的作用：**把控制权交出去，用完了再回来。**

---

## 一句话

`yield` 是一个断点——跑到这里暂停，把值交给调用方，等调用方用完后再回来继续执行下一行。

---

## 三语言 yield 总览

三个语言都有 `yield` 关键字，但干的活**完全不一样**：

| 语言 | `yield` 用途 | 出现版本 |
|------|-------------|---------|
| **Python** | 生成器（产出多个值）+ FastAPI 拿来做依赖注入/资源清理 | Python 2.2 |
| **PHP** | 生成器（产出多个值），内存友好的迭代 | PHP 5.5 |
| **Java** | switch 表达式返回值，跟生成器毫无关系 | Java 14 |

同名不同命。

---

## Python yield

### 1. 生成器（产生多个值）

```python
def count_up_to(n):
    i = 1
    while i <= n:
        yield i       # 每次暂停，返回一个值
        i += 1

for num in count_up_to(3):
    print(num)
# 输出：1 2 3
```

### 2. 资源管理（Depends 的用法）

```python
def get_db():
    db = connect_db()
    try:
        yield db       # 交出资源
    finally:
        db.close()     # 自动清理
```

**执行流程：**

```
请求进来
    │
    ▼
connect_db()  ← 打开连接
    │
    ▼
yield db      ← 暂停，把 db 交给路由函数
    │
    ▼
路由函数执行  ← 使用 db 干活
    │
    ▼
db.close()    ← 路由完了，回到 yield 之后清理
    │
    ▼
请求结束
```

```python
def get_db():
    print("1. 打开连接")
    db = "数据库连接"
    yield db                       # 暂停，交出 db
    print("3. 关闭连接")           # 回来继续

result = get_db()
print("2. 使用:", next(result))    # 拿到 db，开始用
print("4. 结束")
```

输出：
```
1. 打开连接
2. 使用: 数据库连接
3. 关闭连接       ← 注意：在用完之后才打印
4. 结束
```

### Depends + yield 常见场景

```python
# 数据库连接
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

# 获取当前用户
def get_current_user(token: str = Header(...)):
    user = decode_token(token)
    yield user
    # 请求结束后记录操作日志
    log_operation(user)

# 文件处理
def get_log_file():
    f = open("access.log", "a")
    try:
        yield f
    finally:
        f.close()
```

---

## PHP yield

PHP 的 `yield` **只能做生成器**，不能做资源管理。

```php
function countUpTo($n) {
    $i = 1;
    while ($i <= $n) {
        yield $i++;
    }
}

foreach (countUpTo(3) as $num) {
    echo $num;  // 1 2 3
}
```

PHP 要做"用完后自动清理"，只能手写 `try/finally` 或靠框架容器：

```php
// PHP 手工管理
function listTodos() {
    $db = new PDO('sqlite:data.db');
    try {
        return $db->query('SELECT * FROM todos')->fetchAll();
    } finally {
        $db = null;  // 手动关闭，每个路由都得写一遍
    }
}

// Laravel 容器——能自动注入，但不能自动清理
app()->singleton(Database::class, fn() => new Database('sqlite:data.db'));

Route::get('/todos', function (Database $db) {
    return $db->query('SELECT * FROM todos');
    // 请求结束，连接不会自动关
});
```

---

## Java yield

Java 的 `yield` 跟生成器**毫无关系**，只在 switch 表达式里返回一个值：

```java
// Java 14+：yield 只在 switch 里用
String result = switch (day) {
    case MONDAY, FRIDAY -> {
        System.out.println("工作日");
        yield "搬砖";           // 把 "搬砖" 返回给 result
    }
    case SUNDAY -> "休息";       // 单行可以省略 yield
    default -> "普通日子";
};
```

Java 的资源管理用的是 `try-with-resources`（Java 7+），比 Python yield 更简洁：

```java
// Java：try-with-resources，只要实现了 AutoCloseable 就能自动关
try (Connection conn = DriverManager.getConnection(url);
     Statement stmt = conn.createStatement()) {
    
    ResultSet rs = stmt.executeQuery("SELECT * FROM todos");
    // 用 conn 和 stmt 干活
    
} // 这里自动调用 conn.close() 和 stmt.close()，不需要 finally
```

---

## 三语言资源管理对比

| | Python | PHP | Java |
|---|---|---|---|
| 机制 | `yield` + `Depends` | 手写 `try/finally` 或靠框架 | `try-with-resources` |
| 自动清理 | ✅ finally 保证必执行 | ❌ 手动 | ✅ 自动调 `.close()` |
| 代码量 | 写 1 次 | N 个路由写 N 次 | 写 1 次 |
| 能注入配置/用户？ | ✅ `Depends` 通用 | 靠 Laravel 容器 | 靠 Spring DI |
| 语法复杂度 | 中等 | 简单但重复 | 最简洁 |

---

## yield 和 return 的区别

```python
def with_return():
    db = connect_db()
    return db
    # db.close()  ← 永远不会执行

def with_yield():
    db = connect_db()
    yield db
    db.close()      # ✅ 调用方用完后会执行
```

- `return`：一次性，出去了回不来
- `yield`：暂停交出，用完了回来继续
