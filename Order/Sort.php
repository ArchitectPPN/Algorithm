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
     * 冒泡排序
     * 1. 比较相邻两个数字的大小, 如果第一个比第二个大, 交换位置
     * 2. 对每一组相邻数据做同样的工作,从开始到结尾的最后一对. 到此最后一位将是最大或最小的值
     * 3. 针对所有重复的元素重复上面的步骤, 除了最大或最小的那个值
     * 4. 持续每次对越来越少的元素重复上面的步骤，直到没有任何一对数字需要比较
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
     * 快速排序
     * 1. 从数列中挑出一个元素, 称之为"基准(pivot)"
     * 2. 重新排序数列, 所有元素比基准小的摆放在基准前面, 所有元素比基准大的摆在基准后面(相同的数可以放到任意一遍). 在这个分区退出之后, 该基准就处于数列的中间位置, 这个称为分区操作.
     * 3. 递归的把小于基准值元素的子数列和大于基准元素的子数列排序
     *
     * @return array
     * 
     */
    public function quickSort(): array
    {
        return $this->quickSortProcess($this->outOfOrder);
    }

    /**
     * 快速排序过程
     */
    private function quickSortProcess(array $outOfOrder): array
    {
        if (count($outOfOrder) <= 1) {
            return $outOfOrder;
        }

        // 把第一个元素作为基准
        $pivotNum = $outOfOrder[0];
        // 初始化左右分区
        $rightArr = $leftArr = [];

        for ($i = 1; $i < count($outOfOrder); $i++) {
            if ($outOfOrder[$i] > $pivotNum) {
                $rightArr[] = $outOfOrder[$i];
            } else {
                $leftArr[] = $outOfOrder[$i];
            }
        }

        $leftArr = $this->quickSortProcess($leftArr);
        $leftArr[] = $pivotNum;

        $rightArr = $this->quickSortProcess($rightArr);

        return array_merge($leftArr, $rightArr);
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
    private function swapPos(int $minPos, int $swapPos)
    {
        $temp = $this->outOfOrder[$swapPos];
        $this->outOfOrder[$swapPos] = $this->outOfOrder[$minPos];
        $this->outOfOrder[$minPos] = $temp;
    }
}

$outOfOrder = (new generateOutOfOrderArray(15))->get();
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
//$sortOrder = (new Sort($outOfOrder))->shellSort();
// 快速排序
$sortOrder = (new Sort($outOfOrder))->quickSort();
print_r($sortOrder);














