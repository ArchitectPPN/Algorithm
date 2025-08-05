<?php

class CombinationSumWithSelectDfs
{
    /**
     * @param Integer[] $candidates
     * @param Integer $target
     * @return Integer[][]
     */
    function combinationSum(array $candidates, int $target): array
    {
        $ans = [];
        $combine = [];
        $this->dfs($candidates, $target, $ans, $combine, 0);
        return $ans;
    }

    /**
     * 深度优先搜索（DFS）寻找所有符合条件的组合
     * @param array $candidates 候选数字数组
     * @param int $target 目标和
     * @param array $ans 存储所有结果组合
     * @param array $combine 当前正在构建的组合
     * @param int $idx 当前处理的候选数字索引
     */
    private function dfs(array &$candidates, int $target, array &$ans, array &$combine, int $idx): void
    {
        // 终止条件1：遍历完所有候选数字，直接返回
        if ($idx == count($candidates)) {
            return;
        }
        // 终止条件2：当前组合的和等于目标值，记录该组合
        if ($target == 0) {
            $ans[] = $combine;
            return;
        }

        // 情况1：跳过当前数字，直接处理下一个数字
        $this->dfs($candidates, $target, $ans, $combine, $idx + 1);

        // 情况2：选择当前数字（若剩余目标和足够）
        if ($target - $candidates[$idx] >= 0) {
            // 将当前数字加入组合
            $combine[] = $candidates[$idx];
            // 递归处理（允许重复选择当前数字，因此索引仍为idx）
            $this->dfs($candidates, $target - $candidates[$idx], $ans, $combine, $idx);
            // 回溯：移除最后加入的数字，尝试其他可能性
            array_pop($combine);
        }
    }
}