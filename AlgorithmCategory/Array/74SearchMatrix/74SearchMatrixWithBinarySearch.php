<?php

# 240. 搜索二维矩阵 II https://leetcode.cn/problems/search-a-2d-matrix-ii/description

/**
 * 逐行进行二分搜索
 * @author niujunqing
 */
class SearchMatrixWithBinarySearch
{
    /**
     * @param array $matrix
     * @param int $target
     * @return bool
     */
    public function searchMatrix(array $matrix, int $target): bool
    {
        if (empty($matrix)) {
            return false;
        }

        foreach ($matrix as $arr) {
            $right = count($arr);

            if ($this->binarySearch($arr, $target, $right - 1, 0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 二分搜索
     * @param array $targetArr
     * @param int $target
     * @param int $right
     * @param int $left
     * @return bool
     */
    private function binarySearch(array $targetArr, int $target, int $right, int $left): bool
    {
        while ($right >= $left) {
            $mid = intval(($right + $left) / 2);
            if ($targetArr[$mid] == $target) {
                return true;
            } elseif ($targetArr[$mid] > $target) {
                $right = $mid - 1;
            } else {
                $left = $mid + 1;
            }
        }

        return false;
    }
}

$questions = [
    [
        [
            [1, 2, 3],
            [2, 3, 4],
            [5, 6, 7],
        ],
        2,
    ],
];

$svc = new SearchMatrixWithBinarySearch();
foreach ($questions as $question) {
    $svc->searchMatrix($question[0], $question[1]);
}

