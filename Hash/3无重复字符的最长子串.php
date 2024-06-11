<?php

namespace HashLengthOfLongestSubstring;

class Solution {

    /**
     * @param String $s
     * @return int
     */
    function lengthOfLongestSubstring($s): int
    {
        if (empty($s)) return 0;

        $ans = [];
        $map = [];
        $len = strlen($s) - 1;
        for ($i = 0; $i <= $len; $i++) {
            if (isset($map[$i])) {
                $ans = count($map) > count($ans) ? $map : $ans;
                $map = [];
                continue;
            } else {
                $map[$s[$i]] = 1;
                $ans[] = $s[$i];
            }
        }

        return count($ans);
    }
}

$str = "abcabcbb";

$solution = new Solution();
echo $solution->lengthOfLongestSubstring($str);