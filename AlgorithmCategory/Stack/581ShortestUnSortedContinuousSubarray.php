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
     *
     * 尝试第二种思路：
     * 1. 先找到无序开始的最小下标
     * 2. 后面找到无序结束的最大下标
     *
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

$nums = [2, 6, 4, 8, 10, 9, 15];
var_dump($svc->findUnsortedSubarray($nums));

$nums = [1, 2, 3, 4];
var_dump($svc->findUnsortedSubarray($nums));

$nums = [1];
var_dump($svc->findUnsortedSubarray($nums));