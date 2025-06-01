<?php

# 743. 网络延迟时间 https://leetcode.cn/problems/network-delay-time/description/

class NetworkDelayTimeSolution
{
    function networkDelayTime($times, $n, $k)
    {
        // 构建邻接表
        $adj = [];
        foreach ($times as $time) {
            // 源节点
            $u = $time[0];
            // 目标节点
            $v = $time[1];
            // 距离
            $w = $time[2];

            $adj[$u][] = [$v, $w];
        }

        // 初始化距离数组，初始值为无穷大
        $dist = array_fill(1, $n, INF);
        // 源节点到自身的距离为0
        $dist[$k] = 0;

        // 优先队列（最大堆，但是代码这里使用取负数的方式巧妙的转化了），存储 [距离, 节点]
        $heap = new SplPriorityQueue();
        //  设置仅输出 value
        $heap->setExtractFlags(SplPriorityQueue::EXTR_DATA);
        $heap->insert([0, $k], 0);

        while (!$heap->isEmpty()) {
            // 出队
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
                    // 松弛操作, 当前已记录的距离大于 上一个节点+当前节点的距离，更新当前记录的距离
                    if ($dist[$v] > $dist[$node] + $w) {
                        $dist[$v] = $dist[$node] + $w;
                        // 取负使最小堆生效，例如：
                        // 假设有一组数据: 7 8 9
                        // 原本的顺序为：9 8 7
                        // 经过取负数，现在的顺序为：-7 -8 -9
                        $heap->insert([$dist[$v], $v], -$dist[$v]);
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