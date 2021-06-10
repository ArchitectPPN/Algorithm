<?php

class Solution
{

    /**
     * @param Integer $x
     * @return Boolean
     */
    function isPalindrome($x): bool
    {

        $len = strlen($x);
        $middle = ceil($len / 2);

        $flag = true;
        for ($i = 0; $i < $middle; $i++) {

            if ($x[$i] != $x[$len - $i - 1]) {
                $flag = false;
                break;
            }
        }

        return $flag;
    }
}

var_dump((new Solution())->isPalindrome('p121p-'));