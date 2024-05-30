<?php

namespace ArrayOfRemoveElement;

class Solution
{
    /**
     * @param Integer[] $nums
     * @param Integer $val
     * @return Integer
     */
    function removeElement(array &$nums, int $val): int
    {
        // 首先获取数组的长度
        $numsLen = count($nums);
        $left = 0;
        // 首先题目说不等于val的值我们才需要，等于val的值我们不需要
        // 那么我们循环一遍数组，使用left重置下标，
        // 不等于val的时候，我们left需要向前移动，right的值就会被保留下来，
        // 等于val时，right会向后移动
        // 等再遇到不等于val的值时，left就会向前移动，原先等于val的right就会被覆盖掉
        for ($right = 0; $right < $numsLen; $right++) {
            if ($nums[$right] != $val) {
                $nums[$left] = $nums[$right];
                $left++;
            }
        }

        return $left;
    }
}

$question = [3,2,2,3];

$solution = new Solution();
$ans = $solution->removeElement($question, 3);

echo "ans: " . $ans;