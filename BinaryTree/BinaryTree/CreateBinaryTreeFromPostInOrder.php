<?php


class CreateBinaryTreeFromPostInOrder
{
    public function buildTree() {
        $this->buildTree();
    }

    public function buildProcess($inOrder, $postOrder) {
        /*
         * 1. postOrder 最后一个元素是当前树的根节点（rootValue）
         * 2. 通过rootValue, 可以将inorder数组分割为两个子树的inorder数组(剔除了rootValue)
         * *leftInOrder
         * *rightInOrder
         * 3. 通过leftInOrder和rightInOrder可以从postOrder剪出两个子树的postOrder数组(剔除了rootValue)
         * *leftPostOrder
         * *rightPostOrder
         * 4. 通过rootValue创建根节点root
         * 5. 递归左右子树
         * */
        if (count($postOrder) == 0) {
            return null;
        }

        // postOrder 数组最后一个节点一定是根节点(rootValue)
        $rootVal = array_pop($postOrder);

    }
}