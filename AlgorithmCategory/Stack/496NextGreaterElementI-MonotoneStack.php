<?php

namespace AlgorithmCategory\Stack;

/**
 * 解题思路：哈希表循环
 */
class HashSolution
{
    /**
     * @var int[] 数组元素索引
     */
    private array $nums2Index = [];

    /**
     * @param Integer[] $nums1
     * @param Integer[] $nums2
     * @return Integer[]
     */
    function nextGreaterElement(array $nums1, array $nums2): array
    {
        if (empty($nums1)) {
            return [];
        }

        $this->initIndex($nums2);

        $ans = [];
        foreach ($nums1 as $val) {
            $ans[] = $this->getNextGreaterElement($val, $nums2);
        }

        return $ans;
    }

    private function getNextGreaterElement(int $target, array $nums2): int
    {
        $index = $this->nums2Index[$target];
        $maxIndex = count($nums2) - 1;
        if ($index == $maxIndex) {
            return -1;
        }

        // 从下一个元素开始
        for ($i = $index + 1; $i <= $maxIndex; $i++) {
            if ($nums2[$i] > $target) {
                return $nums2[$i];
            }
        }

        return -1;
    }

    /**
     * 初始化数组下标索引
     *
     * @param array $nums2
     * @return void
     */
    private function initIndex(array $nums2): void
    {
        foreach ($nums2 as $index => $value) {
            $this->nums2Index[$value] = $index;
        }
    }
}

/**
 * 解题思路: 方法二：单调栈 + 哈希表 https://leetcode.cn/problems/next-greater-element-i/solutions/1065517/xia-yi-ge-geng-da-yuan-su-i-by-leetcode-bfcoj/
 * 1. 因为nums1是nums2的子集, 直接将nums2遍历, 寻找每个元素的下一个更大的值
 * 2. 当前元素大于栈顶元素时, 将栈顶元素出栈, 当前元素就是栈顶元素下一个更大的值, 栈为空时入栈
 * 3. 因为要计算每个元素的下一个更大的值, 所以我们建立一个hash表来存储这个信息, key为栈顶元素, value为当前元素
 * 4. 最后循环拿出nums1每个元素对应的下一个更大值
 */
class StackSolution
{
    /**
     * @param Integer[] $nums1
     * @param Integer[] $nums2
     * @return Integer[]
     */
    function nextGreaterElement(array $nums1, array $nums2): array
    {
        if (empty($nums1)) {
            return [];
        }

        $ans = [];
        $valAndNextMaxMapping = $this->getNextMaxValue($nums2);
        foreach ($nums1 as $val) {
            $ans[] = $valAndNextMaxMapping[$val];
        }

        return $ans;
    }

    /**
     * 压栈
     *
     * @param array $nums2
     * @return int[]
     */
    private function getNextMaxValue(array $nums2): array
    {
        /** @var int[] $valAndNextMaxMapping 下一个最大值mapping */
        $valAndNextMaxMapping = [];
        // 倒序循环
        $maxIndex = count($nums2) - 1;

        $stack = [];
        // 循环从倒数第二个元素开始
        for ($i = $maxIndex; $i >= 0; $i--) {
            // 栈不为空且栈顶元素小于当前元素
            while ($stack && $stack[count($stack) - 1] <= $nums2[$i]) {
                array_pop($stack);
            }

            // 为空入栈
            if (empty($stack)) {
                // 说明右侧没有比他大的元素
                $valAndNextMaxMapping[$nums2[$i]] = -1;
            } else {
                $valAndNextMaxMapping[$nums2[$i]] = $stack[count($stack) - 1];
            }

            $stack[] = $nums2[$i];
        }

        return $valAndNextMaxMapping;
    }
}