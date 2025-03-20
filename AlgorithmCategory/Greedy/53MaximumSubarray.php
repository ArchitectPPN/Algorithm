<?php

# 53. 最大子数组和 https://leetcode.cn/problems/maximum-subarray/description/

class MaximumSubarraySolution
{
    /**
     * @param array $nums
     * @return int
     */
    public function maxSubArray(array $nums): int
    {
        $numsLen = count($nums);
        if ($numsLen == 0) {
            return 0;
        }
        // max 用来存储最大的数组和
        // $nowMax 存储当前正在跟踪的数组和
        $max = $nowMax = $nums[0];

        // 因为赋值为了第一个元素, 所以从 1 开始循环
        for ($i = 1; $i < $numsLen; $i++) {
            // 这里存在两种情况,
            // 1. 当前元素大于当前正在跟踪的数组和,
            // 2. 如果当前元素不大于之前累加的元素, 就累加当前元素
            $nowMax = max($nums[$i], $nums[$i] + $nowMax);
            $max = max($max, $nowMax);
        }

        return $max;
    }
}

$svc = new MaximumSubarraySolution();
echo $svc->maxSubArray(
    [
        -2,
        1,
        -3,
        4,
        -1,
        2,
        1,
        -5,
        4,
    ]
) . PHP_EOL;