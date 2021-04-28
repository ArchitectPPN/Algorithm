<?php


class BinaryTree
{
    /**
     * @var string 数据
     */
    public $data;

    /**
     * @var object 子节点
     */
    public $right;

    /**
     * @var object 子节点
     */
    public $left;
}

function createBinaryTree()
{
    $handle = fopen('php://stdin', 'r');
    $inputData = trim(fgets($handle));

    // 用来控制程序退出
    if ($inputData == '#') {
        $binaryTree = NULL;
    } else {
        // 生成一个二叉树
        $binaryTree = new BinaryTree();

        $binaryTree->data = $inputData;
        // 填充二叉树的左节点
        $binaryTree->left = createBinaryTree();
        // 填充二叉树的右节点
        $binaryTree->right = createBinaryTree();
    }

    return $binaryTree;
}

$binaryTree = createBinaryTree();

var_dump($binaryTree);