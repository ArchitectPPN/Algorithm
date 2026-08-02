# Python 解包与 zip

> for 循环一次能解包多个对象。

---

## 元组解包

```python
# 三个变量一起赋值
tool_id, func_name, func_args = ("call_001", "get_commits", {"count": 5})
# 位置 0 → tool_id
# 位置 1 → func_name
# 位置 2 → func_args
```

PHP 等价写法：

```php
list($tool_id, $func_name, $func_args) = ["call_001", "get_commits", ["count" => 5]];
```

---

## zip 配对

把多个列表一一配对，同时遍历：

```python
names  = ["张三", "李四", "王五"]
scores = [90, 85, 88]

for name, score in zip(names, scores):
    print(f"{name}: {score}")
# 张三: 90
# 李四: 85
# 王五: 88
```

---

## 嵌套解包

```python
parsed  = [("id_a", "get_commits", {"count": 5}), ("id_b", "get_status", {})]
results = ["5条提交记录...",                         "工作区干净..."]

# 同时解包两层
for (tool_id, func_name, func_args), result in zip(parsed, results):
    # (元组拆三个)                , 直接拿
    print(f"{func_name} → {result}")
```

没有 zip 就得用下标，很啰嗦：

```python
for i in range(len(parsed)):
    tool_id = parsed[i][0]
    result = results[i]
```

---

## 列表推导式

for 循环压缩成一行，自动 append：

```python
# for 循环
result = []
for i in range(5):
    result.append(i * 2)

# 列表推导式（等价）
result = [i * 2 for i in range(5)]
# [0, 2, 4, 6, 8]
```

读法：从 for 开始读，最前面是每次产出什么。