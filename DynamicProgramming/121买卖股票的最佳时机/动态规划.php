<?php

namespace MaxProfitWithDynamicProgramming;

class Solution
{
    /**
     * @param Integer[] $prices
     * @return Integer
     */
    function maxProfit(array $prices): int
    {
        // 获取当前股票价格每日价格数组
        $length = count($prices);
        // 如果每日价格小于2个，也就是1个或者0个时，就说明不会有收益了
        // 0个价格时不会产生收益
        // 只有一个时，买入无法卖出，也不会产生收益
        if ($length < 2) {
            return 0;
        }
        $dp = [];
        // 初始化第一天的状况
        $dp[0][0] = 0; // 第一天未持有股票时，收益自然为0
        $dp[0][1] = -$prices[0]; // 第一天持有股票时，收益就是买入股票所需要的花费

        // 使用二维数组表示当前买入和卖出时的收益dp[i][j],
        // i表示当前是第几天，j表示当前持有和不持有股票
        // 当前的收益 = 买入时的价格 - 卖出时的价格
        // 买入时的价格

        // 所以我们从第二天开始
        for ($i = 1; $i < $length; $i++) {
            // 当天不持有股票时，也就是卖出
            // 卖出时，正常的逻辑是 卖出价格 - 买入价格 = 利润
            // 由于当前持有该支股票我们已经花费了金钱购入，
            // 想要获得收益，就需要加上当天卖出时的价格，
            // 这里max换一下思路理解，如果当天的价格+买入时的花费，就可以卖出
            // $dp[$i - 1][0] 这个值比较大的话，就说明，我什么都不做的收益比较大
            // $dp[$i - 1][1] + $prices[$i] 说明我今天卖掉的收益更大
            $dp[$i][0] = max($dp[$i - 1][0], $dp[$i - 1][1] + $prices[$i]);

            // 今天的价格比昨天买入金额花费少，那就可以买入
            // $dp[$i - 1][1] 这个大说明，今天什么都不做，买入的价格要更低
            // -$prices[$i] 这个大说明，今天的价格买入会比较低
            $dp[$i][1] = max($dp[$i - 1][1], -$prices[$i]);
        }

        return $dp[$length - 1][0];
    }
}

$question = [7, 1, 5, 3, 6, 4];
$maxProfit = (new Solution())->maxProfit($question);

var_dump($maxProfit);