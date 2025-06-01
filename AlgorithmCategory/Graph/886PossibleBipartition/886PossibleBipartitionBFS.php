<?php

# 886PossibleBipartition. 可能的二分法 https://leetcode.cn/problems/possible-bipartition/

class PossibleBiPartitionSolutionWithBFS
{
    /**
     * @param int $n
     * @param array $dislikes
     * @return bool
     */
    public function possibleBipartition(int $n, array $dislikes): bool
    {
        // 构建邻接表（0-based索引）
        $g = array_fill(0, $n, []);
        foreach ($dislikes as $pair) {
            $x = $pair[0] - 1;
            $y = $pair[1] - 1;
            $g[$x][] = $y;
            $g[$y][] = $x;
        }

        // 颜色数组：0=未染色，1和-1表示两种颜色
        $color = array_fill(0, $n, 0);

        // 使用队列进行BFS
        $queue = new SplQueue();

        for ($i = 0; $i < $n; $i++) {
            // 未染色的节点，启动BFS染色
            if ($color[$i] === 0) {
                $color[$i] = 1;
                $queue->enqueue($i);

                while (!$queue->isEmpty()) {
                    $x = $queue->dequeue();

                    foreach ($g[$x] as $y) {
                        // 相邻节点颜色相同，无法二分
                        if ($color[$y] === $color[$x]) {
                            return false;
                        }

                        // 未染色，染成相反颜色并加入队列
                        if ($color[$y] === 0) {
                            $color[$y] = -$color[$x];
                            $queue->enqueue($y);
                        }
                    }
                }
            }
        }

        // 所有节点染色成功，是二分图
        return true;
    }
}
$svc = new PossibleBiPartitionSolutionWithBFS();
$svc->possibleBipartition(4, [[1, 2], [2, 1], [2, 4]]);