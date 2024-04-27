<?php

namespace MaxProfitWithDynamicProgrammingII;

class Solution
{
    public function maxProfit($prices) {
        $length =count($prices);
        if ($length < 2) {
            return 0;
        }

        // 认为第一天不持有股票时收益为0
        $dp[0][0] = 0;
        // 持有股票的收益就是当天股票的花费
        $dp[0][1] = -$prices[0];

        for ($i = 1; $i < $length; $i++) {
            // 未持有股票的收益计算：
            // 前一天未持有股票时的收益
            // 前一天卖出股票的收益
            $dp[$i][0] = max($dp[$i-1][0], $dp[$i-1][1] + $prices[$i]);
            // 持有股票的收益计算：
            // $dp[$i-1][1], 不作买卖操作
            // $dp[$i-1][0] - $prices[$i], 买入
            $dp[$i][1] = max($dp[$i-1][1], $dp[$i-1][0] - $prices[$i]);
        }

        return $dp[$length - 1][0];
    }
}

$question = [7,1,5,3,6,4];

$solution = new Solution();
$maxProfit = $solution->maxProfit($question);

var_dump($maxProfit);
