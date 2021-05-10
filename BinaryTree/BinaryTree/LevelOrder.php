<?php


class LevelOrder
{
    public array $result = [];

    public function levelProcess($root, $depth)
    {
        if ($root == null) {
            return ;
        }

        $this->result[$depth][] = $root->val;

        $this->levelProcess($root->left, $depth+1);
        $this->levelProcess($root->right, $depth+1);
    }

    public function levelOrder($root)
    {
        $this->levelProcess($root, 0);
    }
}
