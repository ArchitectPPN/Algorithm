<?php

# 797. 所有可能的路径 https://leetcode.cn/problems/all-paths-from-source-to-target/description/


class AllPathsSourceTargetSolutionWithBFS
{
    /**
     * 所有可能的路径
     * @param array $graph
     * @return array
     */
    function allPathsSourceTarget(array $graph): array
    {
        $n = count($graph);
        $target = $n - 1;
        $result = [];
        // 初始路径是 [0]
        $queue = [[0]];

        while (!empty($queue)) {
            // 取出队列头部路径
            $path = array_shift($queue);
            // 获取当前路径的最后一个节点
            $currentNode = end($path);

            // 如果路径的最后一个节点已经到达目标节点，就把答案放入最终答案
            if ($currentNode == $target) {
                $result[] = $path;
                continue;
            }

            /**
             * 把节点能到达的所有节点拼接到当前路径最后
             * 当前节点能达到5个节点，那就会产生5个新的路径
             */
            foreach ($graph[$currentNode] as $neighbor) {
                // 复制原路径
                $newPath = $path;
                // 添加新节点
                $newPath[] = $neighbor;
                // 放到队尾，是一个队列，先进先出
                $queue[] = $newPath;
            }
        }

        return $result;
    }
}

$svc = new AllPathsSourceTargetSolutionWithBFS();

$questions = [
    [[1, 2], [3], [3], []]
];
foreach ($questions as $question) {
    $svc->allPathsSourceTarget($question);
}
