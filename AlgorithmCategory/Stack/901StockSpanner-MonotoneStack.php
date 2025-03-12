<?php

# 901. 股票价格跨度 https://leetcode.cn/problems/online-stock-span/description/

# 2025年2月11日
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
    80,
    101,
    90,
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
    echo 'price: ' . $price . ' stockSpan:' . $stockSpanner->next($price) . PHP_EOL;
}

# 2025年2月12日

/**
 * 理解:
 * 其实就是在一个数组中, 求小于等于当前元素的个数, 同时满足连续递减的特性.
 * 求出一个之后, 将其结果入栈, 方便后续继续求解.
 * 100, 110, 60, 120
 * 第一个元素为 100, 所以在它之前比它小的数字有0个, 算上自身, 所以结果为1;
 * 第二个元素为 110, 所以在它之前比它小的数字有1个, 计算答案时, 因为知道100的结果, 所以直接将100的结果累加, 就能拿到结果, 最后答案为2;
 * 第三个元素为 60, 所以在它之前比它小的数字有0个, 算上自身, 所以结果为1;
 * 第四个元素为 120, 一眼看上去, 他比所有数字都大, 所以直接累加前面数字的计算结果即可, 最后的答案为4;
 */
class StockSpannerReviewOne
{
    /** @var array 价格栈 */
    private array $priceStack = [];

    /**
     * @param int $price
     * @return int
     */
    public function next(int $price): int
    {
        // 声明当前的结果, 默认为1, 因为包含自身
        $priceSpan = 1;
        // 栈不为空, 并且栈顶元素小于当前价格, 就需要累加之前的计算结果
        while (!empty($this->priceStack) && $price >= end($this->priceStack)[0]) {
            $top = array_pop($this->priceStack);
            $priceSpan += $top[1];
        }

        // 将当前价格对应的结果入栈, 方便后续计算
        $this->priceStack[] = [
            $price,
            $priceSpan,
        ];

        return $priceSpan;
    }
}

# 2025年2月16日
class StockSpannerReviewThree
{
    /** @var array 价格栈 */
    private array $priceStack = [];

    /**
     * @param int $price
     * @return int
     */
    public function next(int $price): int
    {
        // 因为题目要求小于等于今天的价格，所以跨度默认就是1
        $priceSpanner = 1;

        // 栈不为空，当前元素大于等于栈顶元素
        while (!empty($this->priceStack) && $price >= end($this->priceStack)[0]) {
            // 出栈
            $top = array_pop($this->priceStack);
            $priceSpanner += $top[1];
        }

        // 入栈
        $this->priceStack[] = [
            $price,
            $priceSpanner,
        ];

        return $priceSpanner;
    }
}

# 2025年2月17日

/**
 * 思路: 题目要求是小于等于当前的价格, 所以默认跨度就是自身, 就是1; 而且是从后往前找, 从大到小, 符合单调递减的特性;
 * 从前遍历, 例如: 100, 80, 90, 60 入栈顺序为: 100, 80, 遇到90时, 80先出栈, 符合后进先出的特性;
 * @author
 */
class StockSpannerReviewThreeAgain
{
    /** @var array 价格栈 */
    private array $priceStack = [];

    /**
     * @param int $price
     * @return int
     */
    public function next(int $price): int
    {
        // 题目要求小于等于当前价格, 所以跨度默认为1
        $defaultSpan = 1;

        // 栈不为空, 当前价格大于等于栈顶价格
        // 要一直将符合条件的元素出栈, 直到不符合条件为止, 所以要使用while
        while (!empty($this->priceStack) && $price >= end($this->priceStack)[0]) {
            // 栈顶元素出栈
            $top = array_pop($this->priceStack);
            // 将小于当前价格的跨度累加到当前跨度上, 例如: 100 60 70 80 90, 当前价格为90时, 60 70 80 都小于90, 所以需要将60 70 80的跨度累加到90的跨度上
            $defaultSpan += $top[1];
        }

        // 入栈
        $this->priceStack[] = [
            $price,
            $defaultSpan,
        ];

        return $defaultSpan;
    }
}

# 2025年2月18日

/**
 * 思路:
 * 题目要去从后往前看, 小于等于当前价格, 跨度+1; 所以默认跨度就是1;
 * @author niujunqing
 */
class StockSpannerReviewFour
{
    /** @var array 价格栈 */
    private array $priceStack = [];

    /**
     * @param int $price
     * @return int
     */
    public function next(int $price): int
    {
        // 小于等于当前价格, 默认跨度为1
        $defaultSpan = 1;

        // 栈不为空, 栈顶元素小于等于当前价格, 跨度累加
        while (!empty($this->priceStack) && $price >= end($this->priceStack)[0]) {
            $top = array_pop($this->priceStack);
            $defaultSpan += $top[1];
        }

        // 将结果入栈
        $this->priceStack[] = [
            $price,
            $defaultSpan,
        ];

        return $defaultSpan;
    }
}

/**
 * Thinking:
 * 从前往后看, 小于等于当前价格, 跨度+1
 * 单调递增栈
 * @author niujunqing
 */
class StockSpannerReviewFive
{
    /** @var SplStack 单调递减栈 */
    private SplStack $priceStack;

    public function __construct()
    {
        $this->priceStack = new SplStack();
    }

    /**
     * @param $price
     * @return int
     */
    public function next($price): int
    {
        $defaultSpan = 1;

        // 当前价格大于等于栈顶元素, 跨度累加
        while (!$this->priceStack->isEmpty() && $price >= $this->priceStack->top()[0]) {
            $top = $this->priceStack->pop();
            $defaultSpan += $top[1];
        }

        // 将结果入栈
        $this->priceStack->push(
            [
                $price,
                $defaultSpan,
            ]
        );

        return $defaultSpan;
    }
}

/**
 * THINKING:
 * 1. 从前往后看, 找到比今天小的价格, 然后跨度+1, 找到比今天大的, 直接break;
 * 2. 不能跨天
 * @author niujunqing
 */
class StockSpannerReviewSix
{
    /** @var SplStack */
    private SplStack $priceStack;

    public function __construct()
    {
        $this->priceStack = new SplStack();
    }

    public function next($price): int
    {
        // 因为今天也算, 所以默认跨度就是1
        $defaultSpan = 1;

        // 一旦涉及到循环, 就要考虑栈是否为空, 所以很自然就能写出
        // 当前的价格大于等于栈顶元素, 把栈顶的元素的价格累加
        while (!$this->priceStack->isEmpty() && $price >= $this->priceStack->top()[0]) {
            $defaultSpan += $this->priceStack->pop()[1];
        }

        // 入栈的元素为, 当前价格 和 跨度
        $this->priceStack->push([
            $price,
            $defaultSpan,
        ]);

        return $defaultSpan;
    }
}

var_dump("StockSpannerReviewSix");

$stockSpanner = new StockSpannerReviewSix();
foreach ($priceList as $price) {
    echo 'price: ' . $price . ' stockSpan:' . $stockSpanner->next($price) . PHP_EOL;
}