<?php

# [56. 合并区间](https://leetcode-cn.com/problems/merge-intervals)

/**
 *
 */
class MergeIntervalsSolution
{
    /**
     * @param Integer[][] $intervals
     * @return Integer[][]
     */
    function merge(array $intervals):array
    {
        if (empty($intervals)) {
            return [];
        }

        // 对原数据进行排序
        usort($intervals, function ($a, $b) {
            return $a[0] <=> $a[1];
        });

        $result = [];
        foreach ($intervals as $interval) {
            $end = end($result);

            // 没有上一个元素 ｜｜ 上一个区间的结束值小于当前区间的开始值，说明两个区间不重叠
            if (empty($end) || $end[1] < $interval[0]) {
                $result[] = $interval;
            } else {
                // 弹出最后一个元素
                $end = array_pop($result);
                // 修改最后一个区间的结束值
                $end[1] = max($end[1], $interval[1]);
                $end[0] = min($end[0], $interval[0]);
                // 重新放回去
                $result[] = $end;
            }
        }

        return $result;
    }
}

$intervals = [
    [[1,3],[2,6],[8,10],[15,18]],
    [[1,4],[4,5]]
];
$svc = new MergeIntervalsSolution();
foreach ($intervals as $interval) {
    $mergeArr = $svc->merge($interval);
    var_dump($mergeArr);
}
