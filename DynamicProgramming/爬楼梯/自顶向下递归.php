<?php
namespace ClimbStairs;

class Solution {
    /**
     * @var array 备忘录
     */
    private array $mem = [];

    /**
     * 爬楼梯
     *
     * @param Integer $n
     * @return Integer
     */
    function climbStairs(int $n): int
    {
        return $this->dfs($n);
    }

    /**
     * 计算
     *
     * @param int $n
     * @return int
     */
    private function dfs(int $n): int
    {
        if ($n == 0 || $n == 1) return 1;
        if ($n == 2) return 2;
        if (isset($this->mem[$n])) {
            return $this->mem[$n];
        }

        $this->mem[$n] = $this->dfs($n - 1) + $this->dfs($n - 2);

        return $this->mem[$n];
    }
}
