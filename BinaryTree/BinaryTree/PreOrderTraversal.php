<?php

/**
 * 递归解法
 *
 * Class PreOrderTraversal
 */
class PreOrderTraversal
{
    // 最后返回的结果
    public array $result = [];

    private function recursionProcess($root)
    {
        if ($root == null) {
            return ;
        }

        // 前序遍历, 一开始就将节点上的数值存入数组中
        $this->result[] = $root->val;

        $this->recursionProcess($root->left);
        $this->recursionProcess($root->right);
    }

    public function preOrderTraversalFunc($root): array
    {
        $this->recursionProcess($root);

        return $this->result;
    }
}

// 这里传入一个二叉树
//(new PreOrderTraversal())->preOrderTraversalFunc();