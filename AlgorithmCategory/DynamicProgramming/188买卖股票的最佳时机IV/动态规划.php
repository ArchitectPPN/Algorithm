<?php

class Solution
{
    function maxProfit($c, $pricesInput)
    {
        $n = count($pricesInput);

        // Move prices from [0..n-1] to [1..n]
        $prices = array();
        $prices[] = 0;
        for ($i = 1; $i <= $n; $i++) $prices[] = $pricesInput[$i - 1];

        // Initialize DP array
        $f = array();
        for ($i = 0; $i <= $n; $i++) {
            for ($j = 0; $j <= 1; $j++) {
                for ($k = 0; $k <= $c; $k++) {
                    $f[$i][$j][$k] = -1000000000;
                }
            }
        }
        $f[0][0][0] = 0;

        // DP
        for ($i = 1; $i <= $n; $i++) {
            for ($j = 0; $j <= 1; $j++) {
                for ($k = 0; $k <= $c; $k++) {
                    $f[$i][$j][$k] = $f[$i - 1][$j][$k];
                    if ($j == 0)
                        $f[$i][0][$k] = max($f[$i][0][$k], $f[$i - 1][1][$k] + $prices[$i]);
                    if ($j == 1 && $k > 0)
                        $f[$i][1][$k] = max($f[$i][1][$k], $f[$i - 1][0][$k - 1] - $prices[$i]);
                }
            }
        }

        // Calculate the target
        $ans = 0;
        for ($k = 0; $k <= $c; $k++) $ans = max($ans, $f[$n][0][$k]);
        return $ans;
    }
}

$question = [2, 4, 1];

$solution = new Solution();
$maxProfit = $solution->maxProfit(2, $question);

var_dump($maxProfit);
