<?php

class SearchMatrixWithRightOfFind
{
    /**
     * @param array $matrix
     * @param int $target
     * @return bool
     */
    public function searchMatrix(array $matrix, int $target): bool
    {
        $rows = count($matrix);
        if ($rows == 0) {
            return false;
        }
        $cols = count($matrix[0]);
        $x = 0;
        $y = $cols - 1;
        while ($x < $rows && $y >= 0) {
            if ($matrix[$x][$y] == $target) {
                return true;
            } elseif ($matrix[$x][$y] > $target) {
                $y--;
            } else {
                $x++;
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
        2,
    ],
];
$svc = new SearchMatrixWithRightOfFind();
foreach ($questions as $question) {
    $res = $svc->searchMatrix($question[0], $question[1]);
    echo $res ? 'true' : 'false';
}