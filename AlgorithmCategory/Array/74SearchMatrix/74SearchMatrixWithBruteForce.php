<?php

class BruteForceSearchMatrixSolution
{
    /**
     * @param array $matrix
     * @param int $target
     * @return bool
     */
    public function searchMatrix(array $matrix, int $target): bool
    {
        $rows = count($matrix);
        // 先检查矩阵是否为空
        if ($rows === 0) {
            return false;
        }

        // 此时矩阵至少有一行，再获取列数
        $cols = count($matrix[0]);

        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                if ($matrix[$i][$j] == $target) {
                    return true;
                }
            }
        }

        return false;
    }
}

$questions = [
    [
        [
            [1, 2, 3],
            [2, 3, 4],
            [5, 6, 7],
        ],
        9,
    ],
];
$svc = new BruteForceSearchMatrixSolution();
foreach ($questions as $question) {
    $res = $svc->searchMatrix($question[0], $question[1]);
    echo $res ? 'true' : 'false';
}