<?php
# 438. 找到字符串中所有字母异位词 https://leetcode.cn/problems/find-all-anagrams-in-a-string/description/

class FindAnagramsSolution
{
    /**
     * @param String $s
     * @param String $p
     * @return Integer[]
     */
    function findAnagrams(string $s, string $p): array
    {
        // 获取两个字符串的长度
        $sLen = strlen($s);
        $pLen = strlen($p);

        // 字符串s长度小于字符串p的长度, 说明s无法组成p, 返回空数组
        if ($sLen < $pLen) {
            return [];
        }

        // 定义答案数据
        $ans = [];
        $sCount = array_fill(0, 26, 0);
        $pCount = array_fill(0, 26, 0);

        // 初始化窗口数据, ord($s[$i]) - 97 将字符转成ASCII码, 然后减去97, 得到索引
        // 初始化第一个窗口数据
        for ($i = 0; $i < $pLen; $i++) {
            $sCount[ord($s[$i]) - 97]++;
            $pCount[ord($p[$i]) - 97]++;
        }

        // 检查第一个窗口
        if ($sCount === $pCount) {
            $ans[] = 0;
        }

        // 滑动窗口遍历
        for ($i = 0; $i < $sLen - $pLen; $i++) {
            // 移出窗口左边界字符
            $sCount[ord($s[$i]) - 97]--;
            // 移入窗口右边界字符
            $sCount[ord($s[$i + $pLen]) - 97]++;

            // 检查当前窗口是否为异位词
            if ($sCount === $pCount) {
                $ans[] = $i + 1;
            }
        }

        return $ans;
    }
}

$svc = new FindAnagramsSolution();
$ans = $svc->findAnagrams('abcabjkabc', 'abc');
var_dump($ans);