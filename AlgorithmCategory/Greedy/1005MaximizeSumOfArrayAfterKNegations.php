<?php

# 1005. K 次取反后最大化的数组和 https://leetcode.cn/problems/maximize-sum-of-array-after-k-negations/description/

class LargestSumAfterKNegationsSolutionOne
{
    /**
     * @param Integer[] $nums
     * @param Integer $k
     * @return Integer
     */
    function largestSumAfterKNegations(array $nums, int $k): int
    {
        // 对数组进行排序
        sort($nums);

        // 遍历数组，优先反转负数
        for ($i = 0; $i < count($nums); $i++) {
            if ($k > 0 && $nums[$i] < 0) {
                $nums[$i] = -$nums[$i];
                $k--;
            } else {
                break;
            }
        }

        // 如果 k 还有剩余
        if ($k > 0) {
            // 对数组重新排序，因为前面反转负数可能改变了顺序
            sort($nums);
            // 如果 k 是奇数，反转最小的数
            if ($k % 2 == 1) {
                $nums[0] = -$nums[0];
            }
        }

        // 计算数组元素的和
        return array_sum($nums);
    }
}

$nums = [
    [[3, -1, 0, 2], 3],
    [[-8, 3, -5, -3, -5, -2], 6],
];
$k = 3;
$svc = new LargestSumAfterKNegationsSolutionOne();

foreach ($nums as $num) {
    echo $svc->largestSumAfterKNegations($num[0], $num[1]) . PHP_EOL;
}

class LargestSumAfterKNegationsSolutionTwo
{
    public function largestSumAfterKNegations($nums, $k)
    {
        // 排序，把可能有的负数排到前面
        sort($nums);
        $sum = 0;
        for ($i = 0; $i < count($nums); $i++) {
            // 贪心：如果是负数，而k还有盈余，就把负数反过来
            if ($nums[$i] < 0 && $k > 0) {
                $nums[$i] = -1 * $nums[$i];
                $k--;
            }
            $sum += $nums[$i];
        }
        sort($nums);
        // 如果k没剩，那说明能转的负数都转正了，已经是最大和，返回sum
        // 如果k有剩，说明负数已经全部转正，所以如果k还剩偶数个就自己抵消掉，不用删减，如果k还剩奇数个就减掉2倍最小正数。
        return $sum - ($k % 2 == 0 ? 0 : 2 * $nums[0]);
    }
}