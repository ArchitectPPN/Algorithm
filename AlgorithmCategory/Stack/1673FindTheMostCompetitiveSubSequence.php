<?php

# 1673. 找出最具竞争力的子序列 https://leetcode.cn/problems/find-the-most-competitive-subsequence/description/

/**
 * 直接操作栈
 * @author niujunqing
 */
class FindTheMostCompetitiveSubSequence
{
    /**
     * @param Integer[] $nums
     * @param Integer $k
     * @return Integer[]
     */
    function mostCompetitive(array $nums, int $k): array
    {
        // 获取数组长度
        $numsLen = count($nums);
        // 创建一个栈
        $ansStack = [];
        if ($k > $numsLen) {
            return [];
        }

        for ($i = 0; $i < $numsLen; $i++) {
            while (
                $ansStack
                && end($ansStack) > $nums[$i]
                // count($ansStack) + $numsLen - $i 这段要拆开理解
                // $numsLen - $i 表示，从$i的位置开始，还剩下几个元素
                // [1,2,3,4]
                // $i = 0 时， 表示从0的位置开始还有4个元素
                // $i = 1 时， 表示从1的位置开始还有3个元素
                // $i = 2 时， 表示从1的位置开始还有2个元素
                // $i = 3 时， 表示从1的位置开始还有1个元素
                // 剩余元素数量 + 栈内有的数量 > $k 才能保证最后的长度，否则可能会被 end($ansStack) > $nums[$i] 原因弹出，导致长度不够
                && count($ansStack) + $numsLen - $i > $k // 1 + 12 - 1
            ) {
                array_pop($ansStack);
            }

            $ansStack[] = $nums[$i];
        }

        if (empty($ansStack)) {
            return [];
        }
        return array_slice($ansStack, 0, $k);
    }
}

$question = [
    71,
    18,
    52,
    29,
    55,
    73,
    24,
    42,
    66,
    8,
    80,
    2,
];
$k = 3;

$svc = new FindTheMostCompetitiveSubSequence();
$ans = $svc->mostCompetitive($question, $k);
var_dump($ans);

/**
 * 使用下标的方式来操作栈
 * @author niujunqing
 */
class FindTheMostCompetitiveSubSequenceUseIndex
{
    /**
     * @param Integer[] $arr
     * @param Integer $k
     * @return Integer[]
     */
    function mostCompetitive(array $arr, int $k): array
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
            while ($top >= 0 && $stack[$top] > $nowNum && $remain > 0) {
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

$svc = new FindTheMostCompetitiveSubSequenceUseIndex();
$ans = $svc->mostCompetitive($question, $k);
var_dump($ans);

class FindTheMostCompetitiveSubSequenceReviewOne
{
    /**
     * @param Integer[] $arr
     * @param Integer $k
     * @return Integer[]
     */
    function mostCompetitive(array $arr, int $k): array
    {
        $arrLength = count($arr);
        if ($arrLength === 0) {
            return [];
        } else if ($arrLength === $k) {
            return $arr;
        } else if ($arrLength < $k) {
            return [];
        }

        $stack = [];
        for ($i = 0; $i < $arrLength; $i++) {
            while(
                $stack
                && end($stack) > $arr[$i]
                && count($stack) + ($arrLength - $i) > $k
            ) {
                array_pop($stack);
            }
            $stack[] = $arr[$i];
        }

        return $stack;
    }
}

$svc = new FindTheMostCompetitiveSubSequenceReviewOne();
$ans = $svc->mostCompetitive($question, $k);
var_dump($ans);
