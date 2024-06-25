<?php

class TreeNode
{
    public ?TreeNode $val = null;
    public ?TreeNode $left = null;
    public ?TreeNode $right = null;

    function __construct(?TreeNode $value)
    {
        $this->val = $value;
    }
}


class LowestCommonAncestorSolution
{
    private ?TreeNode $ans;

    /**
     * @param ?TreeNode $root
     * @param ?TreeNode $p
     * @param ?TreeNode $q
     * @return ?TreeNode
     */
    function lowestCommonAncestor(?TreeNode $root, ?TreeNode $p, ?TreeNode $q)
    {
        //
        $this->dfs($root, $p, $q);
        return $this->ans;
    }

    /**
     * 获取下一个非null的节点
     */
    private function dfs($root, $p, $q): ?bool
    {
        if (is_null($root)) {
            return false;
        }

        // 先看左子树
        $lSon = $this->dfs($root->left, $p, $q);
        // 在看右子树
        $rSon = $this->dfs($root->right, $p, $q);

        // 左右子树都包含子节点
        if (
            ($lSon && $rSon)
            ||  (
                // 当前节点的值等于p或q的val && （左子树 || 右子树包含p或q）
                ($root->val == $p->val || $root->val == $q->val)
                &&  ($lSon || $rSon)
            )
        ) {
            // 符合条件便可认为满足答案
            $this->ans = $root;
        }

        return $lSon || $rSon || ($root->val == $p->val || $root->val == $q->val);
    }
}
