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
        /**
         * 树结构的性质决定了：如果有 n 条边，则一定有 n + 1 个节点。
         * 但本题给出的输入是一个 n 条边的有向图，所以默认原始结构是一棵树加上一条冗余边，因此总共有 n 个节点。 直接count就能得到得到节点个数
         */
        $n = count($edges);
        // UnionFind 里面是从 0 - n - 1, 所以外部需要 n + 1
        $uf = new UnionFindWithRank($n + 1);
        // 初始化parent数组, 设置每个节点的父节点为自身
        $parent = array_fill(1, $n, 0);
        for ($i = 1; $i <= $n; ++$i) {
            $parent[$i] = $i;
        }
        // 初始化没冲突, 没有环
        $conflict = -1;
        $cycle = -1;
        // 遍历所有阶段, 找到冲突和环
        for ($i = 0; $i < $n; ++$i) {
            $edge = $edges[$i];
            $node1 = $edge[0];
            $node2 = $edge[1];
            /**
             * $node2第一次遍历的时候是自己本身, 然后会被更新掉, 此时$node2的入度为1
             * 等再次遍历到$node2时, $node2会再有一个父节点,两个父节点一定不一样,此时$node2的入度会变为2, 所以当前这条边就是冲突的边
             */
            if ($parent[$node2] != $node2) {
                $conflict = $i;
            } else {
                // 更新节点2的父节点
                $parent[$node2] = $node1;
                // 检查是否有环, 如果有两个节点的父节点一样, 说明有环
                if ($uf->find($node1) == $uf->find($node2)) {
                    $cycle = $i;
                } else {
                    // 没换
                    $uf->unionWithRank($node1, $node2);
                }
            }
        }

        if ($conflict < 0) {
            return [$edges[$cycle][0], $edges[$cycle][1]];
        } else {
            $conflictEdge = $edges[$conflict];
            if ($cycle >= 0) {
                // 若同时存在环（cycle !== -1），冲突节点的第一个父节点边必在环中，返回该边
                return [$parent[$conflictEdge[1]], $conflictEdge[1]];
            } else {
                return [$conflictEdge[0], $conflictEdge[1]];
            }
        }
    }
}

/**
 * 示例：
 * 假设初始状态：
 * 节点：  0 1 2 3 4
 * 父节点：0 1 2 3 4  // 每个节点自成一个集合
 *
 * 执行 union(0, 1) 和 union(2, 3) 后：
 * 节点：  0 1 2 3 4
 * 父节点：1 1 3 3 4  // 集合 {0,1} 和 {2,3}，4 单独成集合
 *
 * 执行 union(1, 3) 后：
 * 节点：0 1 2 3 4
 * 父节点：1 3 3 3 4  // 集合 {0,1,2,3} 和 {4}
 *
 * 此时查询 find(0) 会触发路径压缩，结果为：
 * 节点：0 1 2 3 4
 * 父节点：3 3 3 3 4  // 路径压缩后，0 和 1 直接指向根节点 3
 */
class UnionFindWithRank
{
    /** @var array 存储并查集的祖先节点 */
    public array $ancestor;
    /** @var array 新增 rank 数组 */
    public array $rank;

    public function __construct($n)
    {
        $this->ancestor = array_fill(0, $n, 0);
        // 初始高度为 1
        $this->rank = array_fill(0, $n, 1);
        for ($i = 0; $i < $n; ++$i) {
            $this->ancestor[$i] = $i;
        }
    }

    /**
     * 按秩合并
     * @param int $index1
     * @param int $index2
     * @return void
     */
    public function unionWithRank(int $index1, int $index2): void
    {
        $root1 = $this->find($index1);
        $root2 = $this->find($index2);

        if ($root1 == $root2) {
            return; // 已在同一集合，无需合并
        }

        // 按秩合并：将高度较小的树合并到高度较大的树下
        if ($this->rank[$root1] < $this->rank[$root2]) {
            $this->ancestor[$root1] = $root2;
        } elseif ($this->rank[$root1] > $this->rank[$root2]) {
            $this->ancestor[$root2] = $root1;
        } else {
            // 若高度相同，任选一个作为父节点，并增加高度
            $this->ancestor[$root2] = $root1;
            $this->rank[$root1]++;
        }
    }

    /**
     * 合并
     * @param int $index1
     * @param int $index2
     * @return void
     */
    public function union(int $index1, int $index2): void
    {
        // 通过合并两个节点父节点的方式，将元素 $index1 和 $index2 所在的集合合并为一个集合
        // 1. 通过find方法分别找到两个节点的父节点
        // 2. 将 $index1 的根节点的父节点设为 $index2 的根节点，从而将两个集合连接
        $this->ancestor[$this->find($index1)] = $this->find($index2);
    }

    /**
     * 查找操作 + 路径压缩： 查找元素 $index 所在集合的根节点，并进行路径压缩
     * @param int $index
     * @return int
     */
    public function find(int $index): int
    {
        // 递归查找父节点：若当前节点的父节点不是自身（即非根节点），则递归查找其父节点的根
        if ($this->ancestor[$index] != $index) {
            // 路径压缩：在递归返回时，将当前节点的父节点直接设为根节点，从而缩短后续查询路径
            // 每次 find 操作会将查询路径上的所有节点直接连接到根节点，使树的深度趋近于 1，这保证了后续查询的时间复杂度接近 O (1)
            $this->ancestor[$index] = $this->find($this->ancestor[$index]);
        }
        return $this->ancestor[$index];
    }
}

$questions = [
//    [[1, 2], [1, 3], [2, 3]],
    [[1,2], [3,2], [2,4], [4,3]],
    [[3,2], [1,2], [2,4], [4,3]]
];
$svc = new FindRedundantDirectedConnectionSolution();
foreach ($questions as $question) {
    $ans = $svc->findRedundantDirectedConnection($question);
    var_dump($ans);
}

# 0 -> 1 -> 2 -> 0