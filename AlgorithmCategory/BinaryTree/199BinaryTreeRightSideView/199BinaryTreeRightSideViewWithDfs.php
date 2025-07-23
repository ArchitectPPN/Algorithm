<?php

require_once "BuildBinaryTree.php";

use BinaryTreeRightSideView\BuildBinaryTree;
use BinaryTreeRightSideView\TreeNode;

class BinaryTreeRightSideViewWithDfs
{
    /** @var array */
    private array $ans = [];

    /**
     * 二叉树的右视图
     *
     * @param TreeNode|null $root
     * @return Integer[]
     */
    function rightSideView(?TreeNode $root): array
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
        if (is_null($root)) {
            return;
        }
        // 将当前节点写入ans
        $this->ans[$index] = $root->val;
        // 先遍历左节点，然后再遍历右节点，如果有右节点的话，就会覆盖原先的左节点写入的值
        $this->dfs($root->left, $index + 1);
        // 遍历右节点
        $this->dfs($root->right, $index + 1);
    }
}

$buildBinaryTree = new BuildBinaryTree();
$tree = $buildBinaryTree->buildBinaryTree([1, 2, 3, null, 5, null, 4]);

$svc = new BinaryTreeRightSideViewWithDfs();
$rightSide = $svc->rightSideView($tree);

var_dump($rightSide);