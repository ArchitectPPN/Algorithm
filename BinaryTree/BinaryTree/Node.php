<?php

class Node
{
    /**
     * @var int
     */
    public int $val;

    /**
     * @var Node|null 左子树
     */
    public ?Node $left;

    /**
     * @var Node|null 右子树
     */
    public ?Node $right;

    /**
     * @var Node|null 下一个节点
     */
    public ?Node $next;

    function __construct(int $val = 0)
    {
        $this->val = $val;
        $this->left = null;
        $this->right = null;
        $this->next = null;
    }

    /**
     * @return int
     */
    public function getVal(): int
    {
        return $this->val;
    }

    /**
     * @param int $val
     * @return $this
     */
    public function setVal(int $val): static
    {
        $this->val = $val;
        return $this;
    }

    /**
     * @return Node|null
     */
    public function getLeft(): ?Node
    {
        return $this->left;
    }

    /**
     * @param Node|null $left
     * @return $this
     */
    public function setLeft(?Node $left): static
    {
        $this->left = $left;
        return $this;
    }

    /**
     * @return Node|null
     */
    public function getRight(): ?Node
    {
        return $this->right;
    }

    /**
     * @param Node|null $right
     * @return $this
     */
    public function setRight(?Node $right): static
    {
        $this->right = $right;
        return $this;
    }

    /**
     * @return Node|null
     */
    public function getNext(): ?Node
    {
        return $this->next;
    }

    /**
     * @param Node|null $next
     * @return $this
     */
    public function setNext(?Node $next): static
    {
        $this->next = $next;
        return $this;
    }
}
