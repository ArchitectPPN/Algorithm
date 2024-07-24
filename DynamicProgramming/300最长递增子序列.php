<?php

namespace LengthOfLIS;

class Solution {

    /**
     * 最长递增子序列
     *
     * @param Integer[] $nums
     * @return Integer
     */
    function lengthOfLIS(array $nums): int
    {
        $length = count($nums);
        if ($length <= 1) {
            return $length;
        }

        // 使用 array_fill 初始化 dp 数组为长度为 length 的数组，所有元素初始值为 1，因为每个元素自身可以视为长度为 1 的子序列。
        $dp = array_fill(0, $length, 1);
        // 外层循环从 i=1 到 length-1，遍历数组中的每个元素。
        for ($i = 1; $i < $length; $i++) {
            // 内层循环从 j=0 到 i-1，检查 i 前面的所有元素。
            for ($j = $i; $j < $length; $j++) {
                if ($dp[$i] > $dp[$j]) {
                    $dp[$i] = max($dp[$i], $dp[$j] + 1);
                }
            }
        }

        return max($dp);
    }
}
