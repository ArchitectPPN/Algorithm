<?php

class SubarraySumWithPrefixSumSolution
{
    /**
     * @param Integer[] $nums
     * @param Integer $k
     * @return Integer
     */
    function subarraySum(array $nums, int $k): int
    {
        $ans = 0;

        $len = count($nums);
        for ($i = 0; $i < $len; $i++) {
            $tmpAns = $nums[$i];
            if ($tmpAns === $k) {
                $ans++;
            }
            for ($j = $i + 1; $j < $len; $j++) {
                $tmpAns += $nums[$j];
                if ($tmpAns == $k) {
                    $ans++;
                }
            }
        }

        return $ans;
    }
}

$svc = new SubarraySumWithPrefixSumSolution();
$ans = $svc->subarraySum([1, 2, 3], 3);
var_dump($ans);