<?php


class BinaryTreeObj
{
    public $inputData = '';
    public $left = NULL;
    public $right = NULL;
}

function createBinaryTree()
{
    $handle = fopen('php://stdin', 'r');

    $inputData = trim(fgets($handle));

    if ($inputData == '#') {
        $binaryTree = NULL;
    } else {
        $binaryTree = new BinaryTreeObj();

        $binaryTree->inputData = $inputData;
        $binaryTree->left = createBinaryTree();
        $binaryTree->right = createBinaryTree();
    }

    return $binaryTree;
}

$tree = createBinaryTree();

var_dump($tree);
function countBinaryTree(BinaryTreeObj $root)
{
    if ($root === NULL) {
        return 0;
    }

    $leftNum = empty($root->left) ? 0 : countBinaryTree($root->left);
    $rightNum = empty($root->right) ? 0 : countBinaryTree($root->right);

    return 1 + $leftNum + $rightNum;
}

var_dump(countBinaryTree($tree));

var_dump($tree);