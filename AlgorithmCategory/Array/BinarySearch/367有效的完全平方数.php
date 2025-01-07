<?php

namespace AlgorithmCategory\Array\BinarySearch;

class Solution
{
    /**
     * 求有效的平方根
     * 解题思路：
     *  考虑使用二分查找来优化方法二中的搜索过程。因为 num 是正整数，所以若正整数 x 满足 x*x=num，
     *  则 x 一定满足 1 ≤ x ≤ num。
     *  于是我们可以将 1 和 num 作为二分查找搜索区间的初始边界。
     * 细节：
     *  因为我们在移动左侧边界 left 和右侧边界 right 时，新的搜索区间都不会包含被检查的下标 mid，
     *  所以搜索区间的边界始终是我们没有检查过的。
     * 因此，当 left=right 时，我们仍需要检查 mid=(left+right)/2。
     * @param int $num
     * @return bool
     */
    function isPerfectSquare(int $num): bool
    {
        // 初始化left和right
        $left = 0;
        $right = $num;

        while($left <= $right) {
            $mid = $left + intdiv($right - $left, 2);
            $tmp = $mid * $mid;
            if ($tmp < $num) {
                $left = $mid + 1;
            } else if ($tmp > $num) {
                $right = $mid - 1;
            } else {
                return TRUE;
            }
        }

        return FALSE;
    }
}