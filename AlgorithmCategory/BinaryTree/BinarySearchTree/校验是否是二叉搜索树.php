<?php

namespace BinarySearchTree;

/**
 * 判断是否是二叉搜索树
 */
class isValidBSTSolution
{

    /**
     * @param $root
     * @return bool
     */
    public function isValidBST($root): bool
    {
        return $this->check($root, PHP_INT_MIN, PHP_INT_MAX);
    }

    /**
     * 检查是否是二叉搜索树
     *
     * @param $root
     * @param int $minInt
     * @param int $maxInt
     * @return bool
     */
    private function check($root, int $minInt, int $maxInt) :Bool
    {
        // 节点为null，默认为二叉搜索树
        if (is_null($root)) {
            return true;
        }
        // 当前节点的值不再范围内，认为不是二叉搜索树
        if ($root->val <= $minInt || $root >= $maxInt) {
            return false;
        }

        return $this->check($root->left, $minInt, $root->val) && $this->check($root->right, $root->val, $maxInt);
    }
}