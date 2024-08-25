<?php

namespace ArrayMergeSolution;

# https://leetcode.cn/problems/merge-intervals/description/
class Solution
{
    /**
     * 解题思路：
     *  1. 先将数组按照第一个元素进行排序，相同情况下，按照第二个元素进行排序
     *  2. 使用ans数组进行存储结果集，结果集中已经有元素了，检查当前数组的第一个元素是否小于ans最后一个元素结束值
     * @param Integer[][] $intervals
     * @return Integer[][]
     */
    function merge(array $intervals): array
    {
        return [];
    }

    private function sort(array $intervals)
    {

    }
}

$merge = [
    [
        1,
        3,
    ],
    [
        2,
        6,
    ],
    [
        8,
        10,
    ],
    [
        15,
        18,
    ],
];

$solution = new Solution();
$merge = $solution->merge($merge);

var_dump($merge);