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
                if (empty($stack)) break;
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

$height = [4, 2, 0, 3, 2, 5];