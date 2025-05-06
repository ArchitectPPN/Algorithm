<?php

# 695. 岛屿的最大面积 https://leetcode.cn/problems/max-area-of-island/

class MaxAreaOfIslandSolution
{

    /** @var array Islands */
    private array $grid = [];

    /** @var int rows numbers */
    private int $nr = 0;

    /** @var int col numbers */
    private int $nc = 0;

    /** @var int 最大面积 */
    private int $maxArea = 0;

    /** @var int 当前面积 */
    private int $nowArea = 0;

    /**
     * @param int $row
     * @param int $col
     * @return void
     */
    private function dfs(int $row, int $col): void
    {
        // set visited
        $this->grid[$row][$col] = '0';
        $this->nowArea++;

        // up
        if ($row - 1 >= 0 && $this->grid[$row - 1][$col] == '1') {
            $this->dfs($row - 1, $col);
        }
        // down
        if ($row + 1 < $this->nr && $this->grid[$row + 1][$col] == '1') {
            $this->dfs($row + 1, $col);
        }
        // left
        if ($col - 1 >= 0 && $this->grid[$row][$col - 1] == '1') {
            $this->dfs($row, $col - 1);
        }
        // right
        if ($col + 1 < $this->nc && $this->grid[$row][$col + 1] == '1') {
            $this->dfs($row, $col + 1);
        }
    }

    /**
     * @param Integer[][] $grid
     * @return Integer
     */
    function maxAreaOfIsland(array $grid): int
    {
        $this->initData($grid);
        // empty grid
        if ($this->nr == 0) {
            return $this->maxArea;
        }

        for ($row = 0; $row < $this->nr; $row++) {
            for ($col = 0; $col < $this->nc; $col++) {
                // island
                if ($this->grid[$row][$col] == '1') {
                    $this->dfs($row, $col);
                    $this->maxArea = max($this->maxArea, $this->nowArea);
                    $this->nowArea = 0;
                }
            }
        }

        return $this->maxArea;
    }


    /**
     * 初始化数据
     * @param array $grid
     * @return void
     */
    private function initData(array $grid): void
    {
        $this->grid = $grid;
        $this->nr = count($grid);
        $this->nc = count($grid[0]);
    }
}

$questions = [
    [
        [0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
        [0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0],
        [0, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0],
        [0, 1, 0, 0, 1, 1, 0, 0, 1, 0, 1, 0, 0],
        [0, 1, 0, 0, 1, 1, 0, 0, 1, 1, 1, 0, 0],
        [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0],
        [0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0],
        [0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0]
    ]
];

foreach ($questions as $question) {
    $svc = new MaxAreaOfIslandSolution();
    $max = $svc->maxAreaOfIsland($question);
    echo $max . PHP_EOL;
}
