<?php

class Solution
{
    /**
     * @param Integer[] $nums
     * @param Integer $target
     * @return Integer
     */
    function findTargetSumWays(array $nums, int $target): int
    {
        /**
         * 我们将nums分为两部分，left + right = sum;
         * left表示所有的加法合集，right表示所有的减法合集
         * target = left - right;
         * left = target + right;
         * right = left - target;
         * left = sum - right => sum - (left - target) => sum + target - left;
         * 2*left = sum + target => left = (sum + target) / 2;
         */
        $sum = array_sum($nums);
        // 无法整除，则没有符合要求的组合
        $left = ($sum + $target) / 2;
        if (!is_int($left)) {
            return 0;
        }

        // 该问题转化为背包问题
        for ($i = 0; $i < $target; $i++) {
            $dp[$i] = 0;
        }
        // 默认为0时有1种方法
        $dp[0] = 1;

        // 这一部分的原因请看518零钱兑换II
        for ($i = 0; $i < count($nums); $i++) {
            for ($j = $left; $j >= 0; $j--) {
                $dp[$j] += $dp[$j - $nums[$i]];
            }
        }

        return $dp[$left];
    }
}

$nums = [1,1,1,1,1];
$target = 3;

$solution = new Solution();
echo "方案数量：" . $solution->findTargetSumWays($nums, $target) . PHP_EOL;