<?php

namespace MaxProfit;

/**
 * 思路：
 *      当前题目为获取最大的利润，只需要交易一次，那我们只需要找到最低的买入价格和最高的卖出价格， 然后就能计算出最大的利润
 *  根据题目要求：
 *      1. 你只能选择 某一天 买入这只股票，并选择在 未来的某一个不同的日子 卖出该股票； 说明你只能进行一次交易
 *      2. 你不能在买入前卖出股票
 */
class Solution {
    /**
     * @param Integer[] $prices
     * @return Integer
     */
    function maxProfit(array $prices): int
    {
        // 默认将第一天的价格作为买入价格
        $buyPrice = $prices[0];
        // 当前的利润
        $profit = 0;

        // 找到最小的买入价格，然后找到最高的价格卖出，获取最大的利润
        foreach ($prices as $index => $price) {
            // 获取最小的买入价格
            $buyPrice = min($buyPrice, $price);
            // 如果当前的价格卖出大于当前的利润，就直接卖出
            $profit = max($profit, $price - $buyPrice);
        }

        // 返回最大的利润
        return $profit;
    }
}