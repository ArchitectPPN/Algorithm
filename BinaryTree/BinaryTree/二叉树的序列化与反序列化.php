<?php


class BinaryTreeSerializeAndDeserializeNode
{
    /**
     * @var int
     */
    public int $val;

    /**
     * @var BinaryTreeSerializeAndDeserializeNode|null 左子树
     */
    public ?BinaryTreeSerializeAndDeserializeNode $left;

    /**
     * @var BinaryTreeSerializeAndDeserializeNode|null 右子树
     */
    public ?BinaryTreeSerializeAndDeserializeNode $right;

    /**
     * @var BinaryTreeSerializeAndDeserializeNode|null 下一个节点
     */
    public ?BinaryTreeSerializeAndDeserializeNode $next;

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
     * @return BinaryTreeSerializeAndDeserializeNode|null
     */
    public function getLeft(): ?BinaryTreeSerializeAndDeserializeNode
    {
        return $this->left;
    }

    /**
     * @param BinaryTreeSerializeAndDeserializeNode|null $left
     * @return $this
     */
    public function setLeft(?BinaryTreeSerializeAndDeserializeNode $left): static
    {
        $this->left = $left;
        return $this;
    }

    /**
     * @return BinaryTreeSerializeAndDeserializeNode|null
     */
    public function getRight(): ?BinaryTreeSerializeAndDeserializeNode
    {
        return $this->right;
    }

    /**
     * @param BinaryTreeSerializeAndDeserializeNode|null $right
     * @return $this
     */
    public function setRight(?BinaryTreeSerializeAndDeserializeNode $right): static
    {
        $this->right = $right;
        return $this;
    }

    /**
     * @return BinaryTreeSerializeAndDeserializeNode|null
     */
    public function getNext(): ?BinaryTreeSerializeAndDeserializeNode
    {
        return $this->next;
    }

    /**
     * @param BinaryTreeSerializeAndDeserializeNode|null $next
     * @return $this
     */
    public function setNext(?BinaryTreeSerializeAndDeserializeNode $next): static
    {
        $this->next = $next;
        return $this;
    }
}

class BinaryTreeSerializeAndDeserialize
{
    /**
     * @var array
     */
    private array $arr = [];

    /**
     * 使用前序遍历
     *
     * @param $root
     * @return string
     */
    public function serialize($root): string
    {
        if (is_null($root)) {
            return '#';
        }

        // 前序遍历
        return "{$root->val}," . $this->serialize($root->left) . ',' .$this->serialize($root->right);
    }

    /**
     * 反序列化
     *
     * @param $root
     * @return BinaryTreeSerializeAndDeserializeNode|null
     */
    public function deserialize($root): ?BinaryTreeSerializeAndDeserializeNode
    {
        $this->arr = explode(',', $root);
        if (empty($this->arr)) {
            return null;
        }

        return $this->helper($this->arr);
    }

    /**
     * 构建二叉树
     *
     * @return BinaryTreeSerializeAndDeserializeNode|null
     */
    private function helper(): ?BinaryTreeSerializeAndDeserializeNode
    {
        $lastChar = array_shift($this->arr);
        if ($lastChar == '#') {
            return null;
        }

        $root = new BinaryTreeSerializeAndDeserializeNode((int)$lastChar);
        $root->left = $this->helper();
        $root->right = $this->helper();
        return $root;
    }
}