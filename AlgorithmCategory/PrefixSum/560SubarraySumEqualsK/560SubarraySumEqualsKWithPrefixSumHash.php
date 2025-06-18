<?php

# 560. 和为 K 的子数组 https://leetcode.cn/problems/subarray-sum-equals-k

class SubarraySumWithPrefixSumSolution
{
    /**
     * @param Integer[] $nums
     * @param Integer $k
     * @return Integer
     */
    function subarraySum(array $nums, int $k): int {
        $count = 0;
        $pre = 0;
        $mp = [0 => 1]; // 初始化哈希表，前缀和0出现1次

        foreach ($nums as $num) {
            $pre += $num; // 计算当前前缀和
            $target = $pre - $k;

            // 检查是否存在符合条件的前缀和
            if (isset($mp[$target])) {
                $count += $mp[$target];
            }

            // 更新当前前缀和的出现次数
            if (!isset($mp[$pre])) {
                $mp[$pre] = 0;
            }
            $mp[$pre]++;
        }

        return $count;
    }
}

$svc = new SubarraySumWithPrefixSumSolution();
$ans = $svc->subarraySum([1, 2, 3], 3);
var_dump($ans);