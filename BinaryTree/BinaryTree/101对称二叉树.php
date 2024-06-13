<?php

namespace BinarySearchIsSymmetric;

class TreeNode {
    /**
     * @var int
     */
    public int $val = 0;

    /**
     * @var ?TreeNode
     */
    public ?TreeNode $left = null;
    public ?TreeNode $right = null;
    function __construct(int $val = 0, ?TreeNode $left = null, ?TreeNode $right = null) {
        $this->val = $val;
        $this->left = $left;
        $this->right = $right;
    }
}


class Solution {
    /**
     * @param ?TreeNode $root
     * @return bool
     */
    function isSymmetric(?TreeNode $root) :bool {
        if (is_null($root)) {
            return true;
        }

        return $this->dfs($root->left, $root->right);
    }

    /**
     * @param ?TreeNode $left
     * @param ?TreeNode $right
     * @return bool
     */
    private function dfs(?TreeNode $left, ?TreeNode $right) :bool {
        // 如果左右子节点都是null，则不是对称得
        if (is_null($left) && is_null($right)) {
            return true;
        }

        // 如果左右子节点有一个是null，则不是对称得
        if (is_null($left) || is_null($right)) {
            return false;
        }

        if ($left->val != $right->val) {
            return false;
        }

        // 比较右子树的左子树和左子树的右子树
        // 右子树的右子树， 左子树的左子树
        // 画个图看的比较清楚
        //           1
        //         /   \
        //        2     2
        //       / \   / \
        //      3   4 4   3
        //所以就是 3 和 3 比较， 4 和 4 比较
        return $this->dfs($right->left, $left->right) && $this->dfs($right->right, $left->left);
    }
}