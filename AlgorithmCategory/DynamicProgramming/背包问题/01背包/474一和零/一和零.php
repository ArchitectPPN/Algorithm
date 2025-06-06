<?php


// 解题思路看：https://leetcode.cn/problems/ones-and-zeroes/solutions/814942/gong-shui-san-xie-xiang-jie-ru-he-zhuan-174wv/
class Solution
{

    /**
     * @param String[] $strArr
     * @param Integer $m
     * @param Integer $n
     * @return Integer
     */
    function findMaxForm(array $strArr, int $m, int $n): int
    {
        // 拿到字符串数组的长度
        $strLen = count($strArr);
        /**
         * 统计每个字符串中包含0和1的个数
         * 代入背包问题的话,可以视为每个字符串都单独作为一个物品,然后价值为1, 0和1可以看成物品的重量
         */
        $cnt = array_fill(0, $strLen, [0 => 0, 1 => 0]);
        foreach ($strArr as $key => $value) {
            $zero = $one = 0;
            for ($i = 0; $i < mb_strlen($value); $i++) {
                if ($value[$i] === "0") {
                    $zero++;
                } else {
                    $one++;
                }
            }
            $cnt[$key][0] = $zero;
            $cnt[$key][1] = $one;
        }

        // 初始化$dp数组
        $dp = array_fill(0, $strLen, array_fill(0, $m + 1, array_fill(0, $n + 1, 0)));
        // 处理只考虑第一间物品的情况
        for ($i = 0; $i <= $m; $i++) {
            for ($j = 0; $j <= $n; $j++) {
                // 背包问题,如果当前背包容量大于等于当前物品的重量,那么可以选择当前物品,背包价值就+1
                $dp[0][$i][$j] = ($i >= $cnt[0][0] && $j >= $cnt[0][1]) ? 1 : 0;
            }
        }

        // 处理后续物品
        for ($k = 1; $k < $strLen; $k++) {
            // 拿出每个物品的重量
            $zero = $cnt[$k][0];
            $one = $cnt[$k][1];

            for ($i = 0; $i <= $m; $i++) {
                for ($j = 0; $j <= $n; $j++) {
                    // 不选该物品，继承上一行的结果
                    $a = $dp[$k - 1][$i][$j];
                    // 可以放下该物品, 选该物品，继承上一行的结果
                    $b = ($i >= $zero && $j >= $one) ? $dp[$k - 1][$i - $zero][$j - $one] + 1 : 0;
                    $dp[$k][$i][$j] = max($a, $b);
                }
            }
        }

        return $dp[$strLen - 1][$m][$n];
    }
}

$solution = new Solution();
$ans = $solution->findMaxForm(["10", "0001", "111001", "1", "0"], 5, 3);

echo $ans . PHP_EOL;