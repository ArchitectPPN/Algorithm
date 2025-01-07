<?php


class CreateBinaryTreeFromPostAndInOrderSolution
{
    /**
     * @var array 后序遍历数组
     */
    private array $postOrder;

    /**
     * @var array 前序遍历key val map
     */
    private array $idxMap = [];

    /**
     * @var int 后序遍历最后一个元素的下标
     */
    private int $postIdx;

    public function buildTree($inOrder, $postOrder): ?TreeNode
    {
        $inOrder1 = $inOrder;
        $this->postOrder = $postOrder;

        // 计算前序遍历最大值
        $maxLength = count($inOrder);
        // 初始化后序遍历最大的值
        $this->postIdx = $maxLength - 1;

        // 前序遍历val-key map
        foreach ($inOrder1 as $index => $val) {
            $this->idxMap[$val] = $index;
        }

        return $this->helper(0, $this->postIdx);
    }

    private function helper($indexLeft, $indexRight): ?TreeNode
    {
        // 中序遍历的性质决定right一定大于left
        // 由于是先遍历left然后才会遍历right，天然决定右子树的下标一定大于左子树
        if ($indexLeft > $indexRight) {
            return null;
        }

        // 选择后序数组中的最后一个元素作为当前子树根节点
        $rootVal = $this->postOrder[$this->postIdx];
        // 构建子树
        $root = new TreeNode($rootVal);

        // 根据 root 所在的位置分成左右两棵子树
        $index = $this->idxMap[$root->val];

        // 下标减1，这个下标已经在当前执行过程被用过了，所以要向前进一步，也就是向左移动
        $this->postIdx--;

        // 由于根据后序遍历的性质，所以我们需要先构建右子树，然后构建左子树
        $root->right = $this->helper($index + 1, $indexRight);
        $root->left = $this->helper($indexLeft, $index - 1);

        return $root;
    }
}