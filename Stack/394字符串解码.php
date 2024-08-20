<?php

namespace DecodeStringWithStackSolution;

class Solution
{
    /**
     * @param String $s
     * @return String
     */
    function decodeString(string $s): string
    {
        $stack = [];
        $num = 0;
        $str = '';

        for ($i = 0; $i < strlen($s); $i++) {
            $c = $s[$i];

            if (is_numeric($c)) {
                $num = $num * 10 + intval($c);
            } elseif ($c === '[') {
                $stack[] = [
                    $str,
                    $num,
                ];
                $str = '';
                $num = 0;
            } elseif ($c === ']') {
                [
                    $prevStr,
                    $multiplier,
                ] = array_pop($stack);
                $str = $prevStr . str_repeat($str, $multiplier);
            } else { // 字母
                $str .= $c;
            }
        }

        return $str;
    }
}

$solution = new Solution();
$answer = $solution->decodeString("100[a]");

echo $answer . PHP_EOL;