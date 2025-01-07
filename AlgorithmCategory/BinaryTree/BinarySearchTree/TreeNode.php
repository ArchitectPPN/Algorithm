<?php

namespace BinarySearchTree;

class TreeNode
{
    public ?int $val = null;
    public ?TreeNode $left = null;
    public ?TreeNode $right = null;

    function __construct(int $val = 0, TreeNode $left = null, TreeNode $right = null)
    {
        $this->val = $val;
        $this->left = $left;
        $this->right = $right;
    }
}