# Redis 分布式锁 Demo

这是一个基于 Redis 实现的分布式锁 PHP 示例，包含了完整的分布式锁功能。

## 功能特性

- ✅ **原子性加锁**：使用 Redis SET NX EX 命令确保原子性
- ✅ **防死锁**：支持锁超时自动释放
- ✅ **安全解锁**：使用 Lua 脚本确保只有锁的持有者才能解锁
- ✅ **锁续期**：支持延长锁的过期时间
- ✅ **重试机制**：支持获取锁失败时的重试
- ✅ **并发测试**：包含多进程并发测试示例

## 核心类

### DistributedLock

主要的分布式锁类，提供以下方法：

- `lock()` - 尝试获取锁
- `unlock()` - 释放锁
- `renew($expireTime)` - 续期锁
- `isLocked()` - 检查锁是否存在
- `getTtl()` - 获取锁的剩余时间
- `getLockValue()` - 获取锁的值

## 使用方法

### 1. 基本使用

```php
// 创建 Redis 连接
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// 创建分布式锁实例
$lock = new DistributedLock($redis, 'my_lock', 10);

// 尝试获取锁
if ($lock->lock()) {
    try {
        // 执行业务逻辑
        echo "执行业务逻辑...\n";
        sleep(2);
    } finally {
        // 确保释放锁
        $lock->unlock();
    }
} else {
    echo "获取锁失败\n";
}
```

### 2. 带重试的获取锁

```php
$lock = new DistributedLock($redis, 'my_lock', 10, 5, 200);
// 参数：Redis实例, 锁名, 过期时间, 重试次数, 重试延迟(毫秒)

if ($lock->lock()) {
    // 获取锁成功
    $lock->unlock();
}
```

### 3. 锁续期

```php
$lock = new DistributedLock($redis, 'my_lock', 5);

if ($lock->lock()) {
    // 执行业务逻辑
    sleep(3);
    
    // 续期锁
    if ($lock->renew(10)) {
        echo "锁续期成功\n";
    }
    
    $lock->unlock();
}
```

## 运行示例

确保 Redis 服务正在运行，然后执行：

```bash
php DistributeedLock.php
```

## 注意事项

1. **Redis 连接**：确保 Redis 服务正在运行，默认连接地址为 `127.0.0.1:6379`
2. **密码认证**：如果 Redis 设置了密码，请取消注释相关代码行
3. **pcntl 扩展**：并发测试需要 PHP pcntl 扩展支持
4. **锁超时**：合理设置锁的过期时间，避免业务执行时间超过锁超时时间
5. **异常处理**：在实际使用中，建议在 try-catch 块中使用锁

## 实现原理

1. **加锁**：使用 Redis 的 `SET key value NX EX seconds` 命令原子性地设置锁
2. **解锁**：使用 Lua 脚本确保只有锁的持有者才能删除锁
3. **防死锁**：通过设置过期时间，即使进程崩溃锁也会自动释放
4. **唯一标识**：每个锁都有唯一的标识符，防止误删其他进程的锁

## 适用场景

- 分布式系统中的资源竞争控制
- 防止重复处理（如定时任务）
- 库存扣减等并发控制
- 分布式事务协调 