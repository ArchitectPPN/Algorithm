<?php

namespace AlgorithmCategory\Stack;

class MyStackUseArray
{
    /** @var array */
    private array $stack;

    /**
     * Initialize your data structure here.
     */
    public function __construct()
    {
        $this->stack = [];
    }

    /**
     * Push element x onto stack.
     * @param Integer $x
     * @return void
     */
    public function push(int $x): void
    {
        $this->stack[] = $x;
    }

    /**
     * Removes the element on top of the stack and returns that element.
     * @return Integer
     */
    public function pop(): int
    {
        if ($this->empty()) {
            return 0;
        }

        return array_pop($this->stack);
    }

    /**
     * Get the top element.
     * @return Integer
     */
    public function top(): int
    {
        if ($this->empty()) {
            return 0;
        }

        return $this->stack[count($this->stack) - 1];
    }

    /**
     * Returns whether the stack is empty.
     * @return Boolean
     */
    public function empty(): bool
    {
        return count($this->stack) == 0;
    }
}


class MyStackUseSplQueue
{
    private SplQueue $queue1;
    private SplQueue $queue2;

    /**
     * Initialize your data structure here.
     */
    public function __construct()
    {
        $this->queue1 = new SplQueue();
        $this->queue2 = new SplQueue();
    }

    /**
     * Push element x onto stack.
     * @param Integer $x
     * @return void
     */
    public function push(int $x): void
    {
        $this->queue1->enqueue($x);
    }

    /**
     * Removes the element on top of the stack and returns that element.
     * @return Integer
     */
    public function pop()
    {
        if ($this->empty()) {
            return -1;
        }
        // 来回倒腾一次
        while ($this->queue1->count() > 1) {
            $this->queue2->enqueue($this->queue1->dequeue());
        }

        $return = $this->queue1->dequeue();
        $this->queue1 = $this->queue2;
        $this->queue2 = new SplQueue();
        return $return;
    }

    /**
     * Get the top element.
     * @return Integer
     */
    public function top()
    {
        return $this->queue1->top();
    }

    /**
     * Returns whether the stack is empty.
     * @return Boolean
     */
    public function empty()
    {
        return $this->queue1->isEmpty() && $this->queue2->isEmpty();
    }
}