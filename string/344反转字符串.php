<?php

namespace ReverseString;

/**
 * 双指针解法， 当两个指针相遇时， 终止
 */
class Solution
{

    /**
     * @param String[] $s
     * @return array|null
     */
    function reverseString(array &$s): ?array
    {
        // 为空直接返回
        if (empty($s)) {
            return [];
        }

        // 拿到最大下标
        $maxIndex = count($s) - 1;
        // 这里直接小于就好， 小于等于会多一次计算
        for ($i = 0; $i < $maxIndex; $i++) {
            $tmp = $s[$maxIndex];
            $s[$maxIndex] = $s[$i];
            $s[$i] = $tmp;
            $maxIndex--;
        }

        return $s;
    }
}