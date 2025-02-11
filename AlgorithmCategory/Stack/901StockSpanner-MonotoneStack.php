<?php

# 901. 股票价格跨度 https://leetcode.cn/problems/online-stock-span/description/

class StockSpanner
{
    /** @var array 存储价格跨度的stack */
    private array $priceStack;

    public function __construct()
    {
        $this->priceStack = [];
    }

    /**
     * 返回价格的跨度
     * @param int $price
     * @return int
     */
    public function next(int $price): int
    {
        // 因为题目要求小于等于price的价格时, 跨度加1, 所以默认跨度就是1
        $priceSpan = 1;

        // 栈不为空, 并且栈顶元素小于等于当前价格, 跨度加1
        while (!empty($this->priceStack) && $price >= end($this->priceStack)[0]) {
            // 出栈, 将栈顶元素的跨度累加到当前跨度上
            $stackTop = array_pop($this->priceStack);
            $priceSpan += $stackTop[1];
        }

        // 当前价格跨度入栈, 并返回跨度
        $this->priceStack[] = [
            $price,
            $priceSpan,
        ];

        return $priceSpan;
    }
}

$priceList = [
    100,
    80,
    60,
    70,
    60,
    75,
    85,
];

$stockSpanner = new StockSpanner();
foreach ($priceList as $price) {
    echo 'price: ' . $price . ' stockSpan:' .$stockSpanner->next($price) . PHP_EOL;
}