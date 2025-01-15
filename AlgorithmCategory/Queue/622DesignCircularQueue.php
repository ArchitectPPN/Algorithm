<?php

namespace AlgorithmCategory\Queue\DesignCircularQueue;

# 设计循环队列:https://leetcode.cn/problems/design-circular-queue/description/

class CircularQueue
{
    /** @var int 队列容量 */
    private int $capacity = 0;

    /** @var array 队列 */
    private array $queue = [];

    /** @var int 队列头下标 */
    private int $headIndex = 0;

    /** @var int 队列当前元素数量 */
    private int $count = 0;

    public function __construct($k = 8)
    {
        $this->capacity = $k;
        $this->queue = array_fill(0, $k, 0);
        $this->headIndex = 0;
        $this->count = 0;
    }

    /**
     * 入队
     * @param $value
     * @return bool
     */
    public function enQueue($value): bool
    {
        if ($this->isFull()) {
            return false;
        }

        $this->queue[($this->headIndex + $this->count) % $this->capacity] = $value;
        $this->count++;

        return true;
    }

    public function deQueue(): bool
    {
        if ($this->count == 0) {
            return false;
        }

        // 这里使用逻辑删除, 这样做的好处:
        // 1. 减少数组元素移动次数, 减少时间复杂度
        $this->headIndex = ($this->headIndex + 1) % $this->capacity;
        $this->count--;

        return true;
    }

    /**
     * @return bool
     */
    public function isFull(): bool
    {
        return $this->count == $this->capacity;
    }

    public function Front()
    {
        if ($this->count == 0) {
            return -1;
        }

        return $this->queue[$this->headIndex];
    }

    public function Rear()
    {
        if ($this->count == 0) {
            return -1;
        }
        return $this->queue[($this->headIndex + $this->count - 1) % $this->capacity];
    }

    public function isEmpty(): bool
    {
        return $this->count == 0;
    }
}