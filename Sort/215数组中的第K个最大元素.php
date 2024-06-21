<?php

namespace FindKthLargest;

// 暴力解法
// 先对数组进行排序,然后返回k-1个元素
class BruteForceSolution {
    /**
     * @param Integer[] $nums
     * @param Integer $k
     * @return Integer
     */
    function findKthLargest(array $nums, int $k) : int {
        return 0;
    }
}

class quickSelectSolution
{
    /**
     * @var int[] $nums
     */
    private array $nums;

    /**
     * @param Integer[] $nums
     * @param Integer $k
     * @return Integer
     */
    function findKthLargest(array $nums, int $k) :int {
        $this->nums = $nums;

        $arrLen = count($nums);
        return $this->quickSelect(0, $arrLen - 1, $arrLen - $k);
    }

    /**
     * 对两个元素进行交换
     *
     * @param int $left
     * @param int $right
     * @return void
     */
    private function swap(int $left, int $right): void
    {
        if ($left == $right) {
            return ;
        }

        $tmp = $this->nums[$left];
        $this->nums[$left] = $this->nums[$right];
        $this->nums[$right] = $tmp;
    }

    /**
     * 将数组元素按按照轴值分为两部分
     * 比轴值大的放到右边
     * 比轴值小的放到左边
     *
     * @param int $left
     * @param int $right
     * @param int $pivotIndex
     * @return int
     */
    private function partition(int $left, int $right, int $pivotIndex): int {
        // 拿到轴值
        $pivotVal = $this->nums[$pivotIndex];
        // 将轴值放到最后
        $this->swap($pivotIndex, $right);
        // 记录左边现在移动到哪个位置了
        $storeIndex = $left;

        // 开始遍历, 将小的放到左边, 大的放到右边
        for ($i = $left; $i <= $right; $i++) {
            // 如果当前元素比轴值小, 就交换
            if ($this->nums[$i] < $pivotVal) {
                $this->swap($i, $storeIndex);
                $storeIndex++;
            }
        }

        // 将轴值放到该在的位置
        $this->swap($right, $storeIndex);

        // 返回轴值的下标
        return $storeIndex;
    }

    private function quickSelect(int $left, int $right, int $k): int {
        // 递归前, 先定义递归的出口
        // 两边相等, 终止
        if ($left == $right) {
            return $this->nums[$left];
        }

        // 生成随机的轴值下标, 这里的轴值只是一个随机的下标
        $pivotIndex = $left + rand(0, $right - $left);
        // 这里进行分组之后, 就能拿到上述随机下标对应值当前所在数组中的下标, 这句话比较绕, 建议画个图理解一下
        $pivotIndex = $this->partition($left, $right, $pivotIndex);

        if ($k == $pivotIndex) {
            return $this->nums[$pivotIndex];
        } else if ($k < $pivotIndex) {
            // $k 小于中间值, 说明要找的值在左边
            return $this->quickSelect($left, $pivotIndex - 1, $k);
        }

        // 最后, 说明要找的值在右边
        return $this->quickSelect($pivotIndex + 1, $right, $k);
    }
}

class HeapSolution
{
    /**
     * @param Integer[] $nums
     * @param Integer $k
     * @return Integer
     */
    function findKthLargest(array $nums, int $k) : int {
        return 0;
    }
}
