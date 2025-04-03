<?php

# 452. 用最少数量的箭引爆气球 https://leetcode.cn/problems/minimum-number-of-arrows-to-burst-balloons/description/

class FindMinArrowShotsSolution
{
    /**
     * @param array $points
     * @return int
     */
    function findMinArrowShots(array $points): int
    {
        if (empty($points)) {
            return 0;
        }
        usort($points, function ($a, $b) {
            return $a[1] <=> $b[1];
        });

        $ans = 1;
        $count = count($points);
        $end = $points[0][1];
        for ($i = 1; $i < $count; $i++) {
            // 小于等于起始位置, 说明有重叠
            if ($points[$i][0] <= $end) {
                $end = min($end, $points[$i][1]);
            } else {
                $ans++;
                $end = $points[$i][1];
            }
        }

        return $ans;
    }
}

$svc = new FindMinArrowShotsSolution();
$question = [
    [
        10,
        16,
    ],
    [
        2,
        8,
    ],
    [
        1,
        6,
    ],
    [
        7,
        12,
    ],
];
echo $svc->findMinArrowShots($question) . PHP_EOL;

$question = [
    [
        1,
        2,
    ],
    [
        3,
        4,
    ],
    [
        5,
        6,
    ],
    [
        7,
        8,
    ],
];
echo $svc->findMinArrowShots($question) . PHP_EOL;

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
        4,
        5,
    ],
];
echo $svc->findMinArrowShots($question) . PHP_EOL;