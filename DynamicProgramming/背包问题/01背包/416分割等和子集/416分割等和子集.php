<?php

namespace CanPartition;

class Solution
{
    /**
     * @param Integer[] $nums
     * @return Boolean
     */
    function canPartitionOfficialAnswer(array $nums): bool
    {
        $sum = array_sum($nums);
        // 如果无法分为一个整数，说明无法拼成两个数组
        // 如果整数加起来为0，说明也无法拼成两个数组
        if (!is_int($sum / 2) || $sum == 0) {
            return false;
        }

        // 到这里说明一定能分两个数组
        $target = $sum / 2;

        // 初始化一个dp数组
        $dp = array_fill(0, count($nums), array_fill(0, $target + 1, false));

        if($nums[0] <= $target) {
            $dp[0][$nums[0]] = true;
        }

        // 到这里，将问题转换为能否装满$target的背包
        for ($i = 1; $i < count($nums); $i++) {
            for ($j = 0; $j <= $target; $j++) {
                //  直接继承上一个背包
                $dp[$i][$j] = $dp[$i - 1][$j];
                // 物品恰好能放进背包
                if ($nums[$i] == $j) {
                    $dp[$i][$j] = true;
                } else if ($nums[$i] < $j) {
                    $dp[$i][$j] = $dp[$i-1][$j] || $dp[$i-1][$j - $nums[$i]];
                }
            }
        }

        return $dp[count($nums)-1][$target];
    }
}

$question = [1, 5, 11, 5];

$solution = new Solution();
var_dump("是否可以分为两个数组：", $solution->canPartition($question));