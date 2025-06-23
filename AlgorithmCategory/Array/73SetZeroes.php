<?php

# 73. 矩阵置零 https://leetcode.cn/problems/set-matrix-zeroes/description

class SetZeroesSolution
{
    /**
     * @param array $matrix
     * @return void
     */
    public function setZeroes(array &$matrix):void
    {
        $rows = count($matrix);
        if ($rows == 0) {
            return;
        }
        $cols = count($matrix[0]);
        $flagRowsZero = $flagColsZero = false;

        // 检查第一行是否需要置零
        for ($j = 0; $j < $cols; $j++) {
            if ($matrix[0][$j] == 0) {
                $flagRowsZero = true;
                break;
            }
        }

        // 检查第一列是否需要置零
        for ($i = 0; $i < $rows; $i++) {
            if ($matrix[$i][0] == 0) {
                $flagColsZero = true;
                break;
            }
        }

        // 从第二行第二列开始标记需要置零的行和列
        for ($i = 1; $i < $rows; $i++) {
            for ($j = 1; $j < $cols; $j++) {
                if ($matrix[$i][$j] == 0) {
                    $matrix[$i][0] = 0;
                    $matrix[0][$j] = 0;
                }
            }
        }

        // 根据第一行和第一列的标记置零
        for ($i = 1; $i < $rows; $i++) {
            for ($j = 1; $j < $cols; $j++) {
                if ($matrix[$i][0] == 0 || $matrix[0][$j] == 0) {
                    $matrix[$i][$j] = 0;
                }
            }
        }

        // 处理第一行
        if ($flagRowsZero) {
            for ($j = 0; $j < $cols; $j++) {
                $matrix[0][$j] = 0;
            }
        }

        // 处理第一列
        if ($flagColsZero) {
            for ($i = 0; $i < $rows; $i++) {
                $matrix[$i][0] = 0;
            }
        }
    }
}

$questions = [
    [
        [1,1,1],
        [1,0,1],
        [1,1,1]
    ],
    [
        [0,1,2,0],
        [3,4,5,2],
        [1,3,1,5]
    ]
];
$svc = new SetZeroesSolution();
foreach ($questions as $question) {
    $svc->setZeroes($question);
    var_dump($question);
}
