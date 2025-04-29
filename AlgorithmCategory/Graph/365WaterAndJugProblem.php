<?php

# 365. 水壶问题 https://leetcode.cn/problems/water-and-jug-problem/

class canMeasureWaterSolution
{
    // 定义 seen 属性，用于存储已经访问过的状态
    private array $seen = [];

    public function canMeasureWater($x, $y, $z): bool
    {
        $stack = [[0, 0]];
        while (!empty($stack)) {
            [$remainX, $remainY] = array_pop($stack);
            if ($remainX == $z || $remainY == $z || $remainX + $remainY == $z) {
                return true;
            }
            $key = $remainX . ',' . $remainY;
            if (isset($this->seen[$key])) {
                continue;
            }
            $this->seen[$key] = 1;
            // 把 X 壶灌满。
            $stack[] = [$x, $remainY];
            // 把 Y 壶灌满。
            $stack[] = [$remainX, $y];
            // 把 X 壶倒空。
            $stack[] = [0, $remainY];
            // 把 Y 壶倒空。
            $stack[] = [$remainX, 0];
            // 把 X 壶的水灌进 Y 壶，直至灌满或倒空。
            $stack[] = [
                $remainX - min($remainX, $y - $remainY),
                $remainY + min($remainX, $y - $remainY),
            ];
            // 把 Y 壶的水灌进 X 壶，直至灌满或倒空。
            $stack[] = [
                $remainX + min($remainY, $x - $remainX),
                $remainY - min($remainY, $x - $remainX),
            ];
        }
        return false;
    }
}

$questions = [
    [3, 5, 4]
];
foreach ($questions as $question) {
    $svc = new canMeasureWaterSolution();
    $res = $svc->canMeasureWater($question[0], $question[1], $question[2]);
    var_dump($res);
}