<?php

# 207. 课程表 https://leetcode.cn/problems/course-schedule/

class CanFinishSolution
{
    // 存储图的边信息, $edges[i] 表示从节点 i 出发的边的目标节点列表
    private array $edges = [];
    // 存储每个节点的入度，$indeg[$i] 表示节点 i 的入度
    private array $indeg = [];

    /**
     * 是否能完成课程
     * @param int $numCourses
     * @param array $prerequisites
     * @return bool
     */
    public function canFinish(int $numCourses, array $prerequisites): bool
    {
        // 调整 $edges 数组的大小以适应课程数量
        for ($i = 0; $i < $numCourses; $i++) {
            $this->edges[$i] = [];
        }
        // 初始化每个节点的入度为 0
        $this->indeg = array_fill(0, $numCourses, 0);

        // 遍历先决条件列表，构建图的边信息和节点入度
        foreach ($prerequisites as $info) {
            // 学完 $info[1] 课程后，才能学 $info[0] 课程
            $this->edges[$info[1]][] = $info[0];
            // 目标节点 $info[0] 的入度加 1, 也就是 $info[0] 需要先完成几门课程
            $this->indeg[$info[0]]++;
        }

        // 创建一个队列用于存储入度为 0 的节点
        $q = new SplQueue();

        // 将所有入度为 0 的节点加入队列
        for ($i = 0; $i < $numCourses; $i++) {
            if ($this->indeg[$i] == 0) {
                $q->enqueue($i);
            }
        }

        // 记录已访问的节点数量
        $visited = 0;

        // 进行拓扑排序
        while (!$q->isEmpty()) {
            $visited++;
            // 取出队列头部的节点
            $u = $q->dequeue();
            // 遍历从节点 u 出发的所有边
            foreach ($this->edges[$u] as $v) {
                // 目标节点 v 的入度减 1
                $this->indeg[$v]--;
                // 如果目标节点 v 的入度变为 0，则将其加入队列
                if ($this->indeg[$v] == 0) {
                    $q->enqueue($v);
                }
            }
        }

        // 如果已访问的节点数量等于课程总数，则可以完成所有课程，返回 true；否则返回 false
        return $visited == $numCourses;
    }
}

$questions = [
    [2, [[1, 0], [0, 1]]],
    [2, [[1, 0]]],
];

$svc = new CanFinishSolution();
foreach ($questions as $question) {
    $res = $svc->canFinish($question[0], $question[1]);
    var_dump($res);
}
