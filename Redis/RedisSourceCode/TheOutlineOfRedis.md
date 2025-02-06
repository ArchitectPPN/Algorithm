### 启动流程
- 配置加载
- 事件循环初始化
- 网络监听

### 事件驱动模型
- Redis基于单线程事件循环ae.c，核心是aeEventLoop 结构和 aeProcessEvents() 函数。
- 理解文件事件(处理网络I/O)和时间事件(处理定时任务, 如 RDB 备份)。

### 命令处理流程
从 processCommand()（server.c）跟踪命令执行：
- 解析客户端请求（networking.c 中的 readQueryFromClient()）
- 查找命令表（server.commands），执行对应函数（如 setCommand() 在 t_string.c 中）。

### 数据结构实现
- 动态字符串（sds.c）：内存预分配策略，二进制安全设计。
- 字典（dict.c）：渐进式 Rehash、哈希冲突解决。
- 跳跃表（t_zset.c）：有序集合的底层实现。
- 压缩列表（ziplist.c）：紧凑内存布局，用于 List/Hash 等。
- 集合

### 持久化机制
- RDB：rdb.c 中的 rdbSave() 和 rdbLoad()，关注子进程 fork 和 COW 机制。
- AOF：aof.c 中的 feedAppendOnlyFile()，理解重写（Rewrite）流程和 fsync 策略。

### 高可用与集群
- 主从复制：replication.c，同步流程（全量/增量）、PSYNC 机制。
- 集群：cluster.c，数据分片（CRC16 哈希槽）、节点通信（Gossip 协议）。
