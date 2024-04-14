<?php

namespace BinarySearchTree;
/**
 * Definition for a binary tree node.
 * class TreeNode {
 *     public $val = null;
 *     public $left = null;
 *     public $right = null;
 *     function __construct($val = 0, $left = null, $right = null) {
 *         $this->val = $val;
 *         $this->left = $left;
 *         $this->right = $right;
 *     }
 * }
 */

/**
 * 递归解法
 */
class searchBSTSolution
{
    /**
     * @param TreeNode $root
     * @param int $val
     * @return ?TreeNode
     */
    function searchBST(TreeNode $root, int $val): ?TreeNode
    {
        return $this->search($root, $val);
    }

    /**
     * @param ?TreeNode $root
     * @param int $val
     * @return ?TreeNode
     */
    private function search(?TreeNode $root, int $val): ?TreeNode
    {
        if (is_null($root)) {
            return null;
        }

        // 如果当前节点值等于$val，直接返回
        if ($root->val == $val) {
            return $root;
        } else if ($val >= $root->val) {
            return $this->search($root->right, $val);
        }

        return $this->search($root->left, $val);
    }
}

/**
 * 循环
 */
class searchBSTSolutionForeach
{
    /**
     * @param TreeNode $root
     * @param int $val
     * @return ?TreeNode
     */
    function searchBST(TreeNode $root, int $val): ?TreeNode
    {
        while (!is_null($root)) {
            if ($root->val == $val) {
                return $root;
            } else if ($val >= $root->val) {
                $root = $root->right;
            } else {
                $root = $root->left;
            }
        }
        return null;
    }
}