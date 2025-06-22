<?php
class RotateSolution {

    /**
     * @param Integer[] $nums
     * @param Integer $k
     * @return array
     */
    function rotate(array &$nums, int $k): array
    {
        $len = count($nums);

        $copyNums = $nums;
        for($i = 0; $i < $len; $i++) {
            $index = ($i + $k) % $len;
            $copyNums[$index] = $nums[$i];
        }

        $nums = $copyNums;
        return $nums;
    }
}

$nums = [1, 2, 3, 4, 5, 6];
$svc = new RotateSolution();
$svc->rotate($nums, 3);