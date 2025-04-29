<?php

# 365. 水壶问题 https://leetcode.cn/problems/water-and-jug-problem/

/**
 * 模拟操作和状态变化
 * 现在开始对水壶进行操作，并记录每一步的状态：
 * 操作一：把大壶灌满：此时大壶有 5 升水，小壶还是 0 升水，状态变为 (0, 5)。这就像你打开水龙头把 5 升容量的水壶接满水一样。
 * 操作二：把大壶的水往小壶倒满：因为小壶容量是 3 升，所以从大壶倒 3 升水给小壶，这时大壶剩下 2 升水，小壶有 3 升水，状态变为 (3, 2)。这就类似于你把大容器里的水往小容器里倒，直到小容器满。
 * 操作三：把小壶倒空：小壶的 3 升水倒掉后，小壶变为 0 升，大壶还是 2 升，状态变为 (0, 2)。
 * 操作四：把大壶里的 2 升水倒进小壶：此时小壶有 2 升水，大壶变为 0 升，状态变为 (2, 0)。
 * 操作五：把大壶再次灌满：大壶又有了 5 升水，小壶还是 2 升水，状态变为 (2, 5)。
 * 操作六：用大壶的水把小壶倒满：小壶还能再装 1 升就满了，所以从大壶倒 1 升水给小壶，这时大壶剩下 4 升水，小壶有 3 升水，状态变为 (3, 4)。我们的目标是得到 4 升水，此时大壶里正好是 4 升，就达到了目标。
 *
 * 算法的作用和逻辑
 * 在算法中，stack 这个栈就像是一个任务清单，记录了每一步要处理的水壶状态。
 * $seen 集合（在 PHP 代码中用数组模拟）就像一个笔记本，记录了哪些状态已经处理过了，避免重复做同样的操作。
 * 每次从栈中取出一个状态，就像从任务清单中取出一个任务来处理。
 * 然后对这个状态进行各种可能的操作（装满、倒空、相互倒水等），产生新的状态，并把新状态加入到栈中，等待后续处理。
 * 如果在处理过程中发现某个状态满足了目标（比如某个水壶里的水量等于我们要的 4 升），就说明可以通过这些操作得到目标水量，算法就返回 true。
 * 如果把栈里所有的状态都处理完了，还没达到目标，那就说明无法得到目标水量，算法返回 false。
 */

class canMeasureWaterSolution
{
    // 定义 seen 属性，用于存储已经访问过的状态
    private array $seen = [];

    public function canMeasureWater($x, $y, $z): bool
    {
        $stack = [[0, 0]];
        while (!empty($stack)) {
            [$remainX, $remainY] = array_pop($stack);
            if ($remainX == $z || $remainY == $z || $remainX + $remainY == $z) {
                return true;
            }
            $key = $remainX . ',' . $remainY;
            if (isset($this->seen[$key])) {
                continue;
            }
            $this->seen[$key] = 1;
            // 把 X 壶灌满。
            $stack[] = [$x, $remainY];
            // 把 Y 壶灌满。
            $stack[] = [$remainX, $y];
            // 把 X 壶倒空。
            $stack[] = [0, $remainY];
            // 把 Y 壶倒空。
            $stack[] = [$remainX, 0];
            // 把 X 壶的水灌进 Y 壶，直至灌满或倒空。
            $stack[] = [
                $remainX - min($remainX, $y - $remainY),
                $remainY + min($remainX, $y - $remainY),
            ];
            // 把 Y 壶的水灌进 X 壶，直至灌满或倒空。
            $stack[] = [
                $remainX + min($remainY, $x - $remainX),
                $remainY - min($remainY, $x - $remainX),
            ];
        }
        return false;
    }
}

$questions = [
    [3, 5, 4]
];
foreach ($questions as $question) {
    $svc = new canMeasureWaterSolution();
    $res = $svc->canMeasureWater($question[0], $question[1], $question[2]);
    var_dump($res);
}