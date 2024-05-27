<?php

namespace CoinChangeISolution;

/**
 *
 */
class Solution {

    /**
     * @param Integer $amount
     * @param Integer[] $coins
     * @return Integer
     */
    function change(int $amount, array $coins): int
    {
        // 初始化dp数组
        $dp = array_fill(0, $amount + 1, -1);
        $dp[0] = 0;

        for ($j = 1; $j <= $amount; $j++) {
            for ($i = 0; $i < count($coins); $i++) {
                // 目标金额大于硬币面值，并且dp[i-1]不等于-1，说明可以兑换
                if ($j >= $coins[$i] && $dp[$j - $coins[$i]] !== -1) {
                    // dp[j] == -1说明dp[j]还没有被赋值或者$dp[$j]所需硬币数大于dp[j-coins[$i]] + 1
                    if ($dp[$j] == -1 || $dp[$j] > $dp[$j - $coins[$i]] + 1) {
                        $dp[$j] = $dp[$j - $coins[$i]] + 1;
                    }
                }
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