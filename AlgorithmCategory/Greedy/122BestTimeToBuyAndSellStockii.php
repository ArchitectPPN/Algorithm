<?php

# 122. 买卖股票的最佳时机 II https://leetcode.cn/problems/best-time-to-buy-and-sell-stock-ii/

class BestTimeToBuyAndSellStockIISolution
{

    /**
     * 一共有三种操作：
     * 1. 买入
     *  什么时候买入？前一天价格小于后一天价格时
     * 2. 卖出
     *  什么时候卖出？当天价格大于前一天价格时
     * 3. 什么都不做
     *  就是先买入，然后再卖出
     *
     * 这个题目中，我们知道后一天的价格，所以我们就可以从后往前遍历,
     * 只要后一天的价格比前一天的价格高，我们就累加这两天的差值， 那么就能算出最大的利润
     *
     * @param Integer[] $prices
     * @return Integer
     */
    function maxProfit(array $prices): int
    {
        // 最大利润
        $count = count($prices);
        if ($count <= 1) {
            return 0;
        }

        $maxProfit = 0;

        for ($i = $count - 1; $i >= 0; $i--) {
            // 说明已经到头了
            if ($i - 1 < 0) {
                break;
            }

            // 后一天的价格大于前一天，把差值加起来
            if ($prices[$i] > $prices[$i - 1]) {
                $maxProfit += ($prices[$i] - $prices[$i - 1]);
            }
        }

        return $maxProfit;
    }
}


$svc = new BestTimeToBuyAndSellStockIISolution();

$one = [1, 2];
var_dump($svc->maxProfit($one));