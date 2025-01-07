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
        $ans = [];
        if (empty($intervals)) {
            return $ans;
        }

        // 对二维数组进行排序, 升序排序
        usort($intervals, function ($a, $b) {
            return $a[0] <=> $b[0];
        });

        foreach ($intervals as $interval) {
            $last = end($ans);
            // 如果最后的结果集为空, 或者当前元素的结束值大于结果集最后一个元素结束值, 说明两个数组不重叠
            if (empty($last) || $last[1] < $interval[0]) {
                $ans[] = $interval;
            } else {
                // 如果当前值的第一个元素小于等于最后一个元素的结束值，
                // 则将当前值合并到结果集中, 更新结果集的最后一个元素的结束值
                $ans[count($ans) - 1][1] = max($last[1], $interval[1]);
            }
        }

        return $ans;
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

echo json_encode($merge, JSON_UNESCAPED_UNICODE);