<?php

namespace AlgorithmCategory\Stack\DailyTemperatures;

class Solution
{
    /**
     * @param array $temperatures
     * @return array
     */
    public function dailyTemperatures(array $temperatures): array
    {
        $len = count($temperatures);
        $answer = array_fill(0, $len, 0);
        $stack = [];

        for ($i = 0; $i < $len; $i++) {
            // 栈顶元素小于当前元素, 则出栈
            while ($stack && $temperatures[$stack[count($stack) - 1]] < $temperatures[$i]) {
                $stackTopIndex = array_pop($stack);
                $answer[$stackTopIndex] = $i - $stackTopIndex;
            }

            // 入栈
            $stack[] = $i;
        }

        return $answer;
    }
}

$solution = new Solution();
$ans = $solution->dailyTemperatures([73, 74, 75, 71, 69, 72, 76, 73]);
var_dump($ans);