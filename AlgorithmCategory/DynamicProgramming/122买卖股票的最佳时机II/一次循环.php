<?php


class solution {
    /**
     * @param $prices
     * @return integer
     */
    public function maxProfit($prices): int
    {
        // 最大利润
        $maxProfit = 0;
        if (count($prices) < 2) {
            return $maxProfit;
        }

        // 只要前一天的价格小于当前的价格，我们就可以卖出然后获得收益
        for ($i = 1; $i < count($prices); $i++) {
            // 前一天的价格小于当天价格即刻卖出
            $maxProfit += max($prices[$i] - $prices[$i-1], 0);
        }

        return $maxProfit;
    }
}
