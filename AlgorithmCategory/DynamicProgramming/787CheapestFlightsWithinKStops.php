<?php

# 787. K 站中转内最便宜的航班 https://leetcode.cn/problems/cheapest-flights-within-k-stops/

class FindCheapestPriceSolution
{
    /** @var int 最大值，表示无法到达的路径 */
    const MaxAnswer = 10000 * 101 + 1;

    /**
     * 使用动态规划计算从src到dst最多经过k次中转的最便宜价格
     * @param int $n n个城市相连
     * @param array $flights 航班列表，每个元素为 [出发城市, 到达城市, 价格]
     * @param int $src 出发点
     * @param int $dst 目标点
     * @param int $k 最多允许的中转站数量
     * @return int 最小花费，如果无法到达返回-1
     */
    public function findCheapestPrice(int $n, array $flights, int $src, int $dst, int $k): int
    {
        // 初始化动态规划数组f，f[i]表示当前到达城市i的最小花费
        // 初始值设为MaxAnswer表示不可达
        $f = array_fill(0, $n, self::MaxAnswer);

        // 出发城市的初始花费为0
        $f[$src] = 0;

        // 最终答案初始化为最大值
        $ans = self::MaxAnswer;

        // 进行k+1次松弛操作，每次允许增加一个中转站
        for ($t = 1; $t <= $k + 1; ++$t) {
            // 创建临时数组g，用于保存本轮松弛后的结果
            // 防止在更新过程中使用了本轮已更新的值
            $g = array_fill(0, $n, self::MaxAnswer);

            // 遍历所有航班，尝试更新每个航班终点的最小花费
            foreach ($flights as $flight) {
                $j = $flight[0];  // 出发城市
                $i = $flight[1];  // 到达城市
                $cost = $flight[2];  // 航班价格

                // 如果当前出发城市可达（f[j]不是无穷大）
                // 并且通过该航班到达i的总花费更小
                // 则更新临时数组g中i的最小花费
                if ($f[$j] !== self::MaxAnswer) {
                    $g[$i] = min($g[$i], $f[$j] + $cost);
                }
            }

            // 将临时数组g的值赋给f，完成本轮松弛
            $f = $g;

            // 检查经过t次松弛后，到达目标城市的最小花费是否更小
            $ans = min($ans, $f[$dst]);
        }

        // 如果最终答案仍为MaxAnswer，表示无法到达目标城市
        return ($ans == self::MaxAnswer) ? -1 : $ans;
    }
}

$n = 3;
$edges = [[0, 1, 100], [1, 2, 100], [0, 2, 500]];
$src = 0;
$dst = 2;
$k = 1;
$svc = new FindCheapestPriceSolution();
$ans = $svc->findCheapestPrice($n, $edges, $src, $dst, $k);
var_dump($ans);