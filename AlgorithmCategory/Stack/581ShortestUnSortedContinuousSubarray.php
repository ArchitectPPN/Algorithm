<?php

# 581. 最短无序连续子数组 https://leetcode.cn/problems/shortest-unsorted-continuous-subarray/description/

class ShortestUnSortedContinuousSubarraySolution
{
    /**
     * Thinking:
     * 我们需要考虑什么情况下需要把元素入栈
     * 1. 当前元素小于栈顶元素时，就一定需要入栈
     * 2. 当前元素大于栈顶元素时，就需要往后看，后续有没有比当前元素小的元素，如果后面还有，就需要将其入栈
     * 这个思路无法达到on的时间复杂度
     * 尝试第二种思路：
     * 1. 先找到无序开始的最小下标
     * 2. 后面找到无序结束的最大下标
     * @param Integer[] $nums
     * @return Integer
     */
    function findUnsortedSubarray(array $nums): int
    {
        $numsLen = count($nums);
        // 无序开始的最小下标
        $noOrderBeginIndex = $numsLen;
        // 无序结束的最大下标
        $noOrderEndIndex = 0;

        $stack = new SplStack();

        // 找到无序数组开始的下标
        for ($i = 0; $i < $numsLen; $i++) {
            // 栈顶元素大于当前元素时，说明当前已经开始无序了
            while (
                !$stack->isEmpty()
                && $nums[$stack->top()] > $nums[$i]
            ) {
                $noOrderBeginIndex = min($noOrderBeginIndex, $stack->pop());
            }
            $stack->push($i);
        }

        // 初始化栈
        $stack = new SplStack();

        // 从右向左遍历找到无序数组最大的下标
        for ($i = $numsLen - 1; $i >= 0; $i--) {
            // 栈不为空，栈顶元素小于当前元素，说明无序
            // 这里是从后向前遍历的，[9, 8], 栈顶为8，小于9
            while (
                !$stack->isEmpty()
                && $nums[$stack->top()] < $nums[$i]
            ) {
                $noOrderEndIndex = max($noOrderEndIndex, $stack->pop());
            }

            $stack->push($i);
        }

        // 这里 $noOrderBeginIndex 和 $noOrderEndIndex，都是下标，答案要求返回个数
        // 所以就需要+1
        return max($noOrderEndIndex - $noOrderBeginIndex + 1, 0);
    }
}

$svc = new ShortestUnSortedContinuousSubarraySolution();

$nums = [
    2,
    6,
    4,
    8,
    10,
    9,
    15,
];
var_dump($svc->findUnsortedSubarray($nums));

$nums = [
    1,
    2,
    3,
    4,
];
var_dump($svc->findUnsortedSubarray($nums));

$nums = [1];
var_dump($svc->findUnsortedSubarray($nums));

class ShortestUnSortedContinuousSubarraySolutionReviewOne
{
    /**
     * Thinking:
     * 1. 先找到无序开始的最小下标
     *  从前向后遍历, 栈顶元素大于当前元素, 说明现在的顺序是无序的
     * 2. 找到无序开始的最大下标
     *  从后向前遍历, 栈顶元素小于当前元素, 说明现在的顺序是无序的
     * start 为什么要初始化为数组的长度?
     * 答: 我们在查找那些无序的元素时, 会持续更新start的值, 为了能正确更新start的值, 就需要保证当前符合更新条件的元素一定小于start的值.
     * 所以start也可以设置为一个非常大的值, 但是这个值无法保证一定是大于数组长度的, 那么数组长度就是一个非常适合的值, 因为最大的下标是数组的长度-1.
     * @param Integer[] $nums
     * @return Integer
     */
    function findUnsortedSubarray(array $nums): int
    {
        $arrLen = count($nums);
        $start = $arrLen;

        // 从前向后遍历
        // 初始化栈
        $stack = new SplStack();
        for ($i = 0; $i < $arrLen; $i++) {
            // 栈不为空 栈顶元素大于当前元素
            while (!$stack->isEmpty() && $nums[$stack->top()] > $nums[$i]) {
                $start = min($start, $stack->pop());
            }
            // 入栈
            $stack->push($i);
        }

        // 从后向前遍历
        $stack = new SplStack();
        $end = 0;
        for ($j = $arrLen - 1; $j >= 0; $j--) {
            while (
                !$stack->isEmpty() && $nums[$stack->top()] < $nums[$j]
            ) {
                $end = max($end, $stack->pop());
            }
            $stack->push($j);
        }

        return max($end - $start + 1, 0);
    }
}

$svc = new ShortestUnSortedContinuousSubarraySolutionReviewOne();

$nums = [
    2,
    6,
    4,
    8,
    10,
    9,
    15,
];
var_dump($svc->findUnsortedSubarray($nums));

$nums = [
    1,
    2,
    3,
    4,
];
var_dump($svc->findUnsortedSubarray($nums));

$nums = [1];
var_dump($svc->findUnsortedSubarray($nums));