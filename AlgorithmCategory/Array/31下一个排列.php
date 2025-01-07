<?php


namespace AlgorithmCategory\Array;
class NextPermutationSolution
{
    /**
     * 画图说明：
     * 4，5，2，6，3，1
     * 根据题目我们知道，如果数组是倒序的，我们要返回最小的排序，也就是正序排序：1，2，3，4，5，6
     * 很明显，上述数组不是倒序，所以我们要找到他的中心点，也就是4，5，2，6，3，1中的2，因为从这里开始，k-1不再大于k
     * 找到k之后，进行第二步，找到比k-1第一个大的数组，也就是3，进行交换
     * 交换完毕后，数组变为：4,5,3,6,2,1
     * 可以看到3之后的数组还是有序的，我们将其倒序即可
     */

    /**
     * 视频题解：
     * https://www.bilibili.com/video/BV1dT4y1y78u/?spm_id_from=333.337.search-card.all.click&vd_source=fbff21b18c60ea6baf150910c8bd2c70
     * @param Integer[] $nums
     * @return void
     */
    function nextPermutation(array &$nums): void
    {
        // 获取数组的长度
        $arrLength = count($nums) - 1;
        $k = $arrLength;

        // 检查数组是否倒序数组
        while ($k > 0 && $nums[$k - 1] >= $nums[$k]) {
            $k--;
        }

        // 数组为倒叙，直接将整个数组逆序即可
        if ($k <= 0) {
            $this->reverseArr($nums, 0, $arrLength);
            return;
        }

        // 说明数组非倒序，找到比K-1大的第一个数
        $t = $arrLength;
        while ($t > 0 && $nums[$t] <= $nums[$k - 1]) {
            $t--;
        }

        // 交换k-1 和 $t
        $this->swap($nums, $k - 1, $t);

        // 剩下的时有序的，倒叙即可
        $this->reverseArr($nums, $k, $arrLength);
    }

    /**
     * @param array $nums
     * @param int $start
     * @param int $arrLength
     * @return void
     */
    private function reverseArr(array &$nums, int $start, int $arrLength): void
    {
        $end = $arrLength;

        for ($i = $start; $i <= $arrLength; $i++) {
            // 如果$i >= $end，则说明已经交换完毕，结束循环
            if ($i >= $end) {
                break;
            }

            $this->swap($nums, $i, $end);
            $end--;
        }
    }

    /**
     * 反转
     * @param array $nums
     * @param int $dest
     * @param int $source
     * @return void
     */
    private function swap(array &$nums, int $dest, int $source): void
    {
        $tmp = $nums[$dest];
        $nums[$dest] = $nums[$source];
        $nums[$source] = $tmp;
    }
}