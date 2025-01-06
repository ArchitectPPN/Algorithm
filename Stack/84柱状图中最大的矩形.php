<?php

/**
 * 栈相关的例题：
 *  42: 接雨水
 *  739: 每日温度
 *  496: 下一个更大元素
 *  316: 去除重复字母
 *  901: 股票价格跨度
 *  402: 移掉K位数字
 *  581: 最短无序连续子数组
 */

/**
 * 柱状图中最大的矩形
 */
class LargestRectangleInHistogramSolutionOne
{
    /**
     * @param Integer[] $heights
     * @return Integer
     */
    function largestRectangleArea(array $heights): int
    {
        // 数组元素为空时，最大矩形就是0
        $len = count($heights);
        if ($len == 0) {
            return 0;
        }
        // 数组元素只有一个时，最大矩形就是第一个元素
        if ($len == 1) {
            return $heights[0];
        }
        // 标记最大的矩形
        $area = 0;
        $stack = [];
        // [2, 1, 5, 6, 2, 3]
        // 2 stack为空，直接将2入栈
        // 1 stack栈顶为2，
        for ($i = 0; $i < $len; $i++) {
            // 栈顶元素严格大于左右两边元素
            while (!empty($stack) && $heights[$stack[count($stack) - 1]] > $heights[$i]) {
                // 弹出栈顶
                $height = $heights[array_pop($stack)];
                // 栈顶元素和当前元素相等，栈顶元素出栈
                while (!empty($stack) && $heights[$stack[count($stack) - 1]] == $height) {
                    array_pop($stack);
                }

                if (empty($stack)) {
                    $width = $i;
                } else {
                    $width = $i - $stack[count($stack) - 1] - 1;
                }

                $area = max($area, $width * $height);
            }

            // 入栈，存储下标
            $stack[] = $i;
        }

        // 将栈内所有元素弹出，计算栈内元素下标对应高度最大的矩形
        while (!empty($stack)) {
            $height = $heights[array_pop($stack)];
            // 栈顶元素和当前元素相等，栈顶元素出栈
            while (!empty($stack) && $heights[$stack[count($stack) - 1]] == $height) {
                array_pop($stack);
            }

            if (empty($stack)) {
                $width = $len;
            } else {
                $width = $len - $stack[count($stack) - 1] - 1;
            }

            $area = max($area, $width * $height);
        }

        return $area;
    }
}

$question = [2, 1, 5, 6, 2, 3];

$solution = new LargestRectangleInHistogramSolutionOne();
$solution->largestRectangleArea($question);

class LargestRectangleInHistogramSolutionTwo
{
    function largestRectangleArea(array $heights): int
    {
        // 数组元素为空时，最大矩形就是0
        $len = count($heights);
        if ($len == 0) {
            return 0;
        }
        // 数组元素只有一个时，最大矩形就是第一个元素
        if ($len == 1) {
            return $heights[0];
        }

        // 头部和尾部添加哨兵
        array_unshift($heights, 0);
        $heights[] = 0;
        $len += 2;

        // 标记最大的矩形
        $area = 0;
        // 将栈顶元素赋值为0
        $stack[] = 0;
        for ($i = 1; $i < $len; $i++) {
            while ($heights[$stack[count($stack) - 1]] > $heights[$i]) {
                $height = $heights[array_pop($stack)];
                // 栈顶元素和当前元素相等，栈顶元素出栈
                while ($heights[$stack[count($stack) - 1]] == $height) {
                    array_pop($stack);
                }
                $width = $i - $stack[count($stack) - 1] - 1;
                $area = max($area, $width * $height);
            }

            // 存储下标
            $stack[] = $i;
        }

        return $area;
    }
}