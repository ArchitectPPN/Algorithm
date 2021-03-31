<?php

require_once "../../GenerateOutOfOrderArray/GenerateOutOfOrderArray.php";

class FindSlidingWindowsMaximum
{
    private $noSortArray = [];
    private $length = 0;
    private $result = [];
    private $windowLength = 0;

    // 维护一个窗口，存储元素下标
    private $window = [];


    public function __construct(array $noSortArray, int $windowLength)
    {
        if (empty($noSortArray)) {
            return [];
        }

        $this->noSortArray = $noSortArray;
        $this->length = count($this->noSortArray);
        $this->windowLength = $windowLength;
    }

    public function maxSlidingWindow(): array
    {
        // 使用双向队列，该数据结构可以从两端以常数时间压入 / 弹出元素
        // 双端队列和普通队列最大的不同在于，它允许我们在队列的头尾两端都能在O(1) 的时间内进行数据的查看、添加和删除。

        // 算法非常直截了当：
        //处理前 k 个元素，初始化双向队列。
        //遍历整个数组。在每一步 :
        //清理双向队列 :
        //  - 只保留当前滑动窗口中有的元素的索引。
        //  - 移除比当前元素小的所有元素，它们不可能是最大的。
        //将当前元素添加到双向队列中。
        //将 deque[0] 添加到输出中。
        //返回输出数组。
        foreach ($this->noSortArray as $index => $item) {
            // 窗口已建立，最左侧的元素已不属于窗口，弹出最左侧元素
            if (!empty($this->window) && $index >= $this->windowLength + $this->window[0]) {
                array_shift($this->window);
            }

            while ($this->window && end($this->window) && $this->noSortArray[end($this->window)] <= $item) {
                array_pop($this->window);
            }

            $this->window[] = $index;
            if ($index >= $this->windowLength - 1) {
                $this->result[] = $this->noSortArray[$this->window[0]];
            }
        }

        return $this->result;
    }
}

//$outOfOrder = (new generateOutOfOrderArray(10))->get();
$outOfOrder = [2, 6, 8, 1, 4, 7, 9, 10];
//print_r($outOfOrder);

$maxNumArr = (new FindSlidingWindowsMaximum($outOfOrder, 3))->maxSlidingWindow();
print_r($maxNumArr);