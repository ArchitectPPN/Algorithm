<?php

namespace SpiralOrderSolution;

class Solution
{
    /**
     * @param Integer[][] $matrix
     * @return Integer[]
     */
    function spiralOrder(array $matrix): array
    {
        $ans = [];
        // 如果给定的数组为空， 则返回空数组
        if (empty($matrix)) {
            return $ans;
        }

        /**
         * 1,  2,  3,  4
         * 5,  6,  7,  8
         * 9, 10, 11, 12
         *
         * // 遍历顺序为：向右->向下->向左->向上
         * 1 -> 2 -> 3 -> 4 -> 8 -> 12 -> 11 -> 10 -> 9 -> 5 -> 6 -> 7
         */
        // 我们将数组分为四个方向
        $upper = 0;
        // 下边界就是整个数组的长度
        $down = count($matrix) - 1;
        // 从上述的数组可以看到，left从0开始
        $left = 0;
        // right 从右边开始，由于数组每个元素的长度一致，所以可以取第一个元素的长度减1
        $right = count($matrix[0]) - 1;

        while (true) {
            // 从上边界开始循环，向右移动
            for ($i = $left; $i <= $right; $i++) $ans[] = $matrix[$upper][$i];
            // 重新设定边界
            $upper++;
            if ($upper > $down) break;

            // 向下移动，所以要处理有边界
            for ($i = $upper; $i <= $down; $i++) $ans[] = $matrix[$i][$right];
            // 重新设定边界
            $right--;
            if ($right < $left) break;

            // 向左移动，处理下边界
            for ($i = $right; $i >= $left; $i--) $ans[] = $matrix[$down][$i];
            // 重新设定边界
            $down--;
            if ($down < $upper) break;

            // 向上移动，处理左边界
            for ($i = $down; $i >= $upper; $i--) $ans[] = $matrix[$i][$left];
            $left++;
            if ($left > $right) break;

        }

        return $ans;
    }
}