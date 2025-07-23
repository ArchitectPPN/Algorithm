<?php

require_once "BuildBinaryTree.php";

use BinaryTreeRightSideView\BuildBinaryTree;
use BinaryTreeRightSideView\TreeNode;

class BinaryTreeRightSideViewWithWidthSearch
{
    /**
     * 二叉树的右视图
     *
     * @param TreeNode|null $root
     * @return Integer[]
     */
    function rightSideView(?TreeNode $root): array
    {
        $queue = [];
        $queue[] = $root;

        $ans = [];

        $level = 0;
        while (!empty($queue)) {
            $size = count($queue);

            for ($i = 0; $i < $size; $i++) {
                $node = array_shift($queue);
                $ans[$level] = $node->val;

                $node->left && $queue[] = $node->left;
                $node->right && $queue[] = $node->right;
            }
        }

        return $ans;
    }
}

$buildBinaryTree = new BuildBinaryTree();
$tree = $buildBinaryTree->buildBinaryTree([1, 2, 3, null, 5, null, 4]);

$svc = new BinaryTreeRightSideViewWithDfs();
$rightSide = $svc->rightSideView($tree);

var_dump($rightSide);