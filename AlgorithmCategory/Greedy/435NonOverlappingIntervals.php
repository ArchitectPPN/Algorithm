<?php

# 435. 无重叠区间 https://leetcode.cn/problems/non-overlapping-intervals/description/

class NonOverlappingIntervalsSolution
{
    /**
     * 移除重复的区间
     * @param array $intervals
     * @return int
     */
    public function eraseOverlapIntervals(array $intervals): int
    {
        $intervalsLen = count($intervals);
        if ($intervalsLen == 0) {
            return 0;
        }

        // 首先对区间进行排序，排序规则是按照区间的结束位置进行升序排序
        usort($intervals, function ($a, $b) {
            return $a[1] <=> $b[1];
        });

        $unOverlapLen = 1;
        // 默认第一个区间是不重复的
        $right = $intervals[0][1];

        // 从第一个元素开始遍历
        for ($i = 1; $i < $intervalsLen; $i++) {
            // 当前区间的起始值大于等于上一个区间的结束值, 说明两个区间不重叠
            if ($intervals[$i][0] >= $right) {
                $unOverlapLen++;
                $right = $intervals[$i][1];
            }
        }

        return $intervalsLen - $unOverlapLen;
    }
}
$question = [[1, 2], [2, 3], [3, 4], [1, 3]];
$svc = new NonOverlappingIntervalsSolution();
echo $svc->eraseOverlapIntervals($question);