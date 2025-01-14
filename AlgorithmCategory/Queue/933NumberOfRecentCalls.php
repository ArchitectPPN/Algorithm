<?php

namespace AlgorithmCategory\Queue\NumberOfRecentCalls;

class RecentCounter
{
    /** @var array 最近请求 */
    private array $queue = [];

    /**
     *
     * @param int $t
     * @return int
     */
    public function ping(int $t): int
    {
        $this->queue[] = $t;

        while ($this->queue[0] < $t - 3000) {
            // 使用 array_unshift 会超时, 每次都要移动元素, 所以每次操作都是o(n)
            array_unshift($this->queue);
        }

        return count($this->queue);
    }
}

class RecentCounterWithSPLQueue
{
    private ?\SplQueue $queue = null;

    public function __construct()
    {
        $this->queue = new \SplQueue();
    }

    /**
     *
     * @param int $t
     * @return int
     */
    public function ping(int $t): int
    {
        $this->queue->enqueue($t);
        // 这里就应该拿bottom, 因为是一个队列, 队头就是最早的元素
        while ($this->queue->bottom() < $t - 3000) {
            $this->queue->dequeue();
        }

        return $this->queue->count();
    }
}

$splQueue = new RecentCounterWithSPLQueue();
$splQueue->ping(1);
$splQueue->ping(2000);
$splQueue->ping(3000);
$splQueue->ping(4000);