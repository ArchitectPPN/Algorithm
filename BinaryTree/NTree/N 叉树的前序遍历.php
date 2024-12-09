<?php

namespace NTree;

class Node {
    /** @var int|mixed|null */
    public ?int $val = null;

    /** @var array|null */
    public ?array $children = null;

    function __construct($val = 0) {
        $this->val = $val;
        $this->children = array();
    }
}

class NTreePreOrderTraversalSolution
{
    /** @var array 答案 */
    private array $ans = [];

    /**
     * @param Node $root
     * @return integer[]
     */
    public function preorder(Node $root): array
    {
        $this->traversal($root);

        return $this->ans;
    }

    private function traversal(Node $root): void
    {
        if (is_null($root->val)) {
            return ;
        }

        // 前序遍历， 将值写入array中
        $this->ans[] = $root->val;

        foreach ($root->children as $child) {
            $this->traversal($child);
        }
    }
}