<?php

namespace MyQueueSolution;

class MyQueue
{
    /** @var array 输入栈 */
    private array $inStack = [];

    /** @var array 输出栈 */
    private array $outStack = [];

    /**
     * 将输入栈中的元素全部移动到输出栈中
     * @return void
     */
    private function inToOut(): void
    {
        while ($this->inStack) {
            $this->outStack[] = array_pop($this->inStack);
        }
    }

    /**
     * @param Integer $x
     * @return void
     */
    function push(int $x): void
    {
        $this->inStack[] = $x;
    }

    /**
     * @return Integer
     */
    function pop(): int
    {
        if (count($this->outStack) <= 0) {
            $this->inToOut();
        }

        return array_pop($this->outStack);
    }

    /**
     * @return Integer
     */
    function peek(): int
    {
        if (count($this->outStack) <= 0) {
            $this->inToOut();
        }

        return $this->outStack[count($this->outStack) - 1];
    }

    /**
     * @return Boolean
     */
    function empty(): bool
    {
        return empty($this->inStack) && empty($this->outStack);
    }
}

