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
                // 栈已经满了，丢掉元素
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