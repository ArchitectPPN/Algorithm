<?php

namespace BinarySearchTree;

class InsertIntoBSTSolution
{
    /**
     * @param ?TreeNode $root
     * @param int $val
     * @return ?TreeNode
     */
    public function insertIntoBST(?TreeNode $root, int $val): ?TreeNode
    {
        // 当前节点为null时，新建一个
        if (is_null($root)) {
            $root = new TreeNode($val);
        }

        // 插入的节点和当前节点的值一样时，直接返回
        if ($root->val == $val) {
            return $root;
        } else if ($val > $root->val) {
            // 插入的值大于当前节点的值，操作右节点
            $root->right = $this->insertIntoBST($root->right, $val);
        } else if ($val < $root->val) {
            // 插入的值小于当前节点的值，操作左节点
            $root->left = $this->insertIntoBST($root->left, $val);
        }

        return $root;
    }
}