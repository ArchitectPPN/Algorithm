<?php

/**
 * 归并排序是一种采用分治法（Divide and Conquer）的排序算法。
 * 整体思路是将一个待排序的数组不断地分成两个子数组，
 * 分别对这两个子数组进行排序，然后将排好序的子数组合并成一个最终的有序数组。
 * 具体步骤如下：
 * 1. 分解（Divide）：将数组从中间分成两个子数组，递归地对这两个子数组进行同样的分解操作，
 *    直到每个子数组只有一个元素（单个元素的数组本身就是有序的）。
 * 2. 解决（Conquer）：对于每个子数组，由于只有一个元素，无需排序。
 * 3. 合并（Merge）：将排好序的子数组合并成一个更大的有序数组，不断重复这个合并过程，
 *    最终得到一个完整的有序数组。
 */

class MergeSort
{
    /**
     * 对传入的数组进行归并排序
     * @param array $arr 待排序的数组
     * @return array 排序好的数组
     */
    public function sort(array $arr): array
    {
        // 如果数组元素数量小于等于 1，说明数组已经有序，直接返回
        if (count($arr) <= 1) {
            return $arr;
        }
        // 计算数组的中间位置，用于分割数组
        $mid = intval(count($arr) / 2);
        // 分割数组为左半部分，从索引 0 到中间位置（不包含中间位置元素）
        $left = array_slice($arr, 0, $mid);
        // 分割数组为右半部分，从中间位置到数组末尾
        $right = array_slice($arr, $mid);

        // 递归调用 sort 方法对左半部分数组进行排序
        $left = $this->sort($left);
        // 递归调用 sort 方法对右半部分数组进行排序
        $right = $this->sort($right);

        // 调用 merge 方法将排序好的左右两部分数组合并
        return $this->merge($left, $right);
    }

    /**
     * 合并两个已排序的数组
     * @param array $left 左半部分已排序的数组
     * @param array $right 右半部分已排序的数组
     * @return array 合并后的有序数组
     */
    private function merge(array $left, array $right): array
    {
        // 初始化一个空数组，用于存储合并后的结果
        $result = [];
        // 初始化左数组的索引指针
        $i = 0;
        // 初始化右数组的索引指针
        $j = 0;

        // 当左数组和右数组都还有元素未处理时，进行比较合并
        while ($i < count($left) && $j < count($right)) {
            // 如果左数组当前元素小于右数组当前元素
            if ($left[$i] < $right[$j]) {
                // 将左数组当前元素添加到结果数组中
                $result[] = $left[$i];
                // 左数组索引指针向后移动一位
                $i++;
            } else {
                // 否则将右数组当前元素添加到结果数组中
                $result[] = $right[$j];
                // 右数组索引指针向后移动一位
                $j++;
            }
        }

        // 若左数组还有剩余元素，将其依次添加到结果数组末尾
        while ($i < count($left)) {
            $result[] = $left[$i];
            $i++;
        }

        // 若右数组还有剩余元素，将其依次添加到结果数组末尾
        while ($j < count($right)) {
            $result[] = $right[$j];
            $j++;
        }

        // 返回合并后的有序数组
        return $result;
    }
}

// 创建 MergeSort 类的实例
$sort = new MergeSort();
// 定义一个待排序的测试数组
$testArray = [38, 27, 43, 3, 9, 82, 10];
// 调用 sort 方法对测试数组进行排序
$sortedArray = $sort->sort($testArray);
// 打印排序好的数组
print_r($sortedArray);
