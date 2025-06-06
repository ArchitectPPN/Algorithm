<?php

# 797. 所有可能的路径 https://leetcode.cn/problems/all-paths-from-source-to-target/description/


class AllPathsSourceTargetSolution
{
    /**
     * @param array $graph
     * @return array
     */
    function allPathsSourceTarget($graph): array
    {
        $n = count($graph);
        $target = $n - 1;
        $result = [];
        $queue = [[0]]; // 初始路径是 [0]

        while (!empty($queue)) {
            $path = array_shift($queue); // 取出队列头部路径
            $currentNode = end($path);   // 获取当前节点

            // 到达终点则记录结果
            if ($currentNode == $target) {
                $result[] = $path;
                continue;
            }

            // 遍历当前节点的邻居
            foreach ($graph[$currentNode] as $neighbor) {
                $newPath = $path;        // 复制原路径
                $newPath[] = $neighbor; // 添加新节点
                $queue[] = $newPath;    // 入队
            }
        }

        return $result;
    }
}

