<?php


class LevelOrderSolution1
{
    /**
     * @var array 答案
     */
    private array $result = [];

    private function levelProcess($root, $depth)
    {
        if ($root == null) {
            return;
        }

        $this->result[$depth][] = $root->val;

        // 遍历左子树
        $this->levelProcess($root->left, $depth + 1);
        // 遍历右子树
        $this->levelProcess($root->right, $depth + 1);
    }

    public function levelOrder($root)
    {
        $this->levelProcess($root, 0);
    }
}

class LevelOrderSolution2
{
    /**
     * @var int 层级
     */
    private int $levelIndex = 0;

    /**
     * @var array 答案
     */
    private array $result = [];

    private function levelProcess($root)
    {
        if ($root == null) {
            return;
        }
        // 层级+1
        $this->levelIndex += 1;
        $this->result[$this->levelIndex] = $root->val;

        // 遍历左子树
        $this->levelProcess($root->left);
        // 遍历右子树
        $this->levelProcess($root->right);

        // 循环完毕后，层级-1 (还原现场)
        $this->levelIndex -= 1;
    }

    public function levelOrder($root): array
    {
        $this->levelProcess($root, 0);

        return $this->result;
    }
}

