<?php

namespace BinaryTreeZigzagLevelOrder;

 class TreeNode {
     /**
      * @var int
      */
     public int $val = 0;

     /**
      * @var ?TreeNode
      */
     public ?TreeNode $left = null;

     /**
      * @var ?TreeNode
      */
     public ?TreeNode $right = null;
     function __construct(int $val = 0,?TreeNode $left = null,?TreeNode $right = null) {
         $this->val = $val;
         $this->left = $left;
         $this->right = $right;
     }
 }

class Solution {
    /**
     * @var array 最后的answer
     */
    private array $ans = [];

    /**
     * 解决
     *
     * @param TreeNode $root
     * @return array
     */
    public function zigzagLevelOrder(TreeNode $root): array
    {
         $this->dfs($root, 0);

        return $this->ans;
    }

    /**
     * @param ?TreeNode $root
     * @param int $index
     * @return void
     */
    private function dfs(?TreeNode $root, int $index = 0): void
    {
        // 如果为null， 直接返回
        if (is_null($root)) {
            return ;
        }

        // 初始化数组
        if (!isset($this->ans[$index])) {
            $this->ans[$index] = [];
        }

        // 第0层也就是根节点，无所谓从左还是从右， 因为只有一个节点， 题目要求先左再右， 意味着第一层要从左， 第二层从右边开始
        if ($index % 2 == 0) {
            // 放到
            $this->ans[$index][] = $root->val;
        } else {
            array_unshift($this->ans[$index], $root->val);
        }

        $this->dfs($root->left, $index + 1);
        $this->dfs($root->right, $index + 1);
    }
}