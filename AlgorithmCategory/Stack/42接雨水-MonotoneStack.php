<?php
# 42. 接雨水 https://leetcode.cn/problems/trapping-rain-water/description/

# 该题目可以使用数组的双指针解法，已解答
# 也可以使用动态规划思想

/**
 * 解法：单调递减栈
 * 思路：
 */
class TrappingRainWaterSolution
{
    /**
     * @param Integer[] $height
     * @return Integer
     */
    public function trap(array $height): int
    {
        // 初始化答案
        $ans = 0;
        // 声明一个栈
        $stack = [];
        // 获取数组的长度
        $len = count($height);

        for ($i = 0; $i < $len; $i++) {
            // 栈不为空且当前元素大于栈顶元素，这样才可能形成低洼， 才能够存水
            while (!empty($stack) && $height[$stack[count($stack) - 1]] < $height[$i]) {
                // 拿到栈顶元素
                $top = array_pop($stack);
                if (empty($stack)) {
                    break;
                }
                // 计算距离，也就是宽度
                $distance = $i - $stack[count($stack) - 1] - 1;
                $height = min($height[$stack[count($stack) - 1]], $height[$i]) - $height[$top];
                $ans += $distance * $height;
            }
            $stack[] = $i;
        }

        return $ans;
    }
}

$height = [
    4,
    2,
    0,
    3,
    2,
    5,
];

# 2025年2月11日
class TrappingRainWaterSolutionReviewOne
{
    /**
     * 使用单调递减栈来解决
     * 想要积水, 就必须满足产生低洼, 连续三个元素的高度满足, 左边高于中间, 中间低于右边, 例如: 1, 0, 2
     * 这样, 在0的位置就会产生低洼, 就可以蓄水
     * @param Integer[] $height
     * @return Integer
     */
    public function trap(array $height): int
    {
        // 初始化答案
        $ans = 0;
        // 初始化一个栈
        $stack = [];
        // 获取数组的长度
        $len = count($height);

        for ($i = 0; $i < $len; $i++) {
            while (!empty($stack) && $height[$i] > $height[end($stack)]) {
                // 出栈
                $top = array_pop($stack);
                // 栈为空, 说明左边已经没有元素了, 已经无法再形成低洼存水了, 直接跳出循环
                if (empty($stack)) {
                    break;
                }
                // 获取低洼的宽度
                $waterWidth = $i - end($stack) - 1;
                // 左右两边最低的高度作为低洼的高度, 然后减去中间的高度, 得到低洼的高度, 例如: 1, 0, 2, 木桶效应, 只能把最低的边作为高度
                $waterHeight = min($height[$i], $height[end($stack)]) - $height[$top];
                $ans += $waterWidth * $waterHeight;
            }

            // 将下标入栈
            $stack[] = $i;
        }

        return $ans;
    }
}