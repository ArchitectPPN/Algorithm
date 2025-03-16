<?php

# 78. 子集 https://leetcode.cn/problems/subsets/

namespace SubsetsSolution;

class Solution
{
    /** @var array 最终的答案 */
    private array $ans = [];

    /** @var array 存放临时答案 */
    private array $temp = [];

    /** @var int 数组长度 */
    private int $arrayLength = 0;


    /**
     * @param Integer[] $nums
     * @return Integer[][]
     */
    function subsets(array $nums): array
    {
        $this->arrayLength = count($nums);

        $this->findSubsets($nums);

        return $this->ans;
    }

    /**
     * 回溯
     * @param array $nums
     * @param int $index
     * @return void
     */
    private function findSubsets(array $nums, int $index = 0): void
    {
        // 定义递归条件, 当下标等于数组长度时, 递归结束
        if ($index == $this->arrayLength) {
            $this->ans[] = $this->temp;
            return;
        }

        // 一开始不选择当前元素
        $this->findSubsets($nums, $index + 1);
        // 选择当前元素
        $this->temp[] = $nums[$index];
        $this->findSubsets($nums, $index + 1);
        array_pop($this->temp);
    }
}

$solution = new Solution();
$ans = $solution->subsets(
    [
        1,0
    ]
);
var_dump($ans);