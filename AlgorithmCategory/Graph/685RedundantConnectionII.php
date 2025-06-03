<?php

# 685. 冗余连接 II https://leetcode.cn/problems/redundant-connection-ii/


class FindRedundantDirectedConnectionSolution
{
    /**
     * @param array $edges
     * @return array
     */
    public function findRedundantDirectedConnection(array $edges): array
    {
        $n = count($edges);
        $uf = new UnionFind($n + 1);
        // 初始化parent数组
        $parent = array_fill(1, $n, 0);
        for ($i = 1; $i <= $n; ++$i) {
            $parent[$i] = $i;
        }
        $conflict = -1;
        $cycle = -1;
        for ($i = 0; $i < $n; ++$i) {
            $edge = $edges[$i];
            $node1 = $edge[0];
            $node2 = $edge[1];
            if ($parent[$node2] != $node2) {
                $conflict = $i;
            } else {
                $parent[$node2] = $node1;
                if ($uf->find($node1) == $uf->find($node2)) {
                    $cycle = $i;
                } else {
                    $uf->union($node1, $node2);
                }
            }
        }

        if ($conflict < 0) {
            return [$edges[$cycle][0], $edges[$cycle][1]];
        } else {
            $conflictEdge = $edges[$conflict];
            if ($cycle >= 0) {
                return [$parent[$conflictEdge[1]], $conflictEdge[1]];
            } else {
                return [$conflictEdge[0], $conflictEdge[1]];
            }
        }
    }
}

class UnionFind
{
    /** @var array 存储并查集的祖先节点 */
    public array $ancestor;

    public function __construct($n)
    {
        $this->ancestor = array_fill(0, $n, 0);
        for ($i = 0; $i < $n; ++$i) {
            $this->ancestor[$i] = $i;
        }
    }

    public function union($index1, $index2): void
    {
        $this->ancestor[$this->find($index1)] = $this->find($index2);
    }

    public function find($index)
    {
        if ($this->ancestor[$index] != $index) {
            $this->ancestor[$index] = $this->find($this->ancestor[$index]);
        }
        return $this->ancestor[$index];
    }
}