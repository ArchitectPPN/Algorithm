<?php

require_once "../GenerateOutOfOrderArray/GenerateOutOfOrderArray.php";

class Sort
{
    private $outOfOrder = [];
    private $outOfOrderLength = 0;

    public function __construct(array $outOfOrder)
    {
        $this->outOfOrder = $outOfOrder;
        $this->outOfOrderLength = count($this->outOfOrder);
    }

    /**
     * 选择排序
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
     * 冒泡排序
     *
     * @return array
     */
    public function popSort(): array
    {
        for ($startPos = 0; $startPos < $this->outOfOrderLength; $startPos++) {
            for ($second = $startPos + 1; $second < $this->outOfOrderLength; $second++) {
                if ($this->outOfOrder[$startPos] < $this->outOfOrder[$second]) {
                    $this->swapPos($startPos, $second);
                }
            }
        }

        return $this->outOfOrder;
    }

    /**
     * 插入排序
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

    public function quickSort(): array
    {

    }

    /**
     * 希尔排序
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
    private function swapPos(int $minPos, int $swapPos)
    {
        $temp = $this->outOfOrder[$swapPos];
        $this->outOfOrder[$swapPos] = $this->outOfOrder[$minPos];
        $this->outOfOrder[$minPos] = $temp;
    }
}

$outOfOrder = (new generateOutOfOrderArray(100))->get();
print_r($outOfOrder);
//$outOfOrder = [1,15,2,6,5,4,8,7,9,10,3,14,12,11,13];

// 选择排序
//$sortOrder = (new Sort($outOfOrder))->selectSort();
//print_r($sortOrder);
//// 冒泡排序
//$sortOrder = (new Sort($outOfOrder))->popSort();
//print_r($sortOrder);
//// 插入排序
//$sortOrder = (new Sort($outOfOrder))->insertSort();
//print_r($sortOrder);
// 希尔排序
$sortOrder = (new Sort($outOfOrder))->shellSort();
print_r($sortOrder);














