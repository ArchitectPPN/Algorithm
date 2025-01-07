<?php

class Node
{
    /**
     * @var int
     */
    public int $val;

    /**
     * @var Node|null 左子树
     */
    public ?Node $left;

    /**
     * @var Node|null 右子树
     */
    public ?Node $right;

    /**
     * @var Node|null 下一个节点
     */
    public ?Node $next;

    function __construct(int $val = 0)
    {
        $this->val = $val;
        $this->left = null;
        $this->right = null;
        $this->next = null;
    }

    /**
     * @return int
     */
    public function getVal(): int
    {
        return $this->val;
    }

    /**
     * @param int $val
     * @return $this
     */
    public function setVal(int $val): static
    {
        $this->val = $val;
        return $this;
    }

    /**
     * @return Node|null
     */
    public function getLeft(): ?Node
    {
        return $this->left;
    }

    /**
     * @param Node|null $left
     * @return $this
     */
    public function setLeft(?Node $left): static
    {
        $this->left = $left;
        return $this;
    }

    /**
     * @return Node|null
     */
    public function getRight(): ?Node
    {
        return $this->right;
    }

    /**
     * @param Node|null $right
     * @return $this
     */
    public function setRight(?Node $right): static
    {
        $this->right = $right;
        return $this;
    }

    /**
     * @return Node|null
     */
    public function getNext(): ?Node
    {
        return $this->next;
    }

    /**
     * @param Node|null $next
     * @return $this
     */
    public function setNext(?Node $next): static
    {
        $this->next = $next;
        return $this;
    }
}


/**
 * Definition for a binary tree node.
 * class TreeNode {
 *     public $val = null;
 *     public $left = null;
 *     public $right = null;
 *     function __construct($value) { $this->val = $value; }
 * }
 */

class LowestCommonAncestorSolution {
    private Node $ans;

    /**
     * @param Node $root
     * @param Node $p
     * @param Node $q
     * @return Node
     */
    function lowestCommonAncestor(Node $root, Node $p, Node $q): Node
    {
        $this->dfs($root, $p, $q);

        echo $this->ans->val . PHP_EOL;

        return $this->ans;
    }

    private function dfs($root, $p, $q): bool
    {
        if (is_null($root)) {
            return false;
        }

        $lson = $this->dfs($root->left, $p, $q);
        $rson = $this->dfs($root->right, $p, $q);

        if (
            ($lson && $rson)
            || (
                ($root->val == $p->val || $root->val == $q->val)
                && ($lson || $rson)
            )
        ) {
            $this->ans = $root;
        }

        return $lson || $rson || ($root->val == $p->val || $root->val == $q->val);
    }
}


// 构建二叉树
$tree = new Node(3);
$one = new Node(1);
$five = new Node(5);
$six = new Node(6);
$two = new Node(2);
$seven = new Node(7);
$zero = new Node(0);
$eight = new Node(8);
$four = new Node(4);

$tree->left = $five;
$tree->right = $one;

$five->left = $six;
$five->right = $two;

$two->left = $seven;
$two->right = $four;

$one->left = $zero;
$one->right = $eight;

$solution = new LowestCommonAncestorSolution();
$solution->lowestCommonAncestor($tree, $zero, $eight);