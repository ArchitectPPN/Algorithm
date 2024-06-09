<?php

namespace FirstBadVersion;

class Solution {
    /**
     * 返回第一个错误的版本
     *
     * @param int $n
     * @return int
     */
    function firstBadVersion(int $n): int
    {
        // 因为版本是从1开始的
        $left = 1;
        $right = $n;

        while($left < $right) {
            $mid = intdiv($right - $left, 2) + $left;
            if ($this->isBadVersion($mid)) {
                $right = $mid;
            } else {
                $left = $mid + 1;
            }
        }

       return $left;
    }

    /**
     * 检查是否
     *
     * @param $version
     * @return true
     */
    public function isBadVersion($version): bool
    {
        return true;
    }
}