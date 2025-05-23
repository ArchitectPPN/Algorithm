<?php

# 133. 克隆图 https://leetcode.cn/problems/clone-graph/

class Node
{
    /** @var int 值 */
    public int $val = 0;
    /** @var array 邻节点 */
    public array $neighbors = [];

    function __construct($val = 0, $neighbors = [])
    {
        $this->val = $val;
        $this->neighbors = $neighbors;
    }
}

class CloneGraphSolution
{
    /** @var array 模拟哈希表，存储原图节点到克隆节点的映射，检查是否已经访问过 */
    private array $visited = [];

    /**
     * @param Node|null $node
     * @return Node|null
     */
    public function cloneGraph(?Node $node): ?Node
    {
        if ($node === null) {
            return null;
        }

        // 检查阶段是否已经访问过
        if (isset($this->visited[$node->val])) {
            return $this->visited[$node->val];
        }

        // 创建新节点
        $cloneNode = new Node($node->val, []);
        // 标记该节点被访问过
        $this->visited[$node->val] = $cloneNode;

        // 递归克隆所有邻居，并添加到克隆节点的邻居列表
        foreach ($node->neighbors as $neighbor) {
            // 递归克隆邻居
            $clonedNeighbor = $this->cloneGraph($neighbor);
            // 添加克隆后的邻居
            $cloneNode->neighbors[] = $clonedNeighbor;
        }

        return $cloneNode;
    }
}

$oneNode = new Node(1, []);
$twoNode = new Node(2, []);
$threeNode = new Node(3, []);
$fourNode = new Node(4, []);

$oneNode->neighbors = [$twoNode, $fourNode];
$twoNode->neighbors = [$oneNode, $threeNode];
$threeNode->neighbors = [$twoNode, $fourNode];
$fourNode->neighbors = [$oneNode, $threeNode];

$svc = new CloneGraphSolution();
$node = $svc->cloneGraph($oneNode);