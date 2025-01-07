<?php

class Solution
{
    /**
     * 二维dp数组
     *
     * @param int $bagWeight 背包的容量
     * @param int $goodsNumber 物品的数量
     * @param array $goodsValue 物品的价值
     * @param array $goodsWeight 物品的所需容量
     * @return int
     */
    public function answerWhitTwoDimensional(int $bagWeight, int $goodsNumber, array $goodsValue, array $goodsWeight): int
    {
        $dp = [];
        // 初始化dp
        for ($i = 0; $i <= $goodsNumber; $i++) {
            for ($j = 0; $j <= $bagWeight; $j++) {
                // 其他都设置为不合法
                $dp[$i][$j] = PHP_INT_MIN;
                if ($i == 0) {
                    // 0个物品时无论背包容量是多少,价值都为0
                    $dp[0][$j] = 0;
                } else if ($j == 0) {
                    // 背包容量为0时,无论多少个物品,价值都为0
                    $dp[$i][0] = 0;
                }
            }
        }

        $maxVal = 0;
        // 因为0个物品或者背包容量为0时,价值都是0, 是已知的,所以我们直接从1开始
        for ($i = 1; $i <= $goodsNumber; $i++) {
            for ($j = 0; $j <= $bagWeight; $j++) {
                // 背包放不下该物品
                if ($j >= $goodsWeight[$i - 1]) {
                    // $goodsValue[$i-1] 因为$i从1开始的，所以要减1，这样才能和$goodsValue的index对起来
                    $dp[$i][$j] = max($dp[$i][$j], $dp[$i - 1][$j - $goodsWeight[$i - 1]] + $goodsValue[$i - 1]);
                } else {
                    // 放不下，直接拿上一行的结果
                    $dp[$i][$j] = $dp[$i - 1][$j];
                }

                $maxVal = max($maxVal, $dp[$i][$j]);
            }
        }

        return $maxVal;
    }

    /**
     * 一维dp数组
     *
     * @param int $bagWeight 背包的容量
     * @param int $goodsNumber 物品的数量
     * @param array $goodsValue 物品的价值
     * @param array $goodsWeight 物品的所需容量
     * @return int
     */
    public function answerWhitOneDimensional(int $bagWeight, int $goodsNumber, array $goodsValue, array $goodsWeight): int
    {
        // 初始化dp
        $dp = array_fill(0, $bagWeight + 1, 0);

        // 开始循环
        for ($i = 0; $i < $goodsNumber; $i++) {
            for ($j = $bagWeight; $j >= $goodsWeight[$i]; $j--) {
                $dp[$j] = max($dp[$j], $dp[$j - $goodsWeight[$i]] + $goodsValue[$i]);
            }
        }

        return $dp[$bagWeight];
    }
}

$goodsNum = 3;
$goodsVal = [5, 2, 6];
$goodsCap = [2, 1, 4];
$bagCap = 4;

$solution = new Solution();
// 二维数组解法
echo $solution->answerWhitOneDimensional($bagCap, $goodsNum, $goodsVal, $goodsCap);
echo PHP_EOL . "--" . PHP_EOL;
// 一维数组解法
echo $solution->answerWhitTwoDimensional($bagCap, $goodsNum, $goodsVal, $goodsCap);
