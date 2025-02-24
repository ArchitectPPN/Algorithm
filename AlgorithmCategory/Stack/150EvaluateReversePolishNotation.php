<?php

# 150. 逆波兰表达式求值 https://leetcode.cn/problems/evaluate-reverse-polish-notation/description/

class EvaluateReversePolishNotationSolution
{
    /**
     * thinking:
     * 遇到数字时, 直接入栈;
     * 遇到运算符, 栈顶两个元素出栈, 进行运算, 结果入栈;
     * @param array $tokens
     * @return int
     */
    public function evalRPN(array $tokens): int
    {
        $stack = [];
        foreach ($tokens as $token) {
            if ($token == "+" || $token == "-" || $token == "*" || $token == "/") {
                // 去除栈顶两个数字
                $num1 = array_pop($stack);
                $num2 = array_pop($stack);

                switch ($token) {
                    case "+":
                        $stack[] = $num2 + $num1;
                        break;
                    case "-":
                        $stack[] = $num2 - $num1;
                        break;
                    case "*":
                        $stack[] = $num2 * $num1;
                        break;
                    case "/":
                        $stack[] = (int)($num2 / $num1);
                }
                continue;
            }

            // 数字入栈
            $stack[] = $token;
        }

        return empty($stack) ? 0 : array_pop($stack);
    }
}