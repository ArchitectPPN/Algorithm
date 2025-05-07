<?php

# 684. 冗余连接 https://leetcode.cn/problems/redundant-connection/

class FindRedundantConnectionSolution
{
    /** @var array 存储父级 */
    private array $parent = [];

    /**
     * @param array $edges
     * @return array
     */
    public function findRedundantConnection(array $edges): array
    {
        $n = count($edges);
        $this->parent = array_fill(1, $n + 1, 0);
        for ($i = 1; $i <= $n; $i++) {
            $this->parent[$i] = $i;
        }
        for ($i = 0; $i < $n; $i++) {
            $edge = $edges[$i];
            $node1 = $edge[0];
            $node2 = $edge[1];
            if ($this->find($node1) != $this->find($node2)) {
                // 将 index1 所在集合的根节点连接到 index2 的根节点上，从而完成两个集合的合并。
                $this->union($node1, $node2);
            } else {
                return $edge;
            }
        }
        return [];
    }

    /**
     * @param int $index1
     * @param int $index2
     * @return void
     */
    public function union(int $index1, int $index2): void
    {
        $this->parent[$this->find($index1)] = $this->find($index2);
    }

    /**
     * @param int $index
     * @return int
     */
    public function find(int $index): int
    {
        if ($this->parent[$index] != $index) {
            // 这行代码实现了路径压缩优化。
            // 在递归查找根节点的过程中，会把元素 $index 直接连接到根节点上，而不是保留原来的多层父节点关系。
            // 这样做的好处是，下次再查找 $index 或其子孙节点的根节点时，查找路径会大大缩短，从而提升查找效率。
            $this->parent[$index] = $this->find($this->parent[$index]);
        }
        return $this->parent[$index];
    }
}

$questions = [
    [[1, 2], [1, 3], [2, 3]],
];

foreach ($questions as $question) {
    $svc = new FindRedundantConnectionSolution();
    $svc->findRedundantConnection($question);
}