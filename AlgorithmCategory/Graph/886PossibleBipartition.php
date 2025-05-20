<?php

# 886. 可能的二分法 https://leetcode.cn/problems/possible-bipartition/

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


class PossibleBiPartitionSolutionWithBFS
{
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

        // 颜色数组：0=未染色，1和-1表示两种颜色
        $color = array_fill(0, $n, 0);

        // 使用队列进行BFS
        $queue = new SplQueue();

        for ($i = 0; $i < $n; $i++) {
            // 未染色的节点，启动BFS染色
            if ($color[$i] === 0) {
                $color[$i] = 1;
                $queue->enqueue($i);

                while (!$queue->isEmpty()) {
                    $x = $queue->dequeue();

                    foreach ($g[$x] as $y) {
                        // 相邻节点颜色相同，无法二分
                        if ($color[$y] === $color[$x]) {
                            return false;
                        }

                        // 未染色，染成相反颜色并加入队列
                        if ($color[$y] === 0) {
                            $color[$y] = -$color[$x];
                            $queue->enqueue($y);
                        }
                    }
                }
            }
        }

        // 所有节点染色成功，是二分图
        return true;
    }
}
$svc = new PossibleBiPartitionSolutionWithBFS();
$svc->possibleBipartition(4, [[1, 2], [2, 1], [2, 4]]);


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