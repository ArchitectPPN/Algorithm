<?php

# 376. 摆动序列 https://leetcode.cn/problems/wiggle-subsequence/description/

class WiggleSubsequenceSolution
{
    /**
     * @param array $nums
     * @return int
     */
    public function wiggleMaxLength(array $nums): int
    {
        // 数组长度
        $numsLength = count($nums);
        // 如果数组长度小于等于 1，直接返回数组长度
        if ($numsLength <= 2) {
            return $numsLength;
        }

        // 一开始不知道方向
        $lastDirection = null;
        $res = 0;
        //  [1, 2, 2, 2, 1, 3]
        for ($i = 1; $i < $numsLength; $i++) {
            if ($nums[$i] != $nums[$i - 1]) {
                // 方向还未确认
                if ($lastDirection == null) {
                    // 设置方向
                    $lastDirection = $nums[$i] > $nums[$i - 1] ? 1 : 0;
                    $res++;
                } elseif (
                    // 上一次是下降, 这次就应该是上升
                    $lastDirection == 0 && $nums[$i] > $nums[$i - 1]
                    // 上一次是上升, 这次就应该是下降
                    || $lastDirection == 1 && $nums[$i] < $nums[$i - 1]
                ) {
                    // 设置方向
                    $lastDirection = $nums[$i] > $nums[$i - 1] ? 1 : 0;
                    $res++;
                }
            }
        }

        return $res + 1;
    }
}

