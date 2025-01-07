<?php
namespace AlgorithmCategory\DynamicProgramming\爬楼梯;

class Solution {

    /**
     * @param Integer $n
     * @return Integer
     */
    function climbStairs(int $n): int
    {
        // 由于该题目限制了 $n 的大小为 1 =< $n <= 45, 所以无需考虑等于0的情况
        // 可以非常清楚的知道, 爬一层和爬两层的答案分别为1 2，所以
        if ($n == 1) return 1;
        if ($n == 2) return 2;

        // 程序走到当前位置，n必定大于等于3
        // 我们就可以认为我们当前在第二步，要往第三层走
        // 那么前一步就是1
        $pre = 1;
        // 当前步就是2
        $cur = 2;
        // 直接从3开始
        for ($i = 3; $i <= $n; $i++) {
            // 第三步的解就是
            $sum = $pre + $cur;
            // 当前步变为上一步
            $pre = $cur;
            // 更新当前步
            $cur = $sum;
        }

        return $cur;
    }
}
