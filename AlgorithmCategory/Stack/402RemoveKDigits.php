<?php

# 402. 移掉 K 位数字 https://leetcode.cn/problems/remove-k-digits/

/**
 * THINKING：
 * 我们可以反过来思考，从给的字符串中找出长度为(numLength - K)位的最小子序列
 */
class RemoveKDigitsSolution
{
    /**
     * @param String $num
     * @param Integer $k
     * @return String
     */
    function removeKDigits(string $num, int $k): string
    {
        $numLength = strlen($num);
        $k = $this->getK($numLength, $k);
        if ($k == 0) {
            return '0';
        }

        return $this->getMiniSubSequence($num, $numLength, $k);
    }

    /**
     * 计算K
     * @param int $numLength
     * @param int $k
     * @return int
     */
    private function getK(int $numLength, int $k): int
    {
        // 表示要移除所有的字符，所以最后的答案就是0
        if ($k >= $numLength) {
            return 0;
        }

        return $numLength - $k;
    }

    /**
     * @param string $num 给定字符串
     * @param int $numLength 给定字符串的长度
     * @param int $k 要求的长度
     * @return string
     */
    private function getMiniSubSequence(string $num, int $numLength, int $k): string
    {
        $ansStack = [];
        for ($i = 0; $i < $numLength; $i++) {
            // 栈不为空 且 栈顶元素大于当前元素 且 栈内元素+剩余元素仍然可以将$ansStack添置$k位长度
            while (
                $ansStack
                && end($ansStack) > $num[$i]
                && count($ansStack) + $numLength - $i > $k
            ) {
                // 弹出栈顶元素
                array_pop($ansStack);
            }

            $ansStack[] = $num[$i];
        }

        $ansStack = array_slice($ansStack, 0, $k);
        $ansStr = ltrim(implode('', $ansStack), '0');

        return $ansStr == '' ? '0' : $ansStr;
    }
}

$svc = new RemoveKDigitsSolution();
$num = "10200";
$k = 1;
echo $svc->removeKDigits($num, $k) . PHP_EOL;

$num = "1432219";
$k = 3;
echo $svc->removeKDigits($num, $k) . PHP_EOL;

$num = "10";
$k = 2;
echo $svc->removeKDigits($num, $k) . PHP_EOL;

class RemoveKDigitsSolutionReviewOne
{
    /**
     * THINKING:
     * 这次我们不修改K的含义
     * @param String $num
     * @param Integer $k
     * @return String
     */
    function removeKDigits(string $num, int $k): string
    {
        // 最小子序列栈
        $miniSubsequenceStack = [];

        // 数组的长度
        $arrLen = strlen($num);
        $maxLen = $arrLen - $k;
        if ($maxLen <= 0) {
            return '0';
        }

        for ($i = 0; $i < $arrLen; $i++) {
            // 栈不为空
            while (
                $miniSubsequenceStack
                && end($miniSubsequenceStack) > $num[$i]
                && $k > 0
            ) {
                array_pop($miniSubsequenceStack);
                $k--;
            }

            $miniSubsequenceStack[] = $num[$i];
        }

        $miniSubsequenceStack = array_slice($miniSubsequenceStack, 0, $maxLen);
        $ansStr = ltrim(implode('', $miniSubsequenceStack), '0');

        return $ansStr == '' ? '0' : $ansStr;
    }
}

$svc = new RemoveKDigitsSolutionReviewOne();
$num = "10200";
$k = 1;
echo $svc->removeKDigits($num, $k) . PHP_EOL;

$num = "1432219";
$k = 3;
echo $svc->removeKDigits($num, $k) . PHP_EOL;

$num = "10";
$k = 2;
echo $svc->removeKDigits($num, $k) . PHP_EOL;