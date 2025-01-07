<?php

namespace AlgorithmCategory\Array\TheSumOfNNumbers;

class DoublePointSolution {
    /**
     * @var array 最终答案
     */
    private array $ans = [];

    /**
     * @param Integer[] $nums
     * @return Integer[][]
     */
    function threeSum(array $nums) : array {
        // 先将原来得数组进行排序
        sort($nums);
        // 拿到数组总得长度
        $arrSize = count($nums);
        // 循环遍历数组
        for ($i = 0; $i < $arrSize; $i++) {
            // 如果当前值和前一个值一样, 直接跳过
            if ($i > 0 && $nums[$i] == $nums[$i-1]) {
                continue;
            }
            $this->twoSum($nums, $i + 1, $arrSize - 1, -$nums[$i], $nums[$i]);

        }

        return $this->ans;
    }

    /**
     * @param array $nums 给定的数组
     * @param int $start 开始下标
     * @param int $end 结束下标
     * @param int $target 目标值
     * @param int $firstNum 第一个数值
     * @return void
     */
    private function twoSum(array $nums, int $start, int $end, int $target, int $firstNum) : void
    {
        while ($start < $end) {
            // 头+尾的和
            $sum = $nums[$start] + $nums[$end];
            // 和等于目标
            if ($sum == $target) {
                var_dump($end);
                $this->ans[] = [$firstNum, $nums[$start], $nums[$end]];
                // 如果start的下一个值等于start, 则继续往前移
                while ($start < $end && $nums[$start] == $nums[$start+1]) {
                    $start++;
                }
                $start++;

                // 如果end的下一个值等于end, 则继续往前移
                while ($start < $end && $nums[$end] == $nums[$end-1]) {
                    $end--;
                }
                $end--;
            } else if ($sum > $target) {
                $end--;
            } else {
                $start++;
            }
        }
    }
}
