<?php

# 135. 分发糖果 https://leetcode.cn/problems/candy/description/

class CandySolution
{
    /**
     * @param array $ratings
     * @return int
     */
    public function candy(array $ratings): int
    {
        // 人数
        $count = count($ratings);
        // 如果人数为0，直接返回0
        if ($count == 0) {
            return 0;
        } elseif ($count == 1) {
            // 人数为1，直接返回1
            return 1;
        }

        // 最少需要的糖果数
        $lessNeed = 1;
        $pre = $incr = 1;
        $decr = 0;

        for ($i = 1; $i < $count; $i++) {
            // 看做一个递增的序列
            if ($ratings[$i] >= $ratings[$i - 1]) {
                // 初始化递减序列
                $decr = 0;

                // 如果相等, 则只需要1个, 否则需要递增的个数+1
                $pre = $ratings[$i] === $ratings[$i - 1] ? 1 : $pre + 1;
                $lessNeed += $pre;
                $incr = $pre;
            } else {
                // 递减序列
                $decr++;
                // 如果递增序列和递减序列相等，把递增序列最后那一个+1
                // [2,1]  2比1大, 2 -> 1递减, 2的位置需要两颗, 所以要加1
                if ($decr === $incr) {
                    $decr++;
                }
                $lessNeed += $decr;
                $pre = 1;
            }
        }

        return $lessNeed;
    }
}

$svc = new CandySolution();
echo $svc->candy(
        [
            1,
            2,
        ]
    ) . PHP_EOL;