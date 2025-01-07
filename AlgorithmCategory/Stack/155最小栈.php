<?php

namespace AlgorithmCategory\Stack;

/**
 * @see https://leetcode.cn/problems/min-stack/solutions/242190/zui-xiao-zhan-by-leetcode-solution/
 * 思路：
 * 1. 一个栈存放所有元素，一个栈存放最小元素
 * 2. pop时， 检查当前元素是否是最小元素，如果是，则同时出栈
 */
class MinStack
{
    /** @var array 存放所有元素栈 */
    private array $stack = [];

    /** @var array 存放最小元素栈 */
    private array $minStack = [];

    /**
     * @param Integer $val
     * @return void
     */
    function push(int $val): void
    {
        // 入栈
        $this->stack[] = $val;

        // 入最小值栈
        if (empty($this->minStack) || $val <= end($this->minStack)) {
            $this->minStack[] = $val;
        }
    }

    /**
     * @return NULL
     */
    function pop()
    {
        $val = array_pop($this->stack);
        if($val == end($this->minStack)) {
            return array_pop($this->minStack);
        }

        return $val;
    }

    /**
     * @return Integer
     */
    function top(): int
    {
        return end($this->stack);
    }

    /**
     * @return Integer
     */
    function getMin(): int
    {
        return end($this->minStack);
    }
}
