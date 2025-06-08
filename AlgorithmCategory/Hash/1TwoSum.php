<?php

# 1. 两数之和 https://leetcode.cn/problems/two-sum/description/

class SolutionWithHash
{
    /**
     * @param Integer[] $nums
     * @param Integer $target
     * @return Integer[]
     */
    function twoSum(array $nums, int $target): array
    {
        $hashTable = [];
        foreach ($nums as $index => $value) {
            if (isset($hashTable[$target - $value])) {
                return [$hashTable[$target - $value], $index];
            }
            $hashTable[$value] = $index;
        }
        return [];
    }
}

$questions = [
    [[2, 7, 11, 15], 9],
    [[3, 2, 4], 6],
    [[3, 3], 6],
];

$svc = new SolutionWithHash();
foreach ($questions as $question) {
    $ans = $svc->twoSum($question[0], $question[1]);
    var_dump($ans);
}
