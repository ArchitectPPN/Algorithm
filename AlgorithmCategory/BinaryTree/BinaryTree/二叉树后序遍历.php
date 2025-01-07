<?php

/**
 * 二叉树后序遍历
 */
class PostOrderTraversal
{
    public array $result = [];

    public function recursionProcess($root)
    {
        if ($root == null) {
            return ;
        }

        $this->recursionProcess($root->left);
        $this->recursionProcess($root->right);

        $this->result[] = $root->val;
    }

    public function postOrder($root): array
    {
        $this->recursionProcess($root);

        return $this->result;
    }
}