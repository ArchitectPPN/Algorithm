<?php

namespace BinarySearch\FindFirstMaxOrMin;

class Solution {
    /**
     * @param String[] $letters
     * @param String $target
     * @return String
     */
    function nextGreatestLetter(array $letters,string $target): string
    {
        $arrLength = count($letters) - 1;
        if ($target >= $letters[$arrLength]) {
            return $letters[0];
        }

        $left = 0;
        while ($left < $arrLength) {
            $mid = $left + intdiv($arrLength - $left, 2);
            if ($letters[$mid] > $target) {
                $arrLength = $mid;
            } else {
                $left = $mid + 1;
            }
        }

        return $letters[$left];
    }
}

$letters = ["c","f","j"];
$target = 'a';
$solution = new \BinarySearch\FindFirstMaxOrMin\Solution();
echo $solution->nextGreatestLetter($letters, $target) . PHP_EOL;
