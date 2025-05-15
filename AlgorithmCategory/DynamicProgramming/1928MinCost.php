<?php

# 1928. 规定时间内到达终点的最小花费 https://leetcode.cn/problems/minimum-cost-to-reach-destination-in-time/description/

class minCostSolution
{
    // 极大值，使用 PHP_INT_MAX 的一半避免溢出问题
    const INFTY = PHP_INT_MAX / 2;

    /**
     * @param int $maxTime 最大时间
     * @param array $edges 边列表，每个元素为 [起点, 终点, 时间消耗]
     * @param array $passingFees 费用列表，每个元素为起点的通行费
     * @return int
     */
    public function minCost(int $maxTime, array $edges, array $passingFees): int
    {
        $n = count($passingFees);
        // 初始化动态规划数组，f[t][i] 表示花费 t 时间到达节点 i 的最小总费用
        $f = array_fill(0, $maxTime + 1, array_fill(0, $n, self::INFTY));
        // 初始状态：时间为 0 时到达起点 0，费用为起点的通行费
        $f[0][0] = $passingFees[0];

        // 遍历所有可能的时间（从 1 到 maxTime）
        for ($t = 1; $t <= $maxTime; ++$t) {
            // 遍历所有边（无向边，双向处理）
            foreach ($edges as $edge) {
                $start = $edge[0];
                $end = $edge[1];
                $cost = $edge[2]; // 边的时间消耗

                // 如果当前时间 t 足够支付该边的时间消耗
                if ($cost <= $t) {
                    // 从节点 j 经过该边到达节点 i 的情况
                    $candidateI = $f[$t - $cost][$end] + $passingFees[$start];
                    if ($candidateI < $f[$t][$start]) {
                        $f[$t][$start] = $candidateI;
                    }
                    // 从节点 i 经过该边到达节点 j 的情况
                    $candidateJ = $f[$t - $cost][$start] + $passingFees[$end];
                    if ($candidateJ < $f[$t][$end]) {
                        $f[$t][$end] = $candidateJ;
                    }
                }
            }
        }

        // 查找所有时间下到达终点（节点 n-1）的最小费用
        $ans = self::INFTY;
        for ($t = 1; $t <= $maxTime; ++$t) {
            if ($f[$t][$n - 1] < $ans) {
                $ans = $f[$t][$n - 1];
            }
        }

        // 如果仍为极大值，说明无法到达终点
        return $ans === self::INFTY ? -1 : $ans;
    }
}

$maxTime = 29;
$edges = [[0, 1, 10], [1, 2, 10], [0, 2, 15]];
$passingFees = [5, 5, 2];
$svc = new minCostSolution();
$svc->minCost($maxTime, $edges, $passingFees);