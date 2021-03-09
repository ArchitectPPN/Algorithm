<?php

class Solution
{
    /**
     * @param $arr
     * @param Integer $target
     * @return Integer[]
     */
    function twoSum($arr, $target)
    {
        $loop = count($arr);
        for ($i = 0; $i < $loop; $i++) {
            //copy 一份$arr
            $arr2 = $arr;
            unset($arr2[$i]);
            $leftNum = $target - $arr[$i];
            if (in_array($leftNum, $arr2)) {
                return [$i, array_search($leftNum, $arr2)];
            }
        }
    }
}


class Solution2
{

    /**
     * @param Integer[] $nums
     * @param Integer $target
     * @return Integer[]
     */
    function twoSum($nums, $target)
    {
        for ($i = 0; $i < count($nums); $i++) {
            for ($j = $i + 1; $j < count($nums); $j++) {
                $sum = $nums[$i] + $nums[$j];
                if ($sum === $target) {
                    return [$i, $j];
                }
            }
        }

        return [];
    }
}