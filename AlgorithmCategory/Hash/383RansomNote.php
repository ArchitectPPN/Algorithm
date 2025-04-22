<?php

# 383. 赎金信 https://leetcode.cn/problems/ransom-note/

class CanConstructSolution
{

    /**
     * @param string $ransomNote
     * @param string $magazine
     * @return bool
     */
    function canConstruct(string $ransomNote, string $magazine): bool
    {
        $rnLen = strlen($ransomNote);
        $mLen = strlen($magazine);
        if ($rnLen > $mLen) {
            return false;
        }

        $ht = array_fill(0, 25, 0);
        for ($i = 0; $i < $mLen; $i++) {
            $ht[ord($magazine[$i]) - ord('a')] += 1;
        }

        for ($i = 0; $i < $rnLen; $i++) {
            $ht[ord($ransomNote[$i]) - ord('a')] -= 1;
            if ($ht[ord($ransomNote[$i]) - ord('a')] < 0) {
                return false;
            }
        }

        return true;
    }
}

$svc = new CanConstructSolution();
$svc->canConstruct("aa", "aab");