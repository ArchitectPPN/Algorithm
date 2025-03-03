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

/**
 * Thinking:
 * 1. 每一个字母只可以出现一次
 * 2. 保证字符的相对顺序下尽可能保证字典序最小
 */
class RemoveDuplicateLettersSolutionTwo
{
    /**
     * 移除重复的字母并保证字典序最小
     * @param string $s
     * @return string
     */
    function removeDuplicateLetters(string $s): string
    {
        // 获取字符串的长度
        $sLen = strlen($s);
        // 最后的结果栈
        $ansStack = [];
        // init a array to store char last index
        // 统计出每一个字符最后出现的位置
        $charLastIndex = array_fill(0, 26, ['exist' => false, 'lastIndex' => -1]);
        for ($i = 0; $i < $sLen; $i++) {
            $charLastIndex[ord($s[$i]) - ord('a')]['lastIndex'] = $i;
        }


        for ($i = 0; $i < $sLen; $i++) {
            // 如果该字符已经存在栈中了，直接跳过
            $charIndex = ord($s[$i]) - ord('a');
            if ($ansStack && $charLastIndex[$charIndex]['exist']) {
                continue;
            }

            // 栈不为空，当前元素小于栈顶元素，栈顶元素在后面还有
            while ($ansStack
                && ord(end($ansStack)) > ord($s[$i])
                && $charLastIndex[ord(end($ansStack)) - ord('a')]['lastIndex'] > $i
            ) {
                // 弹出栈顶元素
                $top = array_pop($ansStack);
                // 标记栈顶元素不存在
                $charLastIndex[ord($top) - ord('a')]['exist'] = false;
            }

            // 入栈
            $ansStack[] = $s[$i];
            $charLastIndex[$charIndex]['exist'] = true;
        }

        // 返回连接在一起的字符串
        return implode('', $ansStack);
    }
}