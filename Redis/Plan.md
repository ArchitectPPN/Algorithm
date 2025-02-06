阅读 Redis 源码是一个长期且系统的过程，每天 1 小时的时间需要合理规划，确保既能深入理解核心模块，又能保持持续的学习动力。以下是一个为期 **8 周**的阅读计划，每天 1 小时，分为 **入门**、**核心模块**、**高级特性**和**实践与总结**四个阶段。

---

### **第一阶段：入门与基础（第 1-2 周）**
目标：熟悉 Redis 的整体架构、代码结构和基本运行机制。

#### **第 1 周：环境准备与启动流程**
- **Day 1**：搭建 Redis 调试环境
    - 从 GitHub 克隆 Redis 源码（[https://github.com/redis/redis](https://github.com/redis/redis)）。
    - 使用 `make` 编译 Redis，确保可以运行 `redis-server` 和 `redis-cli`。
    - 配置 IDE（如 CLion 或 VSCode）支持代码跳转和调试。
- **Day 2-3**：阅读启动流程
    - 从 `main()` 函数（`server.c`）入手，跟踪 Redis 的初始化过程。
    - 重点理解 `initServer()` 和 `loadServerConfig()` 的调用链。
- **Day 4-5**：事件循环模型
    - 阅读 `ae.c`，理解事件循环的核心结构 `aeEventLoop`。
    - 跟踪 `aeProcessEvents()` 函数，了解文件事件和时间事件的处理逻辑。
- **Day 6-7**：网络层与客户端连接
    - 阅读 `networking.c`，分析 `createClient()` 和 `readQueryFromClient()`。
    - 理解 Redis 如何处理客户端请求（RESP 协议解析）。

---

#### **第 2 周：数据结构与对象系统**
- **Day 1-2**：动态字符串（SDS）
    - 阅读 `sds.c`，理解 `sds` 的内存分配策略和二进制安全设计。
    - 对比 C 字符串，分析 `sds` 的性能优势。
- **Day 3-4**：字典（Dict）
    - 阅读 `dict.c`，理解哈希表的实现（哈希函数、冲突解决）。
    - 重点分析渐进式 Rehash 机制（`dictRehash()`）。
- **Day 5**：跳跃表（SkipList）
    - 阅读 `t_zset.c`，理解跳跃表的结构和插入/查询逻辑。
    - 分析 `zslRandomLevel()` 的随机层高生成算法。
- **Day 6-7**：对象系统
    - 阅读 `object.c`，理解 `redisObject` 的结构（`type`、`encoding`、`refcount`）。
    - 跟踪 `createObject()` 和 `decrRefCount()`，学习引用计数机制。

---

### **第二阶段：核心模块（第 3-5 周）**
目标：深入 Redis 的核心功能模块，理解其实现细节。

#### **第 3 周：命令执行与数据库**
- **Day 1-2**：命令解析与执行
    - 阅读 `server.c` 中的 `processCommand()`，理解命令表的查找与执行流程。
    - 选择一个简单命令（如 `SET`）跟踪其实现（`t_string.c`）。
- **Day 3-4**：数据库实现
    - 阅读 `db.c`，理解键值对的存储与过期机制。
    - 分析 `expireIfNeeded()` 和 `activeExpireCycle()` 的过期键清理逻辑。
- **Day 5-7**：持久化机制（RDB）
    - 阅读 `rdb.c`，理解 `rdbSave()` 和 `rdbLoad()` 的实现。
    - 分析 `fork()` 和写时复制（COW）机制在 RDB 中的应用。

---

#### **第 4 周：持久化与高可用**
- **Day 1-3**：持久化机制（AOF）
    - 阅读 `aof.c`，理解 AOF 的追加写入（`feedAppendOnlyFile()`）和重写（`rewriteAppendOnlyFile()`）。
    - 分析 `fsync` 策略对性能和数据安全的影响。
- **Day 4-5**：主从复制
    - 阅读 `replication.c`，理解全量复制（`syncCommand()`）和增量复制（`replicationFeedSlaves()`）。
    - 分析 PSYNC 机制如何减少全量复制的开销。
- **Day 6-7**：哨兵模式
    - 阅读 `sentinel.c`，理解哨兵的故障检测与主从切换逻辑。

---

#### **第 5 周：集群与性能优化**
- **Day 1-3**：集群模式
    - 阅读 `cluster.c`，理解哈希槽分配（`clusterAddSlot()`）和 Gossip 协议。
    - 分析节点间通信（`clusterSendPing()`）和数据迁移逻辑。
- **Day 4-5**：性能优化
    - 阅读 `zmalloc.c`，理解 Redis 的内存管理策略。
    - 分析 `latency.c` 中的延迟监控机制。
- **Day 6-7**：多线程 I/O
    - 阅读 `networking.c` 中的 `handleClientsWithPendingReadsUsingThreads()`，理解 I/O 多线程的实现。

---

### **第三阶段：高级特性（第 6-7 周）**
目标：探索 Redis 的高级功能与扩展机制。

#### **第 6 周：模块化与扩展**
- **Day 1-3**：Redis 模块系统
    - 阅读 `module.c`，理解模块的加载与初始化流程。
    - 编写一个简单的 Redis 模块（如实现一个新的命令）。
- **Day 4-5**：Lua 脚本
    - 阅读 `scripting.c`，理解 Lua 脚本的执行与缓存机制。
    - 分析 `evalCommand()` 的实现。
- **Day 6-7**：Stream 数据结构
    - 阅读 `t_stream.c`，理解消息队列的实现（`streamAppendItem()`）。

---

#### **第 7 周：测试与调试**
- **Day 1-3**：单元测试
    - 阅读 `tests/` 目录下的测试用例，理解 Redis 的测试框架。
    - 运行并调试测试用例，验证对源码的理解。
- **Day 4-5**：性能测试
    - 使用 `redis-benchmark` 进行性能测试，结合源码分析性能瓶颈。
- **Day 6-7**：调试技巧
    - 使用 GDB 调试 Redis，跟踪命令执行路径（如 `processCommand()`）。

---

### **第四阶段：实践与总结（第 8 周）**
目标：通过实践巩固所学知识，并输出总结。

#### **第 8 周：实践与输出**
- **Day 1-3**：定制化改造
    - 修改 Redis 源码，添加一个简单的功能（如统计某个命令的调用次数）。
- **Day 4-5**：编写技术文章
    - 总结 Redis 的设计思想与实现细节，输出一篇技术文章。
- **Day 6-7**：分享与讨论
    - 在团队或社区分享你的学习成果，与他人讨论 Redis 的实现细节。

---

### **额外建议**
- **每日记录**：每天阅读后，记录关键函数、数据结构和设计思想。
- **动手实践**：尝试修改源码并运行，验证你的理解。
- **参考资源**：
    - 《Redis 设计与实现》（黄健宏）
    - Redis 官方文档（[https://redis.io/documentation](https://redis.io/documentation)）
    - Redis 源码注释版（[https://github.com/huangz1990/redis-3.0-annotated](https://github.com/huangz1990/redis-3.0-annotated)）

通过这个计划，你将在 8 周内系统掌握 Redis 的核心实现，并具备深入研究和定制化改造的能力。