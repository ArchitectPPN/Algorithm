<?php


class InOrderTraversal
{
    public array $result = [];

    public function recursionProcess($root)
    {
        if ($root == null) {
            return ;
        }

        $this->recursionProcess($root->left);
        $this->result[] = $root->val;
        $this->recursionProcess($root->right);
    }

    public function inOrder($root): array
    {
        $this->recursionProcess($root);

        return $this->result;
    }
}