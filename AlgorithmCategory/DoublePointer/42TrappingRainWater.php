<?php

namespace DoublePointer;

# 42. 接雨水 https://leetcode.cn/problems/trapping-rain-water/

class TrapSolution
{

    /**
     * 解法1：
     * @param Integer[] $height
     * @return Integer
     */
    function trap(array $height): int
    {
        if (empty($height)) {
            return 0;
        }
        // 我们把每个位置都想象为一个桶，想要知道这个桶里可以放多少水，要知道左边和右边桶高，桶最低的那个边减去当前高度，就是当前桶能放的水量。
        // 从0开始循环，第0位元素的高度就是其本身，接着从第一位开始循环，一直循环到最后一位，将当前桶的高度和前一个桶的高度比较，取较大值，就是当前桶的高度。
        // 循环结束后， 就得到了每个桶左边的高度。
        $preMax = [];
        $preMax[0] = $height[0];
        $length = count($height);
        for ($i = 1; $i < $length; $i++) {
            $preMax[$i] = max($preMax[$i - 1], $height[$i]);
        }

        // 这里从后往前循环，拿到桶右边的高度。
        $sufMax = [];
        $sufMax[$length - 1] = $height[$length - 1];
        for ($i = $length - 2; $i >= 0; $i--) {
            $sufMax[$i] = max($sufMax[$i - 1], $height[$i]);
        }

        // 经过两次循环，我们得到了每个桶左右两边的高度，然后计算每个桶能放多少水。
        // 那么每个桶能放的水，就是左右两边高度的较小值减去当前高度。
        // preMax存放桶左边的高度， sufMax存放桶右边的高度，height存放当前桶的高度。
        // 桶可以放多少水，我们就可以这么计算，min($sufMax[$i], $preMax[$i]) - $height[$i];
        $ans = 0;
        for ($i = 0; $i < $length; $i++) {
            $height = min($sufMax[$i], $preMax[$i]) - $height[$i];
            $ans += $height;
        }

        return $ans;
    }

    /**
     * 这个解法是上面方案的优化，我们用双指针来优化。
     * 上面的方案使用了额外的两个数组来保存桶的左右两侧高度。
     * 下面我们使用双指针来优化空间，使用两个变量来存储左右两侧高度。
     * @param array $height
     * @return int
     */
    function doublePointerTrap(array $height): int
    {
        // 还是将每一个位置都想象成一个桶，那么我们只需要知道当前桶的左边和右边的高度，就可以知道当前桶能放多少水。
        // 不同的是，我们在一个循环中完成这件事。
        // 我们用两个指针，一个指针从左边开始， 一个从右边开始。
        // 每次循环比较左右两边高度。
        // 如果左边小于右边，那么当前位置能放的水，就是左边桶高减去当前位置的高度。向右移动左指针。left++
        // 如果左边大于右边，那么当前位置能放的水，就是右边桶高减去当前位置的高度。向左移动右指针。right--
        // 如果两边相等，移动两侧都可以。

        $left = 0;
        $right = count($height) - 1;
        $ans = $preMax = $sufMax = 0;
        while ($left < $right) {
            $preMax = max($preMax, $height[$left]);
            $sufMax = max($sufMax, $height[$right]);
            if ($preMax < $sufMax) {
                $ans += $preMax - $height[$left];
                $left++;
            } else {
                $ans += $sufMax - $height[$right];
                $right--;
            }
        }

        return $ans;
    }
}