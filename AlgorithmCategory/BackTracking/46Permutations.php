<?php

# 46. 全排列 https://leetcode.cn/problems/permutations/description/

namespace BackTrackingPermuteSolution;

class PermutationsSolution
{
    /** @var array 元素是否使用的数组 */
    private array $inUseFlag = [];

    /** @var array 临时存放答案的数组 */
    private array $tmp = [];

    /** @var array 最后存放答案的数组 */
    private array $ans = [];

    /** @var int 整个数组的长度 */
    private int $length = 0;

    /**
     * @param Integer[] $nums
     * @return Integer[][]
     */
    function permute(array $nums): array
    {
        if (empty($nums)) {
            return [];
        }

        $this->initData($nums);

        $this->find($nums);

        return $this->ans;
    }

    /**
     * @param array $nums
     * @param int $index
     * @return void
     */
    private function find(array $nums, int $index = 0): void
    {
        // 递归结束条件: 递归深度等于数组长度, 递归结束
        if ($index == $this->length) {
            // 临时结果写入最终答案集
            $this->ans[] = $this->tmp;
            return;
        }

        foreach ($nums as $num) {
            // 跳过已使用的元素
            if ($this->inUseFlag[$num]) {
                continue;
            }

            // 标记当前元素为已使用
            $this->inUseFlag[$num] = true;
            // 临时结果数组添加当前元素
            $this->tmp[] = $num;

            // 递归
            $this->find($nums, $index + 1);

            // 回溯
            array_pop($this->tmp);
            // 标记当前元素为未使用
            $this->inUseFlag[$num] = false;
        }
    }

    /**
     * 初始化数据
     * @param array $nums
     * @return void
     */
    private function initData(array $nums): void
    {
        // 初始化长度
        $this->length = count($nums);

        // 初始化标记数组
        foreach ($nums as $num) {
            $this->inUseFlag[$num] = false;
        }
    }
}

$nums = [
    1, 2, 3,
];

$solution = new PermutationsSolution();
$arr = $solution->permute($nums);

echo json_encode($arr);