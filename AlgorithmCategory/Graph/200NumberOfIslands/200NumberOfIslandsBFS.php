<?php

# 200. 岛屿数量 https://leetcode.cn/problems/number-of-islands/description/

$grid = [
    ["1", "1", "0", "0", "0",],
    ["1", "1", "0", "0", "0",],
    ["0", "0", "1", "0", "0",],
    ["0", "0", "0", "1", "1",],
];

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