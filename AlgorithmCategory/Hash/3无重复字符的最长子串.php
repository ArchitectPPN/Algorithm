<?php

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
     * @return int
     */
    function lengthOfLongestSubstring(string $s): int
    {
        if (strlen($s) == 1) {
            return 1;
        } else if (strlen($s) == 0) {
            return 0;
        }

        $ans = [];
        $map = [];
        $len = strlen($s) - 1;
        for ($i = 0; $i <= $len; $i++) {
            if (isset($map[$s[$i]])) {
                $map = $this->getBetween($s, $map[$s[$i]] + 1, $i);
            } else {
                $map[$s[$i]] = $i;
            }

            $ans = count($map) > count($ans) ? array_keys($map) : $ans;
        }

        return count($ans);
    }

    /**
     * @param string $str question
     * @param int $start 重复字符第一次出现的位置
     * @param int $end 重复字符第二次出现的位置
     * @return array
     */
    private function getBetween(string $str, int $start, int $end): array
    {
        // 对于 dvdf 这种字符串，d为重复出现的字符，但是我们仍然需要第二个d之前的那个字符v
        // vdf才是符合要求的
        $arr = [];
        for ($i = $start; $i <= $end; $i++) {
            $arr[$str[$i]] = $i;
        }

        return $arr;
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
