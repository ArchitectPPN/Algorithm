<?php

# 886PossibleBipartition. 可能的二分法 https://leetcode.cn/problems/possible-bipartition/

class UnionFind {
    private array $fa;
    private array $rank;

    public function __construct($n) {
        $this->fa = range(0, $n - 1); // 初始化父数组（0-based索引）
        $this->rank = array_fill(0, $n, 1); // 初始化秩数组
    }

    /**
     * @param int $x
     * @return int
     */
    private function find(int $x): int
    {
        if ($this->fa[$x] !== $x) {
            $this->fa[$x] = $this->find($this->fa[$x]); // 路径压缩
        }
        return $this->fa[$x];
    }

    /**
     * @param int $x
     * @param int $y
     * @return void
     */
    public function union(int $x, int $y): void
    {
        $fx = $this->find($x);
        $fy = $this->find($y);
        // 已经在同一集合，无需合并
        if ($fx === $fy) return;

        // 按秩合并，将秩较小的树合并到秩较大的树下
        if ($this->rank[$fx] < $this->rank[$fy]) {
            [$fx, $fy] = [$fy, $fx];
        }

        $this->rank[$fx] += $this->rank[$fy];
        $this->fa[$fy] = $fx;
    }

    /**
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function isConnected(int $x, int $y): bool
    {
        // 判断是否连通
        return $this->find($x) === $this->find($y);
    }
}
class PossibleBiPartitionSolutionWhitUnionFind {
    /**
     * @param int $n
     * @param array $dislikes
     * @return bool
     */
    public function possibleBipartition(int $n, array $dislikes): bool
    {
        // 构建邻接表（0-based索引）
        $g = array_fill(0, $n, []);
        foreach ($dislikes as $pair) {
            $x = $pair[0] - 1;
            $y = $pair[1] - 1;
            $g[$x][] = $y;
            $g[$y][] = $x;
        }

        $uf = new UnionFind($n);

        for ($x = 0; $x < $n; $x++) {
            $neighbors = $g[$x];
            // 无邻居节点，跳过
            if (empty($neighbors)) continue;

            // 取第一个邻居作为基准点
            $firstNeighbor = $neighbors[0];
            foreach ($neighbors as $y) {
                // 将所有邻居合并到同一集合
                $uf->union($firstNeighbor, $y);

                // 检查当前节点x与邻居y是否在同一集合（二分图要求相邻节点必须在不同集合）
                if ($uf->isConnected($x, $y)) {
                    // 发现冲突，无法二分
                    return false;
                }
            }
        }

        // 所有节点检查通过，是二分图
        return true;
    }
}
$svc = new PossibleBiPartitionSolutionWhitUnionFind();
$svc->possibleBipartition(4, [[1, 2], [1, 3], [2, 4]]);