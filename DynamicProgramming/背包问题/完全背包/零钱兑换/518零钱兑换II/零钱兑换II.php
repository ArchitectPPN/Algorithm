<?php

namespace CoinChangeIISolution;

/**
 * 动归求方案数
 */
class Solution {

    /**
     * @param Integer $amount
     * @param Integer[] $coins
     * @return Integer
     */
    function change(int $amount, array $coins): int
    {
        // 初始化动归数组
        for ($i = 0; $i <= $amount; $i++) {
            $dp[$i] = 0;
        }
        // 给个空的硬币数组，也认为有一种方案
        $dp[0] = 1;

        // 循环所有的物品
        for ($i = 0; $i < count($coins); $i++) {
            for ($j = $coins[$i]; $j <= $amount; $j++) {
                $dp[$j] += $dp[$j - $coins[$i]];
            }
        }

        return $dp[$amount];
    }
}

$amount = 5;
$coins = [1,2,5];

$solution = new Solution();
$res = $solution->change($amount, $coins);

echo $res;