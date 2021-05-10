<?php


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

    public function postOrder($root)
    {
        $this->recursionProcess($root);

        return $this->result;
    }
}