<?php

# 3. 无重复字符的最长子串 https://leetcode.cn/problems/longest-substring-without-repeating-characters/

namespace HashLengthOfLongestSubstring;

/**
 * 整体思路：
 *  1. 由于题目要求只出现一次，所以我们可以用Map来存储当前已出现过的字符，key为出现过的字符，value为字符的下标
 *  2. 需要考虑特殊的边界条件，当字符串长度为1时，直接返回1，当字符串长度为0时，直接返回0
 *  3. 遍历整个字符串，当出现重复的字符时，把当前的map的key更新到ans中去，同时更新当前map，
 *     更新map时需要注意的时，重复字符出现位置+1的字符到当前重复字符第二次出现位置中间的字符一同写入map， 举例：dvopdf ，如果不把vop写入map中，最后的答案就会不准确
 */
class Solution
{

    /**
     * @param String $s
     * @return Integer
     */
    function lengthOfLongestSubstring(string $s): int
    {
        $n = strlen($s);
        $maxLen = 0;
        $charMap = [];
        $left = 0;

        for ($right = 0; $right < $n; $right++) {
            $char = $s[$right];
            // 如果字符已存在且在窗口内，调整左边界
            if (isset($charMap[$char]) && $charMap[$char] >= $left) {
                $left = $charMap[$char] + 1;
            }
            // 更新字符位置
            $charMap[$char] = $right;
            // 计算当前窗口长度
            $currentLen = $right - $left + 1;
            $maxLen = max($maxLen, $currentLen);
        }

        return $maxLen;
    }
}

$testCase = [
    ["question" => "abcabcbb", "ans" => 3],
    ["question" => "bbbbb", "ans" => 1],
    ["question" => "pwwkew", "ans" => 3],
    ["question" => " ", "ans" => 1],
    ["question" => "au", "ans" => 2],
    ["question" => "aau", "ans" => 2],
    ["question" => "dvdf", "ans" => 3],
];

$str = " ";
$solution = new Solution();
//echo $solution->lengthOfLongestSubstring("dvdf");


foreach ($testCase as $test) {
    if ($test['ans'] == $solution->lengthOfLongestSubstring($test['question'])) {
        continue;
    }

    var_dump("error: ", $test);
}
