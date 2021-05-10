<?php


class TargetSum
{
    private function process($root, $target): bool
    {
        if ($root == null) {
            return false;
        }

        if ($root->left == null && $root->right == null) {
            return $root->val == $target;
        }

        return $this->process($root->left, $target - $root->val) || $this->process($root->right, $target - $root->val);
    }

    public function hasPathSum($root, $target): bool
    {
        return $this->process($root, $target);
    }
}