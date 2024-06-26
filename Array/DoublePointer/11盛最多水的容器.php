<?php

namespace MaxArea;


/**
 * 视频讲解： https://www.bilibili.com/video/BV1Qg411q7ia/?vd_source=fbff21b18c60ea6baf150910c8bd2c70
 */
class MaxAreaSolution
{
    /**
     * @param Integer[] $height
     * @return Integer
     */
    function maxArea(array $height): int
    {
        $maxV = 0;
        $left = 0;
        $right = count($height) - 1;

        while ($left < $right) {
            $tmp = ($right - $left) * min($height[$left], $height[$right]);
            $maxV = max($maxV, $tmp);
            if ($height[$left] < $height[$right]) {
                $left++;
            } else {
                $right--;
            }
        }

        return $maxV;
    }
}

$solution = new MaxAreaSolution();
echo $solution->maxArea([1, 8, 6, 2, 5, 4, 8, 3, 7]);