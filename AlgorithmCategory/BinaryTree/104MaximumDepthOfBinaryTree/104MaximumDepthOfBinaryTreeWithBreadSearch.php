<?php

namespace MaximumDepthOfBinaryTree;

require_once "BuildBinaryTree.php";

/**
 * 广度优先
 */
class MaximumDepthOfBinaryTreeWithBreadSearchSolution
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

        $stack = [];
        $maxDepth = 0;
        // 入栈
        $stack[] = $root;

        while (!empty($stack)) {
            $ns = count($stack);

            for ($i = 0; $i < $ns; $i++) {
                $top = array_pop($stack);

                if (!is_null($top->left)) {
                    $stack[] = $top->left;
                }

                if (!is_null($top->right)) {
                    $stack[] = $top->right;
                }
            }

            $maxDepth++;
        }

        return $maxDepth;
    }
}

$questions = [
    [3, 9, 20, null, null, 15, 7]
];

$buildSvc = new BuildBinaryTree();
$svc = new MaximumDepthOfBinaryTreeWithBreadSearchSolution();

foreach ($questions as $question) {
    $binaryTree = $buildSvc->buildBinaryTree($question);

    $maxDepth = $svc->maxDepth($binaryTree);
    var_dump($maxDepth);
}

