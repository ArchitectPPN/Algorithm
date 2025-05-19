<?php

namespace AlgorithmCategory\Stack\ValidParentheses;

# [20. 有效的括号](https://leetcode.cn/problems/valid-parentheses/description/)

class Solution
{
    /**
     * 解法1 STACK
     *
     * @param array $str
     * @return bool
     */
    public function isValid(array $str): bool
    {
        // 空, 认为都匹配
        if (empty($str)) {
            return true;
        }
        // 长度为1, 认为不匹配
        if (count($str) == 1) {
            return false;
        }
        // 首位不是左括号, 认为不匹配
        if ($str[0] == ')' || $str[0] == ']' || $str[0] == '}') {
            return false;
        }

        $stack = [];

        foreach ($str as $c) {
            if ($c == '(' || $c == '[' || $c == '{') {
                $stack[] = $c;
                continue;
            }

            if ($c == ')' || $c == ']' || $c == '}') {
                // 出栈, 判断是否匹配
                $stackTopChar = array_pop($stack);
                if (
                    $stackTopChar == '(' && $c != ')'
                    || $stackTopChar == '[' && $c != ']'
                    || $stackTopChar == '{' && $c != '}'
                ) {
                    return false;
                }
            }
        }

        return empty($stack);
    }

    /**
     * 解法2 HASH + STACK
     * @param string $str
     * @return bool
     */
    public function isValidWithInputString(string $str): bool
    {
        // 如果字符串为空，认为有效
        if (empty($s)) {
            return true;
        }

        // 创建一个括号映射
        $bracket_map = [
            ')' => '(',
            '}' => '{',
            ']' => '[',
        ];

        // 初始化一个空栈
        $stack = [];

        // 遍历字符串中的每个字符
        for ($i = 0; $i < strlen($s); $i++) {
            $char = $s[$i];

            if (isset($bracket_map[$char])) {
                // 如果当前字符是右括号，检查栈顶元素是否为对应的左括号
                $top_element = !empty($stack) ? array_pop($stack) : '#';
                if ($bracket_map[$char] != $top_element) {
                    return false;
                }
            } else {
                // 如果当前字符是左括号，压入栈中
                $stack[] = $char;
            }
        }

        // 如果栈为空，说明所有括号都匹配
        return empty($stack);
    }
}