<?php

namespace CoinChangeISolution;

/**
 *
 */
class Solution {

    /**
     * @param Integer $amount
     * @param Integer[] $coins
     * @return Integer
     */
    function change(int $amount, array $coins): int
    {

    }
}

$amount = 5;
$coins = [1,2,5];

$solution = new Solution();
$res = $solution->change($amount, $coins);

echo $res;