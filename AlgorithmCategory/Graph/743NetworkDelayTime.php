<?php

# 743. 网络延迟时间 https://leetcode.cn/problems/network-delay-time/description/

/**
 * LeetCode 743. 网络延迟时间的题目内容如下：
 * 有 n 个网络节点，标记为 1 到 n。
 * 给定一个列表 times，表示信号经过有向边的传递时间。times[i] = (ui, vi, wi)，其中 ui 是源节点，vi 是目标节点，wi 是一个信号从源节点传递到目标节点的时间。
 * 现在，从某个节点 K 发出一个信号。需要多久才能使所有节点都收到信号？如果不能使所有节点收到信号，返回 -1。
 * 同时，题目还给出了一些限制条件：
 * n 的范围在 (1, 100) 之间。
 * K 的范围在 (1, n) 之间。
 * times 的长度在 (1, 6000) 之间。
 * 所有的边 times[i] = (ui, vi, wi) 都有 1 <= ui, vi <= n 且 0 <= wi <= 100。
 */
class NetworkDelayTimeSolution
{
    function networkDelayTime($times, $n, $k)
    {
        // 构建邻接表
        $adj = [];
        foreach ($times as $time) {
            $u = $time[0];
            $v = $time[1];
            $w = $time[2];
            $adj[$u][] = [$v, $w];
        }

        // 初始化距离数组，初始值为无穷大
        $dist = array_fill(1, $n, INF);
        // 源节点到自身的距离为0
        $dist[$k] = 0;

        // 优先队列（最小堆），存储 [距离, 节点]
        $heap = new SplPriorityQueue();
        $heap->setExtractFlags(SplPriorityQueue::EXTR_DATA);
        $heap->insert([0, $k], 0);

        while (!$heap->isEmpty()) {
            $current = $heap->extract();
            $d = $current[0];
            $node = $current[1];

            // 如果当前距离大于已记录的最短距离，跳过
            if ($d > $dist[$node]) {
                continue;
            }

            // 遍历所有邻居
            if (isset($adj[$node])) {
                foreach ($adj[$node] as $neighbor) {
                    $v = $neighbor[0];
                    $w = $neighbor[1];
                    // 松弛操作, 当前已记录的距离大于 上一个节点+当前节点的距离
                    if ($dist[$v] > $dist[$node] + $w) {
                        $dist[$v] = $dist[$node] + $w;
                        $heap->insert([$dist[$v], $v], -$dist[$v]); // 取负使最小堆生效
                    }
                }
            }
        }

        // 找出最大延迟
        $maxTime = max($dist);
        return $maxTime === INF ? -1 : $maxTime;
    }
}

$questions = [
    [
        'n' => 5,
        'times' => [[1, 2, 1], [2, 3, 2], [1, 3, 4], [2, 4, 1], [3, 5, 1]],
        'k' => 1,
        'expect' => 4,
    ],
];

$svc = new NetworkDelayTimeSolution();
foreach ($questions as $question) {
    $res = $svc->networkDelayTime($question['times'], $question['n'], $question['k']);
    if ($res != $question['expect']) {
        var_dump('出错');
    } else {
        var_dump($res . ': 正确');
    }
}