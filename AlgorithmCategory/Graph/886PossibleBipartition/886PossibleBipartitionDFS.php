<?php

# 886PossibleBipartition. 可能的二分法 https://leetcode.cn/problems/possible-bipartition/

class PossibleBiPartitionSolutionWithDFS
{
    /** @var array 邻接表 */
    private array $g = [];
    private array $color = [];

    /**
     * @param int $n
     * @param array $dislikes
     * @return bool
     */
    public function possibleBipartition(int $n, array $dislikes): bool
    {
        // 初始化邻接表, 把各自不喜欢的人加入到邻接表中
        $this->g = array_fill(0, $n, []);
        foreach ($dislikes as $pair) {
            $x = $pair[0] - 1;
            $y = $pair[1] - 1;
            $this->g[$x][] = $y;
            $this->g[$y][] = $x;
        }

        // 颜色数组：
        // 0=未访问
        // 1 和 -1 是对立的两种颜色, 比如 [1, 3] 1 的颜色是1, 3 的颜色就必须是-1, 如果不是说明不是二分图
        $this->color = array_fill(0, $n, 0);

        for ($i = 0; $i < $n; $i++) {
            if ($this->color[$i] === 0) {
                // 从节点i开始染色，初始颜色为1
                if (!$this->dfs($i, 1)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param int $x
     * @param int $c
     * @return bool
     */
    private function dfs(int $x, int $c): bool
    {
        $this->color[$x] = $c;
        foreach ($this->g[$x] as $y) {
            if ($this->color[$y] === $c) {
                // 相邻节点颜色相同，不是二分图
                return false;
            }

            // 相邻节点颜色不同，继续递归
            if ($this->color[$y] === 0) {
                // 未染色，递归染成-c
                if (!$this->dfs($y, -$c)) {
                    return false;
                }
            }
        }
        return true;
    }
}
$svc = new PossibleBiPartitionSolutionWithDFS();
$svc->possibleBipartition(4, [[1, 2], [1, 3], [2, 4]]);