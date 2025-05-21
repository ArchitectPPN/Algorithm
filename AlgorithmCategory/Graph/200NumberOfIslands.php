<?php

# 200. 岛屿数量 https://leetcode.cn/problems/number-of-islands/description/

class NumIslandsSolutionWithDFS
{
    /** @var array Islands */
    private array $grid = [];

    /** @var int rows numbers */
    private int $nr = 0;

    /** @var int col numbers */
    private int $nc = 0;

    /**
     * @param int $row
     * @param int $col
     * @return void
     */
    private function dfs(int $row, int $col): void
    {
        // set visited
        $this->grid[$row][$col] = '0';
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
     * @param array $grid
     * @return int
     */
    public function numIslands(array $grid): int
    {
        $this->initData($grid);
        // empty grid
        if ($this->nr == 0) {
            return 0;
        }

        // 岛屿的数量
        $numberOfIslands = 0;

        for ($row = 0; $row < $this->nr; $row++) {
            for ($col = 0; $col < $this->nc; $col++) {
                // island
                if ($this->grid[$row][$col] == '1') {
                    $numberOfIslands++;
                    $this->dfs($row, $col);
                }
            }
        }

        return $numberOfIslands;
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

$grid = [
    ["1", "1", "0", "0", "0",],
    ["1", "1", "0", "0", "0",],
    ["0", "0", "1", "0", "0",],
    ["0", "0", "0", "1", "1",],
];

$svc = new NumIslandsSolutionWithDFS();
echo $svc->numIslands($grid) . PHP_EOL;

class NumIslandsSolutionReviewOne
{
    /** @var array Islands */
    private array $grid = [];

    /** @var int rows numbers */
    private int $nr = 0;

    /** @var int col numbers */
    private int $nc = 0;

    /**
     * @param int $row
     * @param int $col
     * @return void
     */
    private function dfs(int $row, int $col): void
    {
        // 设置 visited
        $this->grid[$row][$col] = '0';

        // up
        if ($row - 1 >= 0 && $this->grid[$row - 1][$col] == '1') {
            $this->dfs($row - 1, $col);
        }

        // down
        if ($row + 1 < $this->nr && $this->grid[$row + 1][$col] == '1') {
            $this->dfs($row + 1, $col);
        }

        // right
        if ($col + 1 < $this->nc && $this->grid[$row][$col + 1] == '1') {
            $this->dfs($row, $col + 1);
        }

        // left
        if ($col - 1 >= $this->nc && $this->grid[$row][$col - 1] == '1') {
            $this->dfs($row, $col - 1);
        }
    }

    /**
     * @param array $grid
     * @return int
     */
    public function numIslands(array $grid): int
    {
        // 初始化数据
        $this->initData($grid);

        // 输入为空, 直接返回
        if ($this->nr == 0) {
            return 0;
        }

        $numberOfIslands = 0;

        for ($row = 0; $row < $this->nr; $row++) {
            for ($col = 0; $col < $this->nc; $col++) {
                if ($this->grid[$row][$col] == '1') {
                    $numberOfIslands++;
                    $this->dfs($row, $col);
                }
            }
        }

        return $numberOfIslands;
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

$grid = [
    ["1", "1", "0", "0", "0",],
    ["1", "1", "0", "0", "0",],
    ["0", "0", "1", "0", "0",],
    ["0", "0", "0", "1", "1",],
];

$svc = new NumIslandsSolutionReviewOne();
echo $svc->numIslands($grid) . PHP_EOL;