<?php

# 994. 腐烂的橘子 https://leetcode.cn/problems/rotting-oranges/

class OrangesRottingSolution
{
    /** @var int[] X轴方向 */
    private array $dir_x = [0, 1, 0, -1];
    /** @var int[] Y轴方向 */
    private array $dir_y = [1, 0, -1, 0];

    public function orangesRotting($grid)
    {
        // 初始化距离数组（用二维数组模拟，-1表示未访问）
        $dis = array_fill(0, 10, array_fill(0, 10, -1));
        // 初始化新鲜橘子数量
        $cnt = 0;
        // 初始化行数
        $n = count($grid);
        if ($n === 0) {
            return -1;
        }
        // 初始化每行多少个元素，也就是列数
        $m = count($grid[0]);
        // 初始答案
        $ans = 0;
        $queue = new SplQueue();

        // 遍历网格，初始化队列和统计新鲜橘子
        for ($i = 0; $i < $n; ++$i) {
            for ($j = 0; $j < $m; ++$j) {
                if ($grid[$i][$j] === 2) {
                    // 烂橘子入队
                    $queue->enqueue([$i, $j]);
                    // 腐烂橘子初始时间为0
                    $dis[$i][$j] = 0;
                } elseif ($grid[$i][$j] === 1) {
                    // 统计新鲜橘子数量
                    $cnt++;
                }
            }
        }

        // BFS处理腐烂过程
        while (!$queue->isEmpty()) {
            $front = $queue->dequeue();
            $r = $front[0];
            $c = $front[1];

            // 遍历四个方向
            for ($i = 0; $i < 4; ++$i) {
                $tx = $r + $this->dir_x[$i];
                $ty = $c + $this->dir_y[$i];

                // 检查边界、已访问状态和橘子类型
                if (
                    $tx < 0
                    || $tx >= $n
                    || $ty < 0
                    || $ty >= $m
                    || $dis[$tx][$ty] !== -1
                    || $grid[$tx][$ty] === 0
                ) {
                    continue;
                }

                // 更新距离并加入队列
                $dis[$tx][$ty] = $dis[$r][$c] + 1;
                $queue->enqueue([$tx, $ty]);

                // 处理新鲜橘子腐烂
                if ($grid[$tx][$ty] === 1) {
                    $cnt--;
                    // 记录当前腐烂时间
                    $ans = $dis[$tx][$ty];
                    // 提前终止如果所有橘子已腐烂
                    if ($cnt === 0) {
                        // 跳出两层循环（for和while）
                        break 2;
                    }
                }
            }
        }

        // 判断是否有剩余新鲜橘子
        return $cnt === 0 ? $ans : -1;
    }
}

$svc = new OrangesRottingSolution();

$questions = [
    [
        [2, 1, 1],
        [1, 1, 0],
        [0, 1, 1]
    ]
];

foreach ($questions as $question) {
    $ans = $svc->orangesRotting($question);
    echo $ans . PHP_EOL;
}