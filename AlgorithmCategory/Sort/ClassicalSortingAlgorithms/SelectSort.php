<?php

class SelectSort
{
    /** @var array 需要排序的数组 */
    private array $outOfOrder = [];
    /** @var int 需要排序数组的长度 */
    private int $outOfOrderLength = 0;

    public function __construct(array $outOfOrder)
    {
        $this->outOfOrder = $outOfOrder;
        $this->outOfOrderLength = count($this->outOfOrder);
    }

    /**
     * 选择排序
     * 思路:
     * 1. 首先在未排序序列中找到最小（大）元素，存放到排序序列的起始位置。
     * 2. 再从剩余未排序元素中继续寻找最小（大）元素，然后放到已排序序列的末尾。
     * 3. 重复第二步，直到所有元素均排序完毕。
     *
     * 优化思路:
     * 双向循环, 同时查找最大和最小值;
     *
     * @return array
     */
    public function selectSort(): array
    {
        for ($firstStartPos = 0; $firstStartPos < $this->outOfOrderLength; $firstStartPos++) {
            // 初始化最小位置, 将第一次循环的位置视为最小最值, 每次都从最小位置开始循环
            $minPos = $firstStartPos;
            // 循环找到数据中最小的数据
            for ($secondStartPos = $firstStartPos + 1; $secondStartPos < $this->outOfOrderLength; $secondStartPos++) {
                $minPos = $this->outOfOrder[$secondStartPos] < $this->outOfOrder[$minPos] ? $secondStartPos : $minPos;
            }

            // 交换位置
            $this->swapPos($minPos, $firstStartPos);
        }

        return $this->outOfOrder;
    }

    /**
     * 交换位置
     *
     * @param int $minPos
     * @param int $swapPos
     */
    private function swapPos(int $minPos, int $swapPos): void
    {
        $temp = $this->outOfOrder[$swapPos];
        $this->outOfOrder[$swapPos] = $this->outOfOrder[$minPos];
        $this->outOfOrder[$minPos] = $temp;
    }
}

$outOfOrder = [1, 15, 2, 6, 5, 4, 8, 7, 9, 10, 3, 14, 12, 11, 13];

// 选择排序
$sortOrder = new SelectSort($outOfOrder)->selectSort();
print_r($sortOrder);

