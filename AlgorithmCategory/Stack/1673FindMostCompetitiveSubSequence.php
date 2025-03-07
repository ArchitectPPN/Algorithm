<?php

class FindMostCompetitiveSubSequence
{
    /**
     * @param Integer[] $nums
     * @param Integer $k
     * @return Integer[]
     */
    function mostCompetitive(array $nums, int $k): array
    {
        // 获取数组长度
        $numsLen = count($nums);
        // 创建一个栈
        $ansStack = [];
        if ($k > $numsLen) {
            return [];
        }

        for ($i = 0; $i < $numsLen; $i++) {
            while (
                $ansStack
                && end($ansStack) > $nums[$i]
                // 这个位置不能是 >= $k, 因为在刚好满足填满 $k 时, 如果栈顶元素大于当前元素, 就会被弹出, 这样剩下的元素 + 栈内的元素就无法填满 $k, 所以需要 count($ansStack) + $numsLen - $i > $k
                && count($ansStack) + $numsLen - $i > $k
            ) {
                array_pop($ansStack);
            }

            $ansStack[] = $nums[$i];
        }

        if (empty($ansStack)) {
            return [];
        }
        return array_slice($ansStack, 0, $k);
    }
}

$question = [
    71,
    18,
    52,
    29,
    55,
    73,
    24,
    42,
    66,
    8,
    80,
    2,
];
$k = 3;

$svc = new FindMostCompetitiveSubSequence();
$ans = $svc->mostCompetitive($question, $k);
var_dump($ans);