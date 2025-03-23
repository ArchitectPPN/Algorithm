<?php

class QuickSort
{
    /** @var array 需要排序的数组 */
    private array $outOfOrder = [];

    public function __construct(array $outOfOrder)
    {
        $this->outOfOrder = $outOfOrder;
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
}

$outOfOrder = [1, 15, 2, 6, 5, 4, 8, 7, 9, 10, 3, 14, 12, 11, 13];

// 快速排序
$sortOrder = new QuickSort($outOfOrder)->quickSort();
print_r($sortOrder);
