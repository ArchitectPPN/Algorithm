<?php
// 检查 RdKafka 扩展是否已加载
if (!extension_loaded('rdkafka')) {
    die("RdKafka 扩展未加载，请安装并启用 RdKafka 扩展。\n");
}

// 配置 Kafka 消费者组的函数
function configureConsumerGroup(string $groupId, string $bootstrapServers): RdKafka\Conf
{
    $conf = new RdKafka\Conf();
    $conf->set('bootstrap.servers', $bootstrapServers);
    $conf->set('group.id', $groupId);
    $conf->set('enable.auto.commit', 'true');
    $conf->set('auto.commit.interval.ms', 1000);
    return $conf;
}

// 消费消息的函数
function consumeMessages(RdKafka\Consumer $consumer, string $consumerName): void
{
    $consumer->subscribe(['KAFKA_TEST_TOPIC']);
    while (true) {
        $message = $consumer->consume(1000);
        match ($message->err) {
            RD_KAFKA_RESP_ERR_NO_ERROR => function () use ($consumerName, $message) {
                echo "消费者 {$consumerName} 从分区 {$message->partition} 接收到消息: {$message->payload}\n";
            },
            RD_KAFKA_RESP_ERR__PARTITION_EOF => function () use ($consumerName, $message) {
                echo "消费者 {$consumerName} 已到达分区末尾，等待新消息...\n";
            },
            RD_KAFKA_RESP_ERR__TIMED_OUT => function () use ($consumerName, $message) {
                echo "消费者 {$consumerName} 消费超时，继续等待...\n";
            },
            default => throw new RuntimeException($message->errstr(), $message->err)
        };
    }
}

// 配置信息
$groupId = 'test_consumer_group';
$bootstrapServers = 'kafka.dev.eainc.com:9092';

// 创建两个消费者实例
$conf = configureConsumerGroup($groupId, $bootstrapServers);
$consumer1 = new RdKafka\Consumer($conf);
$consumer2 = new RdKafka\Consumer($conf);

// 启动两个进程来模拟两个消费者并行消费
$pid = pcntl_fork();
if ($pid === -1) {
    die('无法创建子进程');
} elseif ($pid === 0) {
    consumeMessages($consumer1, 'Consumer 1');
} else {
    consumeMessages($consumer2, 'Consumer 2');
}