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

class NumIslandsSolutionWithBFS
{
    /**
     * @param array $grid
     * @return int
     */
    public function numIslands(array $grid): int
    {
        // 计算行数
        $rowsNum = count($grid);
        if ($rowsNum == 0) {
            return 0;
        }
        // 计算列数
        $columnsNum = count($grid[0]);
        // 初始化答案
        $ans = 0;
        // 初始化队列
        $queue = new SplQueue();
        // 初始化方向
        $dirX = [0, 1, 0, -1];
        $dirY = [1, 0, -1, 0];
        // 初始化访问列表
        $visited = [];

        for ($i = 0; $i < $rowsNum; $i++) {
            for ($j = 0; $j < $columnsNum; $j++) {
                if ($grid[$i][$j] === '1' && !isset($visited[$i][$j])) {
                    // 设置已访问
                    $visited[$i][$j] = 1;
                    // 岛屿数量+1
                    $ans++;
                    $queue->enqueue([$i, $j]);
                    while (!$queue->isEmpty()) {
                        $tmp = $queue->dequeue();
                        $x = $tmp[0];
                        $y = $tmp[1];

                        for ($dir = 0; $dir < 4; $dir++) {
                            $nx = $dirX[$dir] + $x;
                            $ny = $dirY[$dir] + $y;
                            if (
                                $nx < 0
                                || $nx >= $rowsNum
                                || $ny < 0
                                || $ny >= $columnsNum
                                || $grid[$nx][$ny] !== '1'
                                || isset($visited[$nx][$ny])
                            ) {
                                continue;
                            }
                            // 设置已访问
                            $visited[$nx][$ny] = 1;
                            $queue->enqueue([$nx, $ny]);
                        }
                    }
                }
            }
        }

        return $ans;
    }
}

$svc = new NumIslandsSolutionWithBFS();
echo $svc->numIslands($grid) . PHP_EOL;