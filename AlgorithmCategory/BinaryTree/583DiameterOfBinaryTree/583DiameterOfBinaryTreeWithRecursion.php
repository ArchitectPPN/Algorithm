<?php

require_once "BuildBinaryTree.php";

class DiameterOfBinaryTreeWithRecursion
{
    /** @var int 最大深度 */
    private int $ans = 1;

    /**
     * @param \DiameterOfBinaryTree\TreeNode $root
     * @return int
     */
    public function diameterOfBinaryTree(\DiameterOfBinaryTree\TreeNode $root): int
    {
        $this->ans = 1;
        $this->depth($root);

        return $this->ans - 1;
    }

    /**
     * @param \DiameterOfBinaryTree\TreeNode|null $root
     * @return int
     */
    private function depth(?\DiameterOfBinaryTree\TreeNode $root): int
    {
        if ($root === null) {
            return 0;
        }
        // 递归计算左右子树的深度
        $leftDepth = $this->depth($root->left);
        $rightDepth = $this->depth($root->right);

        $this->ans = max($this->ans, $leftDepth + $rightDepth + 1);

        return max($leftDepth, $rightDepth) + 1;
    }
}

$tree = new \DiameterOfBinaryTree\BuildBinaryTree();
$treeNode = $tree->buildBinaryTree([1, 2, 3, 4, 5]);

$svc = new DiameterOfBinaryTreeWithRecursion();
$svc->diameterOfBinaryTree($treeNode);