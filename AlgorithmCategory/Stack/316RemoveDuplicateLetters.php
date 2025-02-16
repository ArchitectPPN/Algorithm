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
            'lastIndex' => -1
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
            while (!empty($ansStack) && ord(end($ansStack)) > ord($s[$i]) && $remark[ord(end($ansStack)) - ord('a')]['lastIndex'] > $i) {
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