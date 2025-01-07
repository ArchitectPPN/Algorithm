<?php

namespace AlgorithmCategory\Array\BinarySearch;
class SearchNumberSolution
{
    /**
     * @var array
     */
    private array $nums = [];

    /**
     * @param Integer[] $nums
     * @param Integer $target
     * @return Integer
     */
    function search(array $nums, int $target): int
    {
        $this->nums = $nums;

        // 数组为空，不在数组中
        if (empty($this->nums)) {
            return -1;
        }

        return $this->searchHelper(0, count($this->nums) - 1, $target);
    }

    /**
     * 1. 根据题目的要求看，反转后的数组，旋转点两边一定有一组数据是有序的
     * 2. 我们找到数组的中值，检查中值/最右值/最左值，如果等于目标值，直接返回就好了
     * 3. 走到这里说明target不在以上条件中，我们根据一开始的想法，找到那一组有序的数组，根据中值将整个数组分为两部分。
     *    左边一组的数据拿开始值和中值作比较，开始值小于中值，说明左边是有序的。
     *    找到有序数组后，用target比较有序数组的头和尾，判断是否在有序数组中，如果在，递归查找。
     *    不在有序数组中，用同样的方法找右边的数组。
     * @param int $left
     * @param int $right
     * @param int $target
     * @return int
     */
    private function searchHelper(int $left, int $right, int $target): int
    {
        // 定义出口条件
        if ($right < $left) {
            return -1;
        }

        // 求中值
        $mid = intval(($left + $right) / 2);

        if ($this->nums[$left] == $target) {
            return $left;
        } elseif ($this->nums[$mid] == $target) {
            return $mid;
        } elseif ($this->nums[$right] == $target) {
            return $right;
        }

        // 左半边有序
        if ($this->nums[$left] < $this->nums[$mid]) {
            // 目标值在左半边
            if ($target > $this->nums[$left] && $target <= $this->nums[$mid]) {
                return $this->searchHelper($left + 1, $mid - 1, $target);
            } else {
                // 目标值在右半边
                return $this->searchHelper($mid + 1, $right - 1, $target);
            }
        }

        // 右半边有序
        if ($this->nums[$mid] < $target && $target <= $this->nums[$right]) {
            return $this->searchHelper($mid + 1, $right - 1, $target);
        }

        // 目标值在左半边
        return $this->searchHelper($left + 1, $mid - 1, $target);
    }
}