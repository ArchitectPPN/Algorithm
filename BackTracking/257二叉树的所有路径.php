<?php

namespace BinaryTreePathsSolution;

class TreeNode
{
    /** @var int */
    public int $val = 0;

    /** @var ?TreeNode */
    public ?TreeNode $left = null;

    /** @var ?TreeNode */
    public ?TreeNode $right = null;

    function __construct(int $val = 0, ?TreeNode $left = null, ?TreeNode $right = null)
    {
        $this->val = $val;
        $this->left = $left;
        $this->right = $right;
    }
}

class Solution
{
    /** @var array 临时存储 */
    private array $tmp = [];

    /** @var array 最终答案 */
    private array $ans = [];

    /**
     * @param ?TreeNode $root
     * @return String[]
     */
    function binaryTreePaths(?TreeNode $root): array
    {
        $this->backTracking($root);

        return $this->ans;
    }

    /**
     * 回溯
     * @param ?TreeNode $root
     * @return void
     */
    private function backTracking(?TreeNode $root): void
    {
        if (is_null($root)) {
            return;
        }

        $this->tmp[] = $root->val;

        if (is_null($root->left) && is_null($root->right)) {
            $this->ans[] = implode('->', $this->tmp);
        } else {
            $this->backTracking($root->left);
            $this->backTracking($root->right);
        }

        array_pop($this->tmp);
    }
}