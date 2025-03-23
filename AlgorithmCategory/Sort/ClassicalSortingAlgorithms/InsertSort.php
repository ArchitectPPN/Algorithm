<?php

class InsertSort
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
     * 插入排序
     * 1. 把一组数据的第一个元素看做一个有序数据, 然后将第二个到最后一个视为待排序数据
     * 2. 从头开始扫描未排序序列, 将扫描的每个元素插入有序序列的适当位置, 如果待插入的元素与有序序列中的某个元素相等,则将待插入元素插入到相等元素的后面.
     *
     * @return array
     */
    public function insertSort(): array
    {
        for ($firstStart = 1; $firstStart < $this->outOfOrderLength; $firstStart++) {
            for ($secondStart = $firstStart; $secondStart > 0; $secondStart--) {
                if ($this->outOfOrder[$secondStart] < $this->outOfOrder[$secondStart - 1]) {
                    $this->swapPos($secondStart, $secondStart - 1);
                }
            }
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

// 插入排序
$sortOrder = new InsertSort($outOfOrder)->insertSort();
print_r($sortOrder);