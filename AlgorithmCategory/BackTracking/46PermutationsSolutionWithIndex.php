<?php

# 46. 全排列 https://leetcode.cn/problems/permutations/description/

namespace BackTrackingPermuteSolution;

class PermutationsSolutionWithIndex
{
    /** @var array 最终答案 */
    private array $ansAnswer = [];

    /** @var int 数组长度 */
    private int $arrLen = 0;

    /** @var array 临时答案 */
    private array $tmpAnswer = [];

    /** @var array 标记当前是否在使用 */
    private array $inUse = [];

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

        return $this->ansAnswer;
    }

    /**
     * @param array $nums
     * @return void
     */
    private function initData(array $nums): void
    {
        $this->arrLen = count($nums);

        for ($i = 0; $i < $this->arrLen; $i++) {
            $this->inUse[$i] = false;
        }
    }

    /**
     * @param array $nums
     * @param int $len
     * @return void
     */
    private function find(array $nums, int $len = 0): void
    {
        if ($len == $this->arrLen) {
            $this->tmpAnswer && $this->ansAnswer[] = $this->tmpAnswer;
            return;
        }

        // 使用下标来存储，防止值相同的情况
        for ($i = 0; $i < $this->arrLen; $i++) {
            if ($this->inUse[$i]) {
                continue;
            }
            $this->inUse[$i] = true;
            $this->tmpAnswer[] = $nums[$i];

            $this->find($nums, $len + 1);

            array_pop($this->tmpAnswer);
            $this->inUse[$i] = false;
        }
    }
}

$nums = [
    1, 2, 3,
];

$solution = new PermutationsSolutionWithIndex();
$arr = $solution->permute($nums);

echo json_encode($arr);