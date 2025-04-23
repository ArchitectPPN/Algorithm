<?php

# 1584. 连接所有点的最小费用 https://leetcode.cn/problems/min-cost-to-connect-all-points/

// 定义并查集类，用于处理集合的合并和查找操作
class DisjointSetUnion
{
    // 存储每个元素的父节点
    private array $f = [];
    // 存储每个集合的秩（可理解为树的高度），用于优化合并操作
    private array $rank = [];
    // 元素的总数
    private int $n;

    // 构造函数，初始化并查集
    public function __construct($n)
    {
        // 保存元素总数
        $this->n = $n;
        // 遍历每个元素
        for ($i = 0; $i < $this->n; $i++) {
            // 初始时，每个元素的秩设为 1
            $this->rank[$i] = 1;
            // 初始时，每个元素的父节点是它自身
            $this->f[$i] = $i;
        }
    }

    // 查找元素 x 所在集合的代表元素，使用路径压缩优化查找效率
    public function find($x)
    {
        // 如果 x 的父节点就是它自身，说明 x 是代表元素，直接返回
        // 否则，递归查找 x 的父节点的代表元素，并将 x 的父节点设为该代表元素
        return $this->f[$x] === $x ? $x : ($this->f[$x] = $this->find($this->f[$x]));
    }

    // 合并元素 x 和 y 所在的集合
    public function unionSet($x, $y): bool
    {
        // 找到 x 所在集合的代表元素
        $fx = $this->find($x);
        // 找到 y 所在集合的代表元素
        $fy = $this->find($y);
        // 如果 x 和 y 已经在同一个集合中，不需要合并，返回 false
        if ($fx === $fy) {
            return false;
        }
        // 为了优化合并，将秩较小的集合合并到秩较大的集合中
        if ($this->rank[$fx] < $this->rank[$fy]) {
            // 交换 fx 和 fy，确保 fx 代表的集合秩更大
            $temp = $fx;
            $fx = $fy;
            $fy = $temp;
        }
        // 将 fy 代表的集合合并到 fx 代表的集合中，更新 fx 集合的秩
        $this->rank[$fx] += $this->rank[$fy];
        // 将 fy 的父节点设为 fx
        $this->f[$fy] = $fx;
        // 合并成功，返回 true
        return true;
    }
}

// 定义边类，用于表示图中的边
class Edge
{
    // 边的长度
    public int $len;
    // 边的一个端点
    public int $x;
    // 边的另一个端点
    public int $y;

    // 构造函数，初始化边的信息
    public function __construct($len, $x, $y)
    {
        $this->len = $len;
        $this->x = $x;
        $this->y = $y;
    }
}

// 定义解决方案类，用于解决连接所有点的最小成本问题
class MinCostConnectPointsSolution
{
    // 计算连接所有点的最小成本的方法
    public function minCostConnectPoints($points)
    {
        // 定义一个匿名函数，用于计算两点之间的曼哈顿距离
        $dist = function ($x, $y) use ($points) {
            // 曼哈顿距离的计算公式：|x1 - x2| + |y1 - y2|
            return abs($points[$x][0] - $points[$y][0]) + abs($points[$x][1] - $points[$y][1]);
        };
        // 点的总数
        $n = count($points);
        // 创建并查集对象，用于处理点的连通性
        $dsu = new DisjointSetUnion($n);
        // 存储所有边的数组
        $edges = [];
        // 遍历所有点对
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                // 计算两点之间的距离，并创建边对象添加到边数组中
                $edges[] = new Edge($dist($i, $j), $i, $j);
            }
        }
        // 对边数组按边的长度进行排序，从小到大排列
        usort($edges, function ($a, $b) {
            return $a->len - $b->len;
        });
        // 存储最小成本的变量
        $ret = 0;
        // 已连接的边的数量，初始为 1
        $num = 1;
        // 遍历排序后的边数组
        foreach ($edges as $edge) {
            // 尝试合并边的两个端点所在的集合
            if ($dsu->unionSet($edge->x, $edge->y)) {
                // 如果合并成功，说明这条边是连接两个不同集合的边，将边的长度累加到最小成本中
                $ret += $edge->len;
                // 已连接的边的数量加 1
                $num++;
                // 当已连接的边的数量达到点的总数时，说明所有点都已连接，退出循环
                if ($num === $n) {
                    break;
                }
            }
        }
        // 返回最小成本
        return $ret;
    }
}

$points = [
    [[0, 0], [2, 2], [3, 10], [5, 2], [7, 0]],
    [[3, 12], [-2, 5], [-4, 1]]
];
$svc = new MinCostConnectPointsSolution();
foreach ($points as $point) {
    echo $svc->minCostConnectPoints($point) . PHP_EOL;
}
