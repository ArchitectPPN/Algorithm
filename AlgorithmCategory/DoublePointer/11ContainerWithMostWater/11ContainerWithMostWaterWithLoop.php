<?php

namespace DoublePointer;

# 11. 盛最多水的容器 https://leetcode.cn/problems/container-with-most-water/

/**
 * 视频讲解： https://www.bilibili.com/video/BV1Qg411q7ia/?vd_source=fbff21b18c60ea6baf150910c8bd2c70
 */
class MaxAreaSolutionWithLoop
{
    /**
     * @param Integer[] $height
     * @return Integer
     */
    function maxArea(array $height): int
    {
        $maxV = 0;
        $heightLen = count($height);

        for ($i= 0; $i < $heightLen; $i++) {
            for ($j = $i + 1; $j < $heightLen; $j++) {
                $tmpArea = ($j - $i) * min($height[$i], $height[$j]);
                $maxV = max($maxV, $tmpArea);
            }
        }

        return $maxV;
    }
}

$solution = new MaxAreaSolutionWithLoop();
echo $solution->maxArea([1, 8, 6, 2, 5, 4, 8, 3, 7]);