<?php


class IsSymmetric
{
    public function process($binaryTreeOne, $binaryTreeTwo): bool
    {
        if ($binaryTreeOne == null && $binaryTreeTwo == null) {
            return true;
        }

        if ($binaryTreeOne == null || $binaryTreeTwo == null) {
            return false;
        }

        return ($binaryTreeTwo->val == $binaryTreeOne->val) && $this->process($binaryTreeTwo->left, $binaryTreeOne->right) && $this->process($binaryTreeTwo->right, $binaryTreeOne->left);
    }

    public function isSymmetric($root)
    {
        return $this->process($root, $root);
    }
}