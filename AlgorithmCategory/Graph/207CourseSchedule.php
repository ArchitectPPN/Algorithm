<?php

# 207. 课程表 https://leetcode.cn/problems/course-schedule/

class BFSCanFinishSolution
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

$svc = new BFSCanFinishSolution();
foreach ($questions as $question) {
    $res = $svc->canFinish($question[0], $question[1]);
    var_dump($res);
}


$questions = [
    [2, [[1, 0], [0, 1]]],
    [2, [[1, 0]]],
];
class DFSCanFinishSolution
{
    /** @var array 存储图的边信息，$edges[$i] 表示从节点 i 出发的边的目标节点列表 */
    private array $edges = [];
    /** @var array 存储节点的访问状态，0 表示未访问，1 表示正在访问（在当前递归路径中），2 表示已访问过且不在当前递归路径中 */
    private array $visited = [];
    /** @var bool 用于标记是否存在环，初始为 true，若发现环则设为 false */
    private bool $valid = true;

    /**
     * 深度优先搜索方法
     * @param int $u 节点编号
     * @return void
     */
    private function dfs(int $u): void
    {
        // 将节点 u 标记为正在访问
        $this->visited[$u] = 1;
        // 遍历从节点 u 出发的所有边的目标节点 v
        foreach ($this->edges[$u] as $v) {
            // 如果节点 v 未被访问
            if ($this->visited[$v] === 0) {
                // 对节点 v 进行深度优先搜索
                $this->dfs($v);
                // 如果已经发现存在环，直接返回
                if (!$this->valid) {
                    return;
                }
            } elseif ($this->visited[$v] === 1) {
                // 如果节点 v 正在被访问（在当前递归路径中），说明存在环
                $this->valid = false;
                return;
            }
        }
        // 将节点 u 标记为已访问过且不在当前递归路径中
        $this->visited[$u] = 2;
    }

    /**
     * 判断是否可以完成所有课程的方法
     * @param int $numCourses 课程数量
     * @param array $prerequisites 课程先决条件列表
     * @return bool 如果可以完成所有课程返回 true，否则返回 false
     */
    public function canFinish(int $numCourses, array $prerequisites): bool
    {
        // 输入验证：检查先决条件列表中的元素是否为长度为 2 的数组
        foreach ($prerequisites as $info) {
            if (!is_array($info) || count($info) !== 2) {
                throw new InvalidArgumentException("每个先决条件必须是长度为 2 的数组");
            }
        }

        // 调整 $edges 数组的大小以适应课程数量
        for ($i = 0; $i < $numCourses; $i++) {
            $this->edges[$i] = [];
        }
        // 初始化每个节点的访问状态为未访问（0）
        $this->visited = array_fill(0, $numCourses, 0);

        // 遍历先决条件列表，构建图的边信息
        // 对于每个先决条件 [a, b]，表示学习课程 a 前必须先学习课程 b
        // 所以从节点 b 出发有一条指向节点 a 的边
        foreach ($prerequisites as $info) {
            $this->edges[$info[1]][] = $info[0];
        }

        // 对每个未访问的节点进行深度优先搜索
        for ($i = 0; $i < $numCourses && $this->valid; $i++) {
            if ($this->visited[$i] === 0) {
                $this->dfs($i);
            }
        }

        // 如果不存在环，返回 true；否则返回 false
        return $this->valid;
    }
}
foreach ($questions as $question) {
    $svc = new DFSCanFinishSolution();
    $res = $svc->canFinish($question[0], $question[1]);
    var_dump($res);
}