<?php

namespace AlgorithmCategory\Array\BinarySearch;

class FindTargetWithBinarySearch
{
    /**
     * 二分查找while循环
     *
     * @param array $arr
     * @param int $target
     * @return int
     */
    public function binarySearch(array $arr, int $target): int
    {
        // 给定的数组为空，直接返回 -1
        if (empty($arr)) {
            return -1;
        }

        // 初始化开始和结束指针
        $left = 0;
        $right = count($arr) - 1;

        // 为什么是<=, 最后左右下标会相等, 如果在<时就终止, 那么最后一个=的值就会被丢失,可能造成答案错误
        while ($left <= $right) {
            // 使用位运算来计算中间值，避免溢出问题
            /**
             * 1. $right - $left:
             * 计算当前范围的宽度，即右边界和左边界之间的距离。
             * 2. intdiv($right - $left, 2):
             * 使用 intdiv 函数计算当前范围的一半。这种做法的优点是即使 $right 和 $left 非常大，它们的差值依然在合理范围内。
             * 3. $left + ...:
             * 加上左边界索引。这是因为 right - left 的一半加上 left 就得到了中间值的索引位置。
             */
            $mid = $left + intdiv($right - $left, 2);

            if ($arr[$mid] == $target) {
                return $mid;
            } elseif ($arr[$mid] < $target) {
                // 如果目标值大于中间值，则移动左指针
                $left = $mid + 1;
            } else {
                // 如果目标值小于中间值，则移动右指针
                $right = $mid - 1;
            }
        }

        // 如果没有找到目标值，返回 -1
        return -1;
    }
}

$question = [1,2,3,4,5,6,100, 300];
$solution = new FindTargetWithBinarySearch();
for ($i = 1; $i <= 6; $i++) {
    $target = $i;
    echo $solution->binarySearch($question, $target);
    echo PHP_EOL;
}

echo $solution->binarySearch($question, 500);