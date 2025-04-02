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

$question = [
    [
        1,
        2,
    ],
    [
        2,
        3,
    ],
    [
        3,
        4,
    ],
    [
        1,
        3,
    ],
];
$svc = new NonOverlappingIntervalsSolution();
echo $svc->eraseOverlapIntervals($question) . PHP_EOL;

# 2025-4-2
class NonOverlappingIntervalsSolutionReviewOne
{
    /**
     * 移除重复的区间
     * @param array $intervals
     * @return int
     */
    public function eraseOverlapIntervals(array $intervals): int
    {
        $intervalsLen = count($intervals);
        // 区间元素如果小于等于1, 则不需要移除, 直接返回0
        if ($intervalsLen <= 1) {
            return 0;
        }

        // 对区间按照结束位置进行升序排序
        usort($intervals, function ($a, $b) {
            return $a[1] <=> $b[1];
        });

        // 找出不重叠的区间个数
        $unOverlapLen = 1;
        // 默认第一个区间不重叠
        $right = $intervals[0][1];

        for ($i = 1; $i < $intervalsLen; $i++) {
            // 当前区间的起始值大于等于上一个区间的结束值, 说明两个区间不重叠
            if ($intervals[$i][0] >= $right) {
                $unOverlapLen++;
                $right = $intervals[$i][1];
            }
        }

        // 总长度减去不重叠的区间个数就是需要移除的重叠区间个数
        return $intervalsLen - $unOverlapLen;
    }
}

$svc = new NonOverlappingIntervalsSolution();
echo $svc->eraseOverlapIntervals($question) . PHP_EOL;