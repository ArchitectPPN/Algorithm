<?php

namespace AddStringsSolution;

/**
 * 整体思路就是模拟加法，从低位开始相加，如果两个数位数不一样，则用0补齐位数，然后相加，如果相加结果大于10，则进位，然后继续相加，
 * 直到所有数相加结束，最后判断是否有进位，如果有进位，则将进位加到结果中，最后返回结果
 */
class Solution
{

    /**
     * @param String $num1
     * @param String $num2
     * @return String
     */
    function addStrings(string $num1, string $num2): string
    {
        $res = [];
        // 表示进位值
        $add = 0;

        // 我们把两个字符串想象为人工计算得过程:
        // num1 = 123
        // num2 =  56
        // 人工计算时，会先计算3+5得到个位数8, 3和5在数组中的下标为字符串长度-1;
        // 所以我们需要计算出两个字符串的长度, 然后从个位数开始相加;
        $num1Len = strlen($num1) - 1;
        $num2Len = strlen($num2) - 1;

        // 只要num1和num2没有遍及完毕，就一直需要计算下去
        // $add 进位大于0时，仍需进行一次计算
        while ($num2Len >= 0 || $num1Len >= 0 || $add > 0) {
            $tmpX = $num1Len >= 0 ? $num1[$num1Len] : 0;
            $tmpY = $num2Len >= 0 ? $num2[$num2Len] : 0;

            $tmpRes = $tmpX + $tmpY + $add;
            array_unshift($res, $tmpRes % 10);
            // 计算进位
            $add = intval($tmpRes / 10);

            $num1Len--;
            $num2Len--;
        }

        return implode('', $res);
    }
}