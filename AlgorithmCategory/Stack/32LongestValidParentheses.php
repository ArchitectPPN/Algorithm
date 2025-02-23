<?php
# 32. 最长有效括号 https://leetcode.cn/problems/longest-valid-parentheses/description/

# 2025年2月18日

/**
 * 思路(thinking)：
 * 遇到一对匹配的括号就将其消除，然后计算当前元素到栈顶元素的距离
 * 我们一开始假设问题是：()
 * 刚开始遇到左括号，我们直接压入栈，然后遇到右括号，出栈。这时候字符串的长度为2。长度计算时，使用当前字符下标1，减去栈顶下标，但是由于栈顶
 * 为空，所以计算的值是不正确的，于是我们需要在一开始就设置一个分割符，假设栈顶为一个空字符，0的前一个下标为-1，所以将-1压入栈中。
 * 思路和算法
 * 相信大多数人对于这题的第一直觉是找到每个可能的子串后判断它的有效性，但这样的时间复杂度会达到O(n^3)，
 * 无法通过所有测试用例。但是通过栈，我们可以在遍历给定字符串的过程中去判断到目前为止扫描的子串的有效性，同时能得到最长有效括号的长度。
 * 具体做法是我们始终保持栈底元素为当前已经遍历过的元素中「最后一个没有被匹配的右括号的下标」，这样的做法主要是考虑了边界条件的处理，
 * 栈里其他元素维护左括号的下标：
 * 对于遇到的每个‘(’，
 *  我们将它的下标放入栈中
 * 对于遇到的每个‘)’，我们先弹出栈顶元素表示匹配了当前右括号：
 *  如果栈为空，说明当前的右括号为没有被匹配的右括号，我们将其下标放入栈中来更新我们之前提到的「最后一个没有被匹配的右括号的下标」
 *  如果栈不为空，当前右括号的下标减去栈顶元素即为「以该右括号为结尾的最长有效括号的长度」
 * 我们从前往后遍历字符串并更新答案即可。
 * 需要注意的是，如果一开始栈为空，第一个字符为左括号的时候我们会将其放入栈中，这样就不满足提及的「最后一个没有被匹配的右括号的下标」，为了保持统一，我们在一开始的时候往栈中放入一个值为−1的元素。
 */
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
        // 用来处理边界问题，为什么要压入-1？
        // 我们可以这样理解:
        // 这是字符串:        (  (  )
        // 这是数组的下标: -1, 0, 1, 2
        // 开始压入一个-1也符合逻辑，0 的前一位就是-1， 也是为了处理第一位是")"有括号的场景
        $splStack->push(-1);

        for ($i = 0; $i < $sLen; $i++) {
            // 有括号，入栈
            if ($s[$i] === '(') {
                $splStack->push($i);
            } else {
                // 出栈
                $splStack->pop();
                // 栈为空，说明这个右括号没有能与之匹配的左括号，我们将其作为新的基准压入栈中
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
$question = ')()())';

$solution = new LongestValidParenthesesSolution();
$solution->longestValidParentheses($question);

# 2025年2月19日
class LongestValidParenthesesSolutionReviewOne
{
    /**
     * thinking:
     * @param string $s
     * @return int
     */
    public function longestValidParentheses(string $s): int
    {
        // 初始化一下答案
        $maxAns = 0;
        // 初始化一个栈
        $splStack = new SplStack();
        // 压入下标-1, 解决边界问题, 默认下标为0(第一个字符)的时候, 没有匹配的括号
        $splStack->push(-1);
        // 计算字符串的长度
        $sLen = strlen($s);
        for ($i = 0; $i < $sLen; $i++) {
            // 左括号直接入栈
            if ($s[$i] == "(") {
                $splStack->push($i);
                continue;
            }

            // 右括号出栈
            $splStack->pop();
            if ($splStack->isEmpty()) {
                // 说明当前右括号没有匹配的左括号, 将当前右括号入栈, 作为新的基准
                $splStack->push($i);
            } else {
                // 栈不为空, 计算当前右括号与栈顶元素的距离, 保留最大值
                $maxAns = max($maxAns, $i - $splStack->top());
            }
        }

        return $maxAns;
    }
}

/**
 * thinking:
 * 使用栈来解决该问题;
 * 1. 我们将每一个 "(" 入栈
 * 2. 遇到的每一个 ")" 都出栈
 *  如果栈为空, 说明当前的右括号没有匹配的左括号, 将当前右括号入栈, 作为新的边界
 *  如果栈不为空, 计算当前右括号与栈顶元素的距离, 保留最大值
 * @author niujunqing
 */
class LongestValidParenthesesSolutionReviewTwo
{
    /**
     * thinking:
     * @param string $s
     * @return int
     */
    public function longestValidParentheses(string $s): int
    {
        // 字符串的长度
        $sLen = strlen($s);
        // 初始化一个栈
        $stack = new SplStack();
        $stack->push(-1);
        // 最大的长度
        $maxAns = 0;

        for ($i = 0; $i < $sLen; $i++) {
            // 左括号下标入栈
            if ($s[$i] == "(") {
                $stack->push($i);
            } else {
                $stack->pop();
                if ($stack->isEmpty()) {
                    $stack->push($i);
                } else {
                    // 更新最大值
                    $maxAns = max($maxAns, $i - $stack->top());
                }
            }
        }

        return $maxAns;
    }
}