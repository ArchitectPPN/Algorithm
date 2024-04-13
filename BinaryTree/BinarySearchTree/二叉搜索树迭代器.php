<?php

namespace BinarySearchTree;

class BSTIteratorSolution
{
    /**
     * @var int 声明并初始化当前下标
     */
    private int $idx = 0;

    /**
     * @var array 存储压缩后的二叉搜索树
     */
    private array $arr = [];

    /**
     * @param $root
     */
    public function __construct($root)
    {
        $this->inOrderTree($root);
    }

    /**
     * 中序遍历压缩二叉树
     *
     * @param $root
     * @return void
     */
    private function inOrderTree($root): void
    {
        if (is_null($root)) {
            return ;
        }

        $this->inOrderTree($root->left);
        $this->arr[] = $root->val;
        $this->inOrderTree($root->right);
    }

    /**
     * 返回下一个元素
     *
     * @return int
     */
    public function next(): int
    {
        return $this->arr[$this->idx++];
    }

    /**
     * @return bool
     */
    public function hasNext(): bool
    {
        return $this->idx < count($this->arr);
    }
}