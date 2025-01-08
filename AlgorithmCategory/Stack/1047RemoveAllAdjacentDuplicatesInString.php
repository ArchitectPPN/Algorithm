<?php

namespace AlgorithmCategory\Stack\RemoveAllAdjacentDuplicatesInString;

# 1047.删除字符串中的所有相邻重复项
class Solution
{

    /**
     * @param String $s
     * @return String
     */
    function removeDuplicates(string $s): string
    {
        $length = strlen($s);

        $stack = [];
        for ($i = 0; $i < $length; $i++) {
            // 栈为空，则直接入栈
            if (empty($stack)) {
                $stack[] = $s[$i];
            } else {
                // 栈顶元素和当前元素相同，则删除栈顶元素
                if ($stack[count($stack) - 1] === $s[$i]) {
                    array_pop($stack);
                } else {
                    $stack[] = $s[$i];
                }
            }
        }

        return implode('', $stack);
    }
}