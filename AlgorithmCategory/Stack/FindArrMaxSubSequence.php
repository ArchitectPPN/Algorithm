<?php

// 从题目[321拼接最大整数]中发现的子问题， 找到数组中，长度为k的最大子序列
class FindArrMaxSubSequence
{
    /**
     * 找出数组中长度为k的最大子序列
     * @param array $arr
     * @param int $k
     * @return array
     */
    public function maxSubsequence(array $arr, int $k): array
    {
        // 获取到数组的长度
        $arrLength = count($arr);
        if ($k < 0 || $k > $arrLength) {
            return [];
        }
        if ($arrLength === 0) {
            return [];
        }
        // 用来存储最后的答案
        $stack = array_fill(0, $k, 0);
        // 使用下标控制的方式来弹出和入栈操作
        $top = -1;
        // 计算还可以去除几个元素
        $remain = $arrLength - $k;

        for ($i = 0; $i < $arrLength; $i++) {
            // 获取当前元素的大小
            $nowNum = $arr[$i];

            // $top >= 0说明栈不为空，
            // 栈不为空，并且当前元素大于栈顶元素，还可以继续删除元素
            while ($top >= 0 && $stack[$top] < $nowNum && $remain > 0) {
                // 通过下标向左移动，来达到出栈的效果
                $top -= 1;
                // 删除掉元素，可删除元素数量减少1
                $remain -= 1;
            }

            // 栈还没满时，可以直接入栈
            if ($top < $k - 1) {
                // 向右移动，入栈
                $top += 1;
                $stack[$top] = $nowNum;
            } else {
                // 不需要该元素，意味着要舍弃，所以减1
                $remain -= 1;
            }
        }

        return $stack;
    }
}


$svc = new FindArrMaxSubSequence();
$arr = [9, 1, 2, 5, 8, 3];
$k = 3;
$ans = $svc->maxSubsequence($arr, $k);
var_dump($ans);

$arr = [1, 9, 2, 5, 8, 3];
$k = 3;
$ans = $svc->maxSubsequence($arr, $k);
var_dump($ans);

$arr = [4, 3, 2, 5, 6];
$k = 3;
$ans = $svc->maxSubsequence($arr, $k);
var_dump($ans);

# 2025年3月3日

/**
 * Thinking
 * 题目要求从arr中找到k个数字来组成最大的子序列
 * 因为只要k个数字，所以arrLength - k个数字都可以被移除
 * 什么样的数字可以被我们移除掉呢？
 * 首先我们要保证我们的数组是最大的，所以栈顶元素小于当前元素且还可以删除元素时，就应该将栈顶元素丢掉
 */
class FindArrMaxSubSequenceReviewOne
{
    /**
     * @param array $arr
     * @param int $k
     * @return array
     */
    public function maxSubsequence(array $arr, int $k): array
    {
        $arrLength = count($arr);
        // 特殊情况过滤
        // 给定的数组为空
        // 给定的数组长度小于k
        // k为0
        if (empty($arr) || $k <= 0 || $k > $arrLength) {
            return [];
        }
        $answerStack = array_fill(0, $k, 0);
        // 可以丢掉的元素个数
        $remain = $arrLength - $k;
        // 栈顶元素下标
        $top = -1;

        for ($i = 0; $i < $arrLength; $i++) {
            // 获取当前元素
            $nowNum = $arr[$i];
            // 栈不为空
            // 栈顶元素小于当前元素
            // 还可以删除元素
            while ($top >= 0 && $answerStack[$top] < $nowNum && $remain > 0) {
                // 出栈
                $top -= 1;
                // 可删除元素-1
                $remain -= 1;
            }

            // 栈内元素个数小于k
            if ($top < $k - 1) {
                // 入栈
                $top += 1;
                $answerStack[$top] = $nowNum;
            } else {
                // 不入栈，就意味着要丢掉该元素，所以可删除元素要减1
                $remain -= 1;
            }
        }

        return $answerStack;
    }
}