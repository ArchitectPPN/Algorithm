<?php

# 242. 有效的字母异位词 https://leetcode.cn/problems/valid-anagram/description/

/**
 * THINKING
 * 获取两个字符串的长度， 如果两个字符串长度不一致，直接返回false
 * 然后统计每个字符串中每个字母的数量，最后比较两个字符串中每一个字符的数量是否相等，若相等，则说明两个字符串为字母异位词
 */
class IsAnagramSolution
{
    /**
     * @param string $s
     * @param string $t
     * @return Boolean
     */
    function isAnagram(string $s, string $t): bool
    {
        // 获取两个字符串的长度
        $sLen = strlen($s);
        $tLen = strlen($t);
        if ($sLen != $tLen) {
            return false;
        }

        $sHash = $tHash = [];
        for ($i = 0; $i < $sLen; $i++) {
            if (isset($sHash[$s[$i]])) {
                $sHash[$s[$i]] += 1;
                continue;
            }
            $sHash[$s[$i]] = 1;
        }

        for ($i = 0; $i < $tLen; $i++) {
            if (isset($sHash[$t[$i]])) {
                $tHash[$t[$i]] += 1;
                continue;
            }
            $tHash[$t[$i]] = 1;
        }

        foreach ($sHash as $sHashKey => $sHashValue) {
            if (!isset($tHash[$sHashKey]) || $sHashValue != $tHash[$sHashKey]) {
                return false;
            }
        }

        return true;
    }
}

$questions = [
    ['anagram', 'nagaram'],
    ['rat', 'cat'],
];

$svc = new IsAnagramSolution();

foreach ($questions as $question) {
    $res = $svc->isAnagram($question[0], $question[1]);
    var_dump($res);
}

class IsAnagramSolutionTwo
{
    /**
     * @param string $s
     * @param string $t
     * @return Boolean
     */
    function isAnagram(string $s, string $t): bool
    {
        // 获取两个字符串的长度
        $sLen = strlen($s);
        $tLen = strlen($t);
        if ($sLen != $tLen) {
            return false;
        }
        // 生成25个元素
        $letters = array_fill(0, 26,0);
        for ($i = 0; $i < $sLen; $i++) {
            $letters[ord($s[$i]) - ord('a')] += 1;
            $letters[ord($t[$i]) - ord('a')] -= 1;
        }

        for ($i = 0; $i < 26; $i++) {
            if ($letters[$i] != 0) {
                return false;
            }
        }

        return true;
    }
}

$svc = new IsAnagramSolutionTwo();
$svc->isAnagram('cat', 'rat');