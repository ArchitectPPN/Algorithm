<?php

require_once "BuildBinaryTree.php";

class BinaryTreeLevelOrderTraversalSolution
{

    /**
     * @param ?\BinaryTreeLevelOrderTraversal\TreeNode $root
     * @return array
     */
    function levelOrder(?\BinaryTreeLevelOrderTraversal\TreeNode $root): array
    {
        if (is_null($root)) {
            return [];
        }

        $queue = [];
        $ans = [];
        // 入队
        $queue[] = $root;
        while (!empty($queue)) {
            $level = [];
            $size = count($queue);
            for ($i = 0; $i < $size; $i++) {
                $node = array_shift($queue);
                $level[] = $node->val;

                $node->left && $queue[] = $node->left;
                $node->right && $queue[] = $node->right;
            }

            if (!empty($level)) {
                $ans[] = $level;
            }
        }

        return $ans;
    }
}

$buildBinaryTree = new \BinaryTreeLevelOrderTraversal\BuildBinaryTree();
$tree = $buildBinaryTree->buildBinaryTree([3, 9, 20, null, null, 15, 7]);

$svc = new BinaryTreeLevelOrderTraversalSolution();
$res = $svc->levelOrder($tree);

var_dump($res);