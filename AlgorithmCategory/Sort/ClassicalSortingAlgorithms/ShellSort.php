<?php

require_once "../GenerateOutOfOrderArray/GenerateOutOfOrderArray.php";

class ShellSort
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
     * 希尔排序(改进的插入排序)
     * 1. 先将整个待排序的记录序列分割成为若干子序列分别进行直接插入排序
     * 2. 等整个序列中的记录基本有序时, 在对全体记录进行依次直接排序
     *
     */
    public function shellSort(): array
    {
        $loopTimes = 0;
        for ($gap = 4; $gap >= 1; $gap = $gap / 2) {
            for ($firstStart = $gap; $firstStart < $this->outOfOrderLength; $firstStart++) {
                for ($secondStart = $firstStart; $secondStart >= $gap; $secondStart -= $gap) {
                    if ($this->outOfOrder[$secondStart] < $this->outOfOrder[$secondStart - $gap]) {
                        $this->swapPos($secondStart, $secondStart - $gap);
                        $loopTimes++;
                    }
                }
            }
        }

        echo $loopTimes;

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

// 希尔排序
$sortOrder = new ShellSort($outOfOrder)->shellSort();
print_r($sortOrder);

















