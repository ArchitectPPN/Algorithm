<?php

namespace MaximumDepthOfBinaryTree;

require_once "BuildBinaryTree.php";

/**
 * 递归调用
 */
class MaximumDepthOfBinaryTreeWithRecursionSolution
{

    /**
     * @param ?TreeNode $root
     * @return Integer
     */
    function maxDepth(?TreeNode $root): int
    {
        if (is_null($root)) {
            return 0;
        }
        return max($this->maxDepth($root->left), $this->maxDepth($root->right)) + 1;
    }
}

$questions = [
    [3, 9, 20, null, null, 15, 7]
];

$buildSvc = new BuildBinaryTree();
$svc = new MaximumDepthOfBinaryTreeWithRecursionSolution();

foreach ($questions as $question) {
    $binaryTree = $buildSvc->buildBinaryTree($question);

    $maxDepth = $svc->maxDepth($binaryTree);
    var_dump($maxDepth);
}

