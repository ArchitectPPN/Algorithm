<?php

namespace ReadBinaryWatchBackTracking;

class Solution
{
    /**
     * @param int $turnedOn
     * @param int $start
     * @param int $hours
     * @param int $minutes
     * @param array $result
     * @return void
     */
    private function backtrack(int $turnedOn, int $start, int $hours, int $minutes, array &$result): void
    {
        // 如果小时超过11或分钟超过59，退出递归
        if ($hours > 11 || $minutes > 59) {
            return;
        }
        // 如果已经使用了所有的亮灯数，记录时间
        if ($turnedOn == 0) {
            $result[] = sprintf("%d:%02d", $hours, $minutes);
            return;
        }

        // 遍历所有可能的灯
        // 为什么小于10; 因为有10个灯, 也就是 2 ^ 4才可以表示12个小时; 2 ^ 6 = 64才可以表示60分钟;
        // 所以共10个灯, 前4个灯表示小时, 后6个灯表示分钟
        for ($i = $start; $i < 10; $i++) {
            if ($i < 4) {
                // 更新小时数（前4个灯表示小时）
                $this->backtrack($turnedOn - 1, $i + 1, $hours + (1 << $i), $minutes, $result);
            } else {
                // 更新分钟数（后6个灯表示分钟）
                $this->backtrack($turnedOn - 1, $i + 1, $hours, $minutes + (1 << ($i - 4)), $result);
            }
        }
    }

    /**
     * @param int $turnedOn
     * @return array
     */
    function readBinaryWatch(int $turnedOn): array
    {
        $result = [];

        // 初始化回溯
        $this->backtrack($turnedOn, 0, 0, 0, $result);

        return $result;
    }
}

$solution = new Solution();
$ans = $solution->readBinaryWatch(3);
var_dump($ans);