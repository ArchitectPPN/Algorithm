<?php

namespace AlgorithmCategory\Array\TheSumOfNNumbers;

class HashSolution {

    /**
     * @param Integer[] $nums
     * @param Integer $target
     * @return array
     */
    function twoSum(array $nums, int $target) :array {
        // 数组中没有元素时, 直接退出
        if (count($nums) == 0) {
            return [];
        }

        // 循环遍历数组, 创建一个hash表，键为数组元素值，值为数组元素下标
        $hash = [];
        foreach ($nums as $index => $val) {
            $hash[$val] = $index;
        }

        foreach ($nums as $index => $val) {
            $findTargetNum = $target - $val;
            if (isset($hash[$findTargetNum]) && $hash[$findTargetNum] != $index) {
                return [$index, $hash[$findTargetNum]];
            }
        }

        return [];
    }
}