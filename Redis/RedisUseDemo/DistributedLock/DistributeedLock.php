<?php

/**
 * Redis 分布式锁实现
 * 支持锁超时、自动续期、防死锁等特性
 */
class DistributedLock
{
    /** @var Redis Redis 连接实例，用于与 Redis 服务器进行通信 */
    private Redis $redis;

    /** @var string 锁的键名，在 Redis 中作为锁的唯一标识符 */
    private string $lockKey;

    /** @var string 锁的值，用于标识锁的持有者，格式为：uniqid()_进程ID */
    private string $lockValue;

    /** @var int 锁的过期时间（秒），防止死锁的关键参数 */
    private int $expireTime;

    /** @var int 获取锁失败时的重试次数，提高获取锁的成功率 */
    private int $retryTimes;

    /** @var int 重试之间的延迟时间（毫秒），避免频繁重试对 Redis 造成压力 */
    private int $retryDelay;

    /**
     * 构造函数
     * @param Redis $redis Redis 连接实例
     * @param string $lockKey 锁的键名
     * @param int $expireTime 锁过期时间（秒）
     * @param int $retryTimes 重试次数
     * @param int $retryDelay 重试延迟（毫秒）
     */
    public function __construct(
        Redis  $redis,
        string $lockKey,
        int    $expireTime = 10,
        int    $retryTimes = 3,
        int    $retryDelay = 100
    )
    {
        // 存储 Redis 连接实例，用于后续的 Redis 操作
        $this->redis = $redis;

        // 存储锁的键名，作为 Redis 中的键
        $this->lockKey = $lockKey;

        // 设置锁的过期时间，防止进程崩溃导致的死锁
        $this->expireTime = $expireTime;

        // 设置重试次数，当获取锁失败时进行重试
        $this->retryTimes = $retryTimes;

        // 设置重试延迟，避免频繁重试
        $this->retryDelay = $retryDelay;

        // 生成唯一的锁值，格式：时间戳_进程ID，确保每个锁实例的唯一性
        $this->lockValue = uniqid() . '_' . getmypid();
    }

    /**
     * 尝试获取锁
     * @return bool 是否成功获取锁
     */
    public function lock(): bool
    {
        // 当前尝试次数，从 0 开始计数
        $attempts = 0;

        // 循环尝试获取锁，直到成功或达到最大重试次数
        while ($attempts <= $this->retryTimes) {
            // 使用 SET NX EX 命令原子性地设置锁
            // NX: 只有当键不存在时才设置
            // EX: 设置过期时间（秒）
            $result = $this->redis->set($this->lockKey, $this->lockValue, ['NX', 'EX' => $this->expireTime]);

            // 如果设置成功，返回 true
            if ($result) {
                return true;
            }

            // 增加尝试次数
            $attempts++;

            // 如果还有重试机会，则等待一段时间后重试
            if ($attempts <= $this->retryTimes) {
                // 将毫秒转换为微秒，usleep 函数使用微秒作为单位
                usleep($this->retryDelay * 1000);
            }
        }

        // 所有重试都失败，返回 false
        return false;
    }

    /**
     * 释放锁
     * @return bool 是否成功释放锁
     */
    public function unlock(): bool
    {
        // 使用 Lua 脚本确保原子性操作
        // 只有当锁的值等于当前进程的值时，才删除锁
        // 这样可以防止误删其他进程的锁
        $luaScript = <<<LUA
if redis.call("get", KEYS[1]) == ARGV[1] then
    return redis.call("del", KEYS[1])
else
    return 0
end
LUA;

        // 执行 Lua 脚本
        // KEYS[1]: 锁的键名 ($this->lockKey)
        // ARGV[1]: 锁的值 ($this->lockValue)
        // 最后一个参数 1 表示 KEYS 数组的长度
        $result = $this->redis->eval($luaScript, [$this->lockKey, $this->lockValue], 1);

        // 返回 1 表示删除成功，0 表示删除失败
        return $result == 1;
    }

    /**
     * 续期锁
     * @param int|null $expireTime 新的过期时间
     * @return bool 是否成功续期
     */
    public function renew(?int $expireTime = null): bool
    {
        // 如果没有指定新的过期时间，使用默认的过期时间
        if ($expireTime === null) {
            $expireTime = $this->expireTime;
        }

        // 使用 Lua 脚本确保原子性操作
        // 只有当锁的值等于当前进程的值时，才延长锁的过期时间
        $luaScript = <<<LUA
if redis.call("get", KEYS[1]) == ARGV[1] then
    return redis.call("expire", KEYS[1], ARGV[2])
else
    return 0
end
LUA;

        // 执行 Lua 脚本
        // KEYS[1]: 锁的键名 ($this->lockKey)
        // ARGV[1]: 锁的值 ($this->lockValue)
        // ARGV[2]: 新的过期时间 ($expireTime)
        $result = $this->redis->eval($luaScript, [$this->lockKey, $this->lockValue, $expireTime], 1);

        // 返回 1 表示续期成功，0 表示续期失败
        return $result == 1;
    }

    /**
     * 检查锁是否存在
     * @return bool 锁是否存在
     */
    public function isLocked(): bool
    {
        // 检查 Redis 中是否存在指定的键
        return $this->redis->exists($this->lockKey);
    }

