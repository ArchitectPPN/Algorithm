<?php

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
class CreateBinaryTreeFromPreAndInOrderSolution
{
    /**
     * @var int 前序遍历下标
     */
    private int $preIdx = 0;

    /**
     * @var int 下标最大长度
     */
    private int $totalLength;

    /**
     * @var array 中序遍历val => key map
     */
    private array $inOrderMap = [];

    /**
     * @var array 前序遍历数组
     */
    private array $preOrder = [];


    /**
     * @param Integer[] $preorder
     * @param Integer[] $inorder
     * @return TreeNode
     */
    function buildTree(array $preorder, array $inorder): ?TreeNode
    {
        $this->preOrder = $preorder;

        $length = count($preorder);
        $this->totalLength = 0;
        if ($length >= 1) {
            $this->totalLength = $length - 1;
        }

        foreach ($inorder as $index => $val) {
            $this->inOrderMap[$val] = $index;
        }

        return $this->builderHelper(0, $this->totalLength);
    }

    private function builderHelper($inLeft, $inRight): ?TreeNode
    {
        if ($inLeft > $inRight || $this->preIdx > $this->totalLength) {
            return null;
        }

        // 构建二叉树
        $rootVal = $this->preOrder[$this->preIdx];
        $root = new TreeNode($rootVal);

        // 下标往前移
        $this->preIdx++;

        // 找到当前根节点val在中序遍历中的位置
        $mid = $this->inOrderMap[$rootVal];

        // 根据前序遍历的原则：根 -> 左 -> 右
        // 构建左子树
        $root->left = $this->builderHelper($inLeft, $mid - 1);
        // 构建右子树
        $root->right = $this->builderHelper($mid + 1, $inRight);

        return $root;
    }
}