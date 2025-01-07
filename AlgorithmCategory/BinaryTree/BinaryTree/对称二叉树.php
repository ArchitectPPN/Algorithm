<?php


class IsSymmetricSolution
{
    public function process($left, $right): bool
    {
        // 左右子树均为null，说明对称
        if (is_null($left) && is_null($right)) {
            return true;
        }

        // 终止程序，左右子树有一边为null，说明不对称
        if (is_null($left)
            || is_null($right)
        ) {
            return false;
        }

        // 两边值不一致，说明不对称
        if ($right->val != $left->val) {
            return false;
        }

        return $this->process($right->left, $left->right)
            && $this->process($right->right, $left->left);
    }

    /**
     * check二叉树是否对称
     *
     * @param $root
     * @return bool
     */
    public function isSymmetric($root): bool
    {
        return $this->process($root->left, $root->right);
    }
}