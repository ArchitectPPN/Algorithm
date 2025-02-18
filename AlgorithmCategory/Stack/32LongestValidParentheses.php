<?php
# 32. 最长有效括号 https://leetcode.cn/problems/longest-valid-parentheses/description/

class LongestValidParenthesesSolution
{
    /**
     * @param String $s
     * @return Integer
     */
    function longestValidParentheses(string $s): int
    {
        # (()
        // 初始化最大值
        $maxAns = 0;
        // 字符串长度
        $sLen = strlen($s);
        $splStack = new SplStack();
        // 用来处理边界问题
        $splStack->push(-1);

        for ($i = 0; $i < $sLen; $i++) {
            // 有括号，入栈
            if ($s[$i] === '(') {
                $splStack->push($i);
            } else {
                // 出栈
                $splStack->pop();
                // 栈为空
                if ($splStack->isEmpty()) {
                    // 将当前右括号的索引压入栈中，作为新的基准
                    $splStack->push($i);
                } else {
                    // 栈不为空
                    $maxAns = max($maxAns, $i - $splStack->top());
                }
            }
        }

        return $maxAns;
    }
}

$question = '(()';

$solution = new LongestValidParenthesesSolution();
$solution->longestValidParentheses($question);