<?php

class CombinationSumWithForDfs
{
    /**
     * @param Integer[] $candidates
     * @param Integer $target
     * @return Integer[][]
     */
    function combinationSum(array $candidates, int $target): array
    {
        $ans = [];
        $temp = [];
        $this->dfs($ans, $temp, 0, 0, $candidates, $target);
        return $ans;
    }

    /**
     * 深度优先搜索（DFS）寻找组合总和
     * @param array $ans 存储所有符合条件的组合
     * @param array $temp 当前正在构建的组合
     * @param int $index 起始索引（避免重复组合）
     * @param int $sum 当前组合的总和
     * @param array $candidates 候选数字数组
     * @param int $target 目标总和
     */
    private function dfs(array &$ans, array &$temp, int $index, int $sum, array $candidates, int $target): void
    {
        // 终止条件：当前总和超过或等于目标值
        if ($sum >= $target) {
            // 总和等于目标值时，记录当前组合
            if ($sum == $target) {
                $ans[] = $temp;
            }
            return;
        }

        // 从起始索引开始遍历，避免重复组合（如[2,3]和[3,2]视为同一组合）
        for ($i = $index; $i < count($candidates); $i++) {
            // 选择当前数字，加入临时组合
            $temp[] = $candidates[$i];
            // 递归：索引不变（允许重复选择当前数字），总和累加
            $this->dfs($ans, $temp, $i, $sum + $candidates[$i], $candidates, $target);
            // 回溯：移除最后加入的数字，尝试下一个可能性
            array_pop($temp);
        }
    }
}