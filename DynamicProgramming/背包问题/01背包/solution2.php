<?php

namespace ZeroOneBagQuestion;

$weight = [1, 3, 4];   // 各个物品的重量
$value = [15, 20, 30]; // 对应的价值
$bagWeight = 4;         // 背包最大能放下多少重的物品

// 二维数组：状态定义:dp[i][j]表示从0-i个物品中选择不超过j重量的物品的最大价值
$dp = array_fill(0, count($weight) + 1, array_fill(0, $bagWeight + 1, 0));

// 初始化:第一列都是0，第一行表示只选取0号物品最大价值
for ($j = $bagWeight; $j >= $weight[0]; $j--) {
    $dp[0][$j] = $dp[0][$j - $weight[0]] + $value[0];
}

// weight数组的大小 就是物品个数
for ($i = 1; $i < count($weight); $i++) { // 遍历物品(第0个物品已经初始化)
    for ($j = 0; $j <= $bagWeight; $j++) { // 遍历背包容量
        if ($j < $weight[$i]) {           // 背包容量已经不足以拿第$i个物品了
            $dp[$i][$j] = $dp[$i - 1][$j]; // 最大价值就是拿第$i-1个物品的最大价值
        } else {                           // 背包容量足够拿第$i个物品,可拿可不拿
            $dp[$i][$j] = max($dp[$i - 1][$j], $dp[$i - 1][$j - $weight[$i]] + $value[$i]);
        }
    }
}

echo $dp[count($weight) - 1][$bagWeight]; // 输出结果

