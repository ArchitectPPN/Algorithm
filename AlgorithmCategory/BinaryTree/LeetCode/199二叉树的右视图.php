<?php

namespace RightSideView;

class TreeNode
{
    public int $val;
    public TreeNode $left;
    public TreeNode $right;

    function __construct(int $val = 0, TreeNode $left = null, TreeNode $right = null)
    {
        $this->val = $val;
        $this->left = $left;
        $this->right = $right;
    }
}

class Solution {
    /** @var array */
    private array $ans = [];

    /**
     * 二叉树的右视图
     *
     * @param TreeNode $root
     * @return Integer[]
     */
    function rightSideView(TreeNode $root): array
    {
        $this->dfs($root, 0);

        return $this->ans;
    }

    /**
     * @param TreeNode|null $root
     * @param $index
     * @return void
     */
    private function dfs(?TreeNode $root, $index): void
    {
        // 下一节点不存在时退出
        if(is_null($root)) {
            return ;
        }
        // 将当前节点写入ans
        $this->ans[$index] = $root->val;
        // 先遍历左节点，然后再遍历右节点，如果有右节点的话，就会覆盖原先的左节点写入的值
        $this->dfs($root->left, $index + 1);
        // 遍历右节点
        $this->dfs($root->right, $index + 1);
    }
}