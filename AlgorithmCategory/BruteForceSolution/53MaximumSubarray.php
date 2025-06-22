<?php

// 53. 最大子数组和 https://leetcode.cn/problems/maximum-subarray/description

class MaximumSubarrayLoopSolution
{
    /**
     * @param array $nums
     * @return int
     */
    public function maxSubArray(array $nums):int
    {
        $count = count($nums);
        if ($count === 0) {
            return 0;
        }

        $nowMax = $totalMax = $nums[0];

        for ($i = 1; $i < $count; $i++) {
            $nowMax = max($nums[$i], $nums[$i] + $nowMax);
            $totalMax = max($nowMax, $totalMax);
        }

        return $totalMax;
    }
}

$svc = new MaximumSubarraySolution();
$maxTotal = $svc->maxSubArray([1, 2, 3]);
var_dump($maxTotal);