    /**
     * 获取锁的剩余时间
     * @return int 剩余时间（秒），-1 表示永不过期，-2 表示键不存在
     */
    public function getTtl(): int
    {
        // 获取键的剩余生存时间（TTL: Time To Live）
        return $this->redis->ttl($this->lockKey);
    }

    /**
     * 获取锁的值
     * @return string|null 锁的值
     */
    public function getLockValue(): ?string
    {
        // 获取锁的值，用于验证锁的持有者
        return $this->redis->get($this->lockKey);
    }
}

/**
 * 使用示例
 */
class DistributedLockDemo
{
    /** @var Redis Redis 连接实例，用于创建分布式锁 */
    private Redis $redis;

    public function __construct()
    {
        // 创建 Redis 连接实例
        $this->redis = new Redis();

        // 连接到 Redis 服务器，默认地址和端口
        $this->redis->connect('redis');

        // 如果有密码，取消注释下面这行
        // $this->redis->auth('your_password');
    }

    /**
     * 基本使用示例
     */
    public function basicUsage(): void
    {
        echo "=== 基本使用示例 ===\n";

        // 创建分布式锁实例，锁名为 'test_lock'，过期时间 10 秒
        $lock = new DistributedLock($this->redis, 'test_lock', 10);

        // 尝试获取锁
        if ($lock->lock()) {
            echo "成功获取锁\n";

            // 模拟业务处理，这里用 sleep 代替实际的业务逻辑
            echo "执行业务逻辑...\n";
            sleep(2);

            // 释放锁
            if ($lock->unlock()) {
                echo "成功释放锁\n";
            } else {
                echo "释放锁失败\n";
            }
        } else {
            echo "获取锁失败\n";
        }
    }

    /**
     * 并发测试示例
     */
    public function concurrentTest(): void
    {
        echo "\n=== 并发测试示例 ===\n";

        // 存储所有子进程的 PID
        $processes = [];

        // 创建多个进程模拟并发
        for ($i = 0; $i < 5; $i++) {
            // 创建子进程
            $pid = pcntl_fork();

            if ($pid == 0) {
                // 子进程代码
                // 创建分布式锁实例，锁名为 'concurrent_lock'，过期时间 5 秒
                $lock = new DistributedLock($this->redis, 'concurrent_lock', 5);

                // 尝试获取锁
                if ($lock->lock()) {
                    echo "进程 " . getmypid() . " 获取锁成功\n";

                    // 模拟业务处理
                    sleep(1);

                    // 释放锁
                    if ($lock->unlock()) {
                        echo "进程 " . getmypid() . " 释放锁成功\n";
                    }
                } else {
                    echo "进程 " . getmypid() . " 获取锁失败\n";
                }

                // 子进程结束
                exit(0);
            } else {
                // 父进程：记录子进程的 PID
                $processes[] = $pid;
            }
        }

        // 等待所有子进程结束
        foreach ($processes as $pid) {
            // 等待指定 PID 的进程结束
            pcntl_waitpid($pid, $status);
        }
    }

    /**
     * 锁超时测试
     */
    public function timeoutTest(): void
    {
        echo "\n=== 锁超时测试 ===\n";

        // 创建分布式锁实例，锁名为 'timeout_lock'，过期时间 3 秒
        $lock = new DistributedLock($this->redis, 'timeout_lock', 3);

        // 尝试获取锁
        if ($lock->lock()) {
            echo "获取锁成功，TTL: " . $lock->getTtl() . " 秒\n";

            // 等待锁过期（等待 4 秒，超过锁的过期时间）
            echo "等待锁过期...\n";
            sleep(4);

            // 检查锁的状态
            echo "锁过期后 TTL: " . $lock->getTtl() . "\n";
            echo "锁是否存在: " . ($lock->isLocked() ? '是' : '否') . "\n";
        }
    }

    /**
     * 锁续期测试
     */
    public function renewTest(): void
    {
        echo "\n=== 锁续期测试 ===\n";

        // 创建分布式锁实例，锁名为 'renew_lock'，过期时间 5 秒
        $lock = new DistributedLock($this->redis, 'renew_lock', 5);

        // 尝试获取锁
        if ($lock->lock()) {
            echo "获取锁成功，TTL: " . $lock->getTtl() . " 秒\n";

            // 等待 2 秒
            sleep(2);
            echo "2秒后 TTL: " . $lock->getTtl() . " 秒\n";

            // 续期锁，将过期时间延长到 10 秒
            if ($lock->renew(10)) {
                echo "续期成功，新的 TTL: " . $lock->getTtl() . " 秒\n";
            }

            // 释放锁
            $lock->unlock();
        }
    }
}

// 运行示例
if (php_sapi_name() === 'cli') {
    // 创建演示实例
    $demo = new DistributedLockDemo();

    // 运行各种测试示例
    $demo->basicUsage();
    $demo->timeoutTest();
    $demo->renewTest();

    // 注意：并发测试需要 pcntl 扩展
    if (extension_loaded('pcntl')) {
        $demo->concurrentTest();
    } else {
        echo "\n注意：pcntl 扩展未安装，跳过并发测试\n";
    }
} 