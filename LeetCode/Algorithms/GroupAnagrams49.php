<?php

## 解法1：排序 https://leetcode.cn/problems/group-anagrams/

$question = ["eat", "tea", "tan", "ate", "nat", "bat"];

$answer = [];
foreach ($question as $value) {
    $sortStr = sortStr($value);

    $answer[$sortStr][] = $value;
}

/**
 * 字符串排序
 *
 * @param string $waitString 等待排序的字符串
 * @return string
 */
function sortStr(string $waitString): string
{
    $strArr = str_split($waitString);

    asort($strArr);

    return implode("", $strArr);
}

var_dump($answer);