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
                $num2 = array_pop($stack);
                $num1 = array_pop($stack);

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

# 2025年2月25日

class EvaluateReversePolishNotationSolutionReviewOne
{
    public function evalRPN(array $tokens): int
    {
        $arrLen = count($tokens);
        $stack = new SplStack();
        for ($i = 0; $i < $arrLen; $i++) {
            if ($tokens[$i] == "+" || $tokens[$i] == "-" || $tokens[$i] == "*" || $tokens[$i] == "/") {
                $oneNum = $stack->pop();
                $twoNum = $stack->pop();
                $resVal = 0;

                switch ($tokens[$i]) {
                    case "+":
                        $resVal = $oneNum + $twoNum;
                        break;
                    case "-":
                        $resVal = $oneNum - $twoNum;
                        break;
                    case "*":
                        $resVal = $oneNum * $twoNum;
                        break;
                    case "/":
                        $resVal = intval($oneNum / $twoNum);
                        break;
                }
                // 计算完毕后，放入栈中，以便后续继续使用
                $stack->push($resVal);
                continue;
            }

            $stack->push($tokens[$i]);
        }

        return $stack->pop();
    }
}

# 2025年2月27日
class EvaluateReversePolishNotationSolutionReviewTwo
{
    public function evalRPN(array $tokens): int
    {
        $stack = new SplStack();

        $operator = [
            '+',
            '-',
            '*',
            '/',
        ];
        foreach ($tokens as $token) {
            if (in_array($token, $operator)) {
                $num2 = $stack->pop();
                $num1 = $stack->pop();

                switch ($token) {
                    case '+':
                        $stack->push($num1 + $num2);
                        break;
                    case '-':
                        $stack->push($num1 - $num2);
                        break;
                    case '*':
                        $stack->push($num1 * $num2);
                        break;
                    case '/':
                        $stack->push(intval($num1 / $num2));
                        break;
                }
                continue;
            }

            // 遇到数字就入栈
            $stack->push($token);
        }

        return $stack->top();
    }
}

# 2025年2月27日

/**
 * Thinking
 * 从前向后进行遍历，遇到数字就入栈，遇到运算符就出栈计算，把计算的结果再入栈
 */
class EvaluateReversePolishNotationSolutionReviewThree
{
    /**
     * @param array $tokens
     * @return int
     */
    public function evalRPN(array $tokens): int
    {
        $stack = new SplStack();
        $arrLen = count($tokens);
        for ($i = 0; $i < $arrLen; $i++) {
            switch ($tokens[$i]) {
                case "+":
                case "-":
                case "*":
                case "/":
                    $this->operate($stack, $tokens[$i]);
                    break;
                default:
                    $stack->push($tokens[$i]);
                    break;
            }
        }

        return $stack->pop();
    }

    /**
     * @param SplStack $stack
     * @param string $operate 运算符
     * @return void
     */
    private function operate(SplStack $stack, string $operate): void
    {
        // ["1", "2", "+"] => 1 + 2
        // 2会被先弹出来，2在运算符之后，这个顺序要注意
        // 这里要记住，先进后出
        $numTwo = $stack->pop();
        $numOne = $stack->pop();
        $calVal = 0;
        switch ($operate) {
            case "+":
                $calVal = $numOne + $numTwo;
                break;
            case "-":
                $calVal = $numOne - $numTwo;
                break;
            case "*":
                $calVal = $numOne * $numTwo;
                break;
            case "/":
                $calVal = intval($numOne / $numTwo);
                break;
        }
        $stack->push($calVal);
    }
}

$svc = new EvaluateReversePolishNotationSolutionReviewThree();
echo $svc->evalRPN(["2", "1", "+", "3", "*"]);

# 2025年4月2日

class EvaluateReversePolishNotationSolutionReviewFour
{
    /**
     * 计算
     * @param array $tokens
     * @return int
     */
    public function evalRPN(array $tokens): int
    {
        // tokens的长度
        $tokensLen = count($tokens);
        if ($tokensLen == 0) {
            return 0;
        } else if ($tokensLen == 1) {
            return $tokens[0];
        }

        $splStack = new splStack();
        for ($i = 0; $i < $tokensLen; $i++) {
            if (
                $tokens[$i] != '+'
                && $tokens[$i] != '-'
                && $tokens[$i] != '*'
                && $tokens[$i] != '/'
            ) {
                $splStack->push($tokens[$i]);
            } else {
                $secondNum = $splStack->pop();
                $firstNum = $splStack->pop();
                switch ($tokens[$i]) {
                    case '-':
                        $splStack->push($firstNum - $secondNum);
                        break;

                    case '+':
                        $splStack->push($firstNum + $secondNum);
                        break;

                    case '*':
                        $splStack->push($firstNum * $secondNum);
                        break;

                    case '/':
                        $splStack->push(intval($firstNum / $secondNum));
                        break;
                }
            }
        }

        return intval($splStack->pop());
    }
}

$svc = new EvaluateReversePolishNotationSolutionReviewFour();
echo $svc->evalRPN(["2", "1", "+", "3", "*"]);