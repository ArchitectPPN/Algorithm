<?php

use FlattenBinaryTreeToLinkedList\BuildBinaryTree;

require "BuildBinaryTree.php";

class FlattenBinaryTreeToLinkedListWithWidthSearch
{
    /**
     * @param $root
     * @return void
     */
    public function flatten($root): void
    {
        if ($root === null) {
            return;
        }

        $stack[] = $root;
        $prev = null;

        while (!empty($stack)) {
            // 弹出栈顶节点（当前处理节点）
            $curr = array_pop($stack);

            // 调整前一个节点的指针
            if ($prev !== null) {
                $prev->left = null;   // 左指针置空
                $prev->right = $curr; // 右指针指向当前节点
            }

            // 保存左右子节点引用
            $left = $curr->left;
            $right = $curr->right;

            // 右子节点先入栈，左子节点后入栈（栈是后进先出，确保左子树先处理）
            if ($right !== null) {
                $stack[] = $right;
            }
            if ($left !== null) {
                $stack[] = $left;
            }

            // 更新前一个节点为当前节点
            $prev = $curr;
        }
    }
}


$buildBinaryTree = new BuildBinaryTree();
$tree = $buildBinaryTree->buildBinaryTree([1, 2, 5, 3, 4, null, 6]);

$svc = new FlattenBinaryTreeToLinkedListWithWidthSearch();
$svc->flatten($tree);

var_dump($tree);