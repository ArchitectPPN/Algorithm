<?php

# 316. 去除重复字母 https://leetcode.cn/problems/remove-duplicate-letters/

class RemoveDuplicateLettersSolution
{
    /**
     * @param String $s
     * @return String
     */
    function removeDuplicateLetters(string $s): string
    {
        $sLen = strlen($s);

        // 初始化 remark 数组
        $remark = array_fill(0, 26, [
            'exist' => false,
            'lastIndex' => -1,
        ]);

        // 记录每个字符最后出现的位置
        for ($i = 0; $i < $sLen; $i++) {
            $remark[ord($s[$i]) - ord('a')]['lastIndex'] = $i;
        }

        $ansStack = [];

        // 遍历字符串
        for ($i = 0; $i < $sLen; $i++) {
            $charIndex = ord($s[$i]) - ord('a');

            // 如果字符已经在栈中，跳过
            if ($remark[$charIndex]['exist']) {
                continue;
            }

            // 栈非空，且栈顶元素大于当前字符，且栈顶元素在后面还会出现
            while (
                !empty($ansStack)
                && ord(end($ansStack)) > ord($s[$i])
                && $remark[ord(end($ansStack)) - ord('a')]['lastIndex'] > $i
            ) {
                $top = array_pop($ansStack);
                $remark[ord($top) - ord('a')]['exist'] = false;
            }

            // 将当前字符入栈
            $ansStack[] = $s[$i];
            $remark[$charIndex]['exist'] = true;
        }

        return implode('', $ansStack);
    }
}

# 2025年2月17日

/**
 * 思路:
 * 首先要知道字典序是什么，字典序就是按照字母顺序排列的。
 * abc < bcd; 依次比较每一位的字符, a < b, 说明abc的字典序比bcd小;
 * abc < adc; 依次比较每一位的字符, 首字母相同, 比较第二位, b < d, 说明abc的字典序比adc小;
 * 题目要求去除重复的字母, 尽可能保证字典序最小. 这里附上一份ascii码表: https://tool.oschina.net/commons?type=4
 * 从这份ascii表可以知道, a-z转为int, 从1-26;
 * @author
 */
class RemoveDuplicateLettersSolutionOne
{
    /** @var array */
    private array $ansStack = [];

    function removeDuplicateLetters(string $s): string
    {
        // 初始化记忆数组
        $memoryArray = array_fill(
            0,
            26,
            [
                'exist' => false,
                'lastIndex' => -1,
            ]
        );
        $sLen = strlen($s);
        // 计算每位字符最后出现的位置
        for ($i = 0; $i < $sLen; $i++) {
            // 因为a-z的ascii是从1-26的,减去1,刚好对应到0-25的数组下标
            $charToInt = ord($s[$i]) - ord('a');
            $memoryArray[$charToInt]['lastIndex'] = $i;
        }

        // 开始遍历字符串
        for ($i = 0; $i < $sLen; $i++) {
            $charToInt = ord($s[$i]) - ord('a');
            // 如果字符已经在栈中, 跳过
            if ($memoryArray[$charToInt]['exist']) {
                continue;
            }

            // 尝试替换, 栈非空, 栈顶元素大于当前字符, 且栈顶元素在后面还会出现
            while (
                !empty($this->ansStack)
                && ord(end($this->ansStack)) > ord($s[$i])
                && $memoryArray[ord(end($this->ansStack)) - ord('a')]['lastIndex'] > $i) {
                // 出栈, 并标记为不存在
                $top = array_pop($this->ansStack);
                $memoryArray[ord($top) - ord('a')]['exist'] = false;
            }

            // 入栈
            $this->ansStack[] = $s[$i];
            // 标记为已存在
            $memoryArray[$charToInt]['exist'] = true;
        }

        return implode('', $this->ansStack);
    }
}