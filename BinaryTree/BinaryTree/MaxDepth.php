<?php

/**
 * Definition for a binary tree node .
 */
class TreeNode
{
    public $val = null;
    public $left = null;
    public $right = null;

    function __construct($val = 0, $left = null, $right = null)
    {
        $this->val = $val;
        $this->left = $left;
        $this->right = $right;
    }
}

/**
 * 自顶向下
 *
 * Class MaxDepth
 */
class MaxDepthFromTop
{
    // 初始化最大深度
    public $maxDepth = 0;

    public function findMaxDepthProcess($root, $depth)
    {
        if ($root == null) {
            return;
        }

        if (!$root->left && !$root->right) {
            $this->maxDepth = max($this->maxDepth, $depth);
        }

        $this->findMaxDepthProcess($root->left, $depth + 1);
        $this->findMaxDepthProcess($root->right, $depth + 1);
    }

    /**
     * @param TreeNode $root
     * @return Integer
     */
    public function maxDepth(TreeNode $root)
    {
        // 默认树高为1
        $this->findMaxDepthProcess($root, 1);
        return $this->maxDepth;
    }
}

/**
 * 自底向上
 */
class MaxDepthFromBottom
{
    public function FindMaxDepthProcess($root)
    {
        if ($root == null) {
            return 0;
        }

        $maxLeftDepth = $this->FindMaxDepthProcess($root->left);
        $maxRightDepth = $this->FindMaxDepthProcess($root->right);

        return max($maxLeftDepth, $maxRightDepth) + 1;
    }

    public function maxDepth($root)
    {
        return $this->FindMaxDepthProcess($root);
    }
}
