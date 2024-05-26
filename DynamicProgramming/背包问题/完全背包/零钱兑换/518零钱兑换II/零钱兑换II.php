<?php

namespace CoinChangeIISolution;

/**
 * 动归求方案数
 */
class Solution
{
    /**
     * @param Integer $amount
     * @param Integer[] $coins
     * @return Integer
     */
    function changeWithOneDimensional(int $amount, array $coins): int
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
                $dp[$j] = $dp[$j] + $dp[$j - $coins[$i]];
            }
        }

        return $dp[$amount];
    }

    /**
     * @param Integer $amount
     * @param Integer[] $coins
     * @return Integer
     */
    function changeWithTwoDimensional(int $amount, array $coins): int
    {
        $totalCoins = count($coins);
        // 初始化动归数组
        $dp = array_fill(0, count($coins) + 1, array_fill(0, $amount + 1, 0));
        // 选择前0个硬币，组成总金额为0的方案数，就是拿一个0元硬币
        for ($i = 0; $i <= $totalCoins; $i++) {
            // 选择前i个硬币，组成总金额为0的方案数，就是拿一个0元硬币
            $dp[$i][0] = 1;
        }

        // 循环所有的物品
        for ($i = 1; $i <= $totalCoins; $i++) {
            for ($j = 1; $j <= $amount; $j++) {
                // 如果当前硬币价值大于总金额，则无法组成总金额，则方案数就是前一个硬币的方案数
                if ($j < $coins[$i - 1]) {
                    $dp[$i][$j] = $dp[$i - 1][$j];
                } else {
                    // 否则，当前硬币价值小于等于总金额，则方案数就是前一个硬币的方案数 + 当前硬币的方案数
                    $dp[$i][$j] = $dp[$i - 1][$j] + $dp[$i][$j - $coins[$i - 1]];
                }
            }
        }

        return $dp[$totalCoins][$amount];
    }
}

// TestCase1:
$amount = 5;
$coins = [1, 2, 5];

// TestCase2:
$coins = [1, 2, 3];

$solution = new Solution();
//$res = $solution->changeWithOneDimensional($amount, $coins);
//echo "一维dp方案数：" . $res .PHP_EOL;

$res = $solution->changeWithTwoDimensional($amount, $coins);
echo "二维dp方案数：" . $res;