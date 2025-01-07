<?php

namespace AlgorithmCategory\Array\DoublePointer;

/**
 * 这道题和27题很类似，26题是给定了指定的元素，然后删除
 * 26题并未给指定的target，然后让删除掉重复的元素
 */
class Solution
{
    /**
     * 删除重复的元素, 这个解法开辟了额外的空间
     *
     * @param array $nums
     * @return int
     */
    function removeDuplicates(array &$nums): int
    {
        // 初始化一个map，然后map的key为nums的value，map的value为nums出现的次数
        $map = [];
        $left = 0;

        for ($i = 0; $i < count($nums); $i++) {
            if (!isset($map[$nums[$i]])) {
                $nums[$left] = $nums[$i];
                $left += 1;
            }

            $map[$nums[$i]] = 1;
        }

        return $left;
    }

    /**
     * 解法2， 使用双指针解决
     *
     * @param array $nums
     * @return int
     */
    function removeDuplicatesStartOne(array &$nums): int
    {
        // 进行特判
        if (count($nums) == 0) {
            return 0;
        }

        /**
         * 问题1: 为什么$slow要从1开始，而不是从0开始
         * 指针从1开始，代表的就是元素个数了，如果从0开始表示的为元素下标，最后的答案还要再+1
         * 问题2: $nums[$quick] != $nums[$slow - 1]， 为什么快指针要和慢指针的前一个元素比较？
         * 一开始指针都指向1，如果慢指针不减1，那么下标为0的元素就会被漏掉，从而导致答案错误，
         * 其实慢指针当前的位置可以来表示能被覆盖掉的位置，这个需要结合图来看
         */
        $slow = 1;
        for ($quick = 1; $quick < count($nums); $quick++) {
            if ($nums[$quick] != $nums[$slow - 1]) {
                $nums[$slow] = $nums[$quick];
                $slow += 1;
            }
        }

        return $slow;
    }

    /**
     * 解法2， 使用双指针解决, 慢指针从0开始
     *
     * @param array $nums
     * @return int
     */
    function removeDuplicatesStartZero(array &$nums): int
    {
        // 进行特判
        if (count($nums) == 0) {
            return 0;
        }

        /**
         * 问题1: 为什么$slow要从1开始，而不是从0开始
         * 慢指针可以从0开始，指针从0开始的话，就是代表下标的值，最后的答案需要+1
         * 进行赋值计算时，指针需要加1，覆盖掉slow指针的前一个位置，这样保证整个数组的相对位置没有发生改变
         */
        $slow = 0;
        for ($quick = 1; $quick < count($nums); $quick++) {
            if ($nums[$quick] != $nums[$slow]) {
                $slow += 1;
                $nums[$slow] = $nums[$quick];
            }
        }

        return $slow + 1;
    }
}

$question = [1, 1, 2];

$solution = new Solution();
$ans = $solution->removeDuplicatesStartZero($question);

echo "ans: " . $ans;