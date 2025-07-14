<?php

namespace MaximumDepthOfBinaryTree;

class TreeNode
{
    public ?int $val = null;
    public ?TreeNode $left = null;
    public ?TreeNode $right = null;

    public function __construct(?int $val = 0, ?TreeNode $left = null, ?TreeNode $right = null)
    {
        $this->val = $val;
        $this->left = $left;
        $this->right = $right;
    }
}

/**
 * 构建二叉树
 */
class BuildBinaryTree
{
    /**
     * 构建二叉树
     * @param array $arr
     * @return TreeNode|null
     */
    public function buildBinaryTree(array $arr): ?TreeNode
    {
        if (empty($arr) || $arr[0] === null) {
            return null;
        }

        // 创建根节点
        $root = new TreeNode($arr[0]);
        $queue = [$root];
        $i = 1; // 数组索引，从第二个元素开始

        while (!empty($queue) && $i < count($arr)) {
            $node = array_shift($queue);

            // 处理左子节点
            if ($arr[$i] !== null) {
                $node->left = new TreeNode($arr[$i]);
                $queue[] = $node->left;
            }
            $i++;

            // 处理右子节点
            if ($i < count($arr) && $arr[$i] !== null) {
                $node->right = new TreeNode($arr[$i]);
                $queue[] = $node->right;
            }
            $i++;
        }

        return $root;
    }
}