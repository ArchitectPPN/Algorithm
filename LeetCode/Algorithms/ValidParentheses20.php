<?php


class Solution
{
    // 因为题目中字符串不存在空的情况, 所以不需要判断字符串为空的情况
    public function isValid(string $checkString): bool
    {
        // 声明一个栈
        $stack = [];
        $mapping = [')' => '(', '}' => '{', ']' => '['];

        for ($index = 0; $index < strlen($checkString); $index++) {
            if (!isset($mapping[$checkString[$index]])) {
                array_unshift($stack, $checkString[$index]);
                // 如果为空, 说明不匹配, 直接返回false;
            } else if (empty($stack) || $mapping[$checkString[$index]] != array_shift($stack)) {
                return false;
            }
        }

        return empty($stack);
    }
}

$res = (new Solution())->isValid('');
var_dump($res);

/* 解决思路
 * 1.判断字符串第一个字符是不是右括号, 如果是右括号, 直接返回false;
 *
 * */