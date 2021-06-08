<?php


class SearchSolution
{
    /**
     * 顺序查找
     *
     * @param array $nums
     * @param int $target
     * @return int
     */
    public function sequenceSearch(array $nums, int $target): int
    {
        for ($i = 0; $i < count($nums); $i++) {
            if ($nums[$i] == $target) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * 二分查找 (非递归)
     *
     * @param array $nums
     * @param int $target
     * @return float|int
     */
    public function binarySearch(array $nums, int $target): int
    {
        // 初始化最小下标 0
        $minIndex = 0;
        // 初始化最大下标
        $maxIndex = count($nums) - 1;

        // 当最小下标不小于最大下标时, 结束(必须为<=, 避免元素只有一个的时候出错)
        while ($minIndex <= $maxIndex) {
            // 设置中间数值
            $middleIndex = ceil(($maxIndex + $minIndex) / 2);

            if ($nums[$middleIndex] == $target) {
                return $middleIndex;
            } else if ($nums[$middleIndex] < $target) {
                $minIndex = $middleIndex + 1;
            } else {
                $maxIndex = $middleIndex - 1;
            }
        }

        // 未找到 -1
        return -1;
    }

    /**
     *
     * @param array $nums
     * @param int $target
     * @return int
     */
    public function searchNumber(array $nums, int $target): int
    {
        return $this->recursionBinarySearch($nums, $target, 0, count($nums) - 1);
    }

    /**
     * @param array $nums
     * @param int $target
     * @param int $minIndex
     * @param int $maxIndex
     * @return int
     */
    public function recursionBinarySearch(array $nums, int $target, int $minIndex, int $maxIndex): int
    {
        if ($minIndex > $maxIndex) return -1;

        // 获取到中间数值
        $middle = ceil(($minIndex + $maxIndex) / 2);

        if ($nums[$middle] == $target) return $middle;

        if ($nums[$middle] < $target) {
            $minIndex = $middle + 1;
        } else {
            $maxIndex = $middle - 1;
        }

        return $this->recursionBinarySearch($nums, $target, $minIndex, $maxIndex);
    }
}

$foundSearch = [1, 2, 4, 5, 6, 7, 8, 9, 10];
$foundSearch2 = [2, 5];

//var_dump((new SearchSolution())->sequenceSearch($foundSearch, 6));
//var_dump((new SearchSolution())->binarySearch($foundSearch2, 5));
var_dump((new SearchSolution())->searchNumber($foundSearch, 5));