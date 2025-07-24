<?php

use FlattenBinaryTreeToLinkedList\TreeNode;
use FlattenBinaryTreeToLinkedList\BuildBinaryTree;

require_once "BuildBinaryTree.php";

class FlattenBinaryTreeToLinkedListSolution
{
    /** @var TreeNode[] */
    private array $ans = [];

    /**
     * @param ?TreeNode $root
     * @return void
     */
    function flatten(?TreeNode $root): void
    {
        if (is_null($root)) {
            return;
        }

        $this->dfs($root);

        $size = count($this->ans);
        for ($i = 1; $i < $size; $i++) {
            $prev = $this->ans[$i - 1];
            $current = $this->ans[$i];

            $prev->right = $current;
            $prev->left = null;
        }
    }

    /**
     * @param TreeNode|null $node
     * @return void
     */
    private function dfs(?TreeNode $node): void
    {
        if (is_null($node)) {
            return;
        }

        $this->ans[] = $node;
        $this->dfs($node->left);
        $this->dfs($node->right);
    }
}

$buildBinaryTree = new BuildBinaryTree();
$tree = $buildBinaryTree->buildBinaryTree([1, 2, 5, 3, 4, null, 6]);

$svc = new FlattenBinaryTreeToLinkedListSolution();
$svc->flatten($tree);

var_dump($tree);