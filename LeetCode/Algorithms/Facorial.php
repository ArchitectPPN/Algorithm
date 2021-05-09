<?php
class Solution
{
    public function climbStairs()
    {
        // 初始化
        $memo[] = [$n- 1];
    }

    public function ClimbStairsMemo(int $n, array $memo)
    {
        if ($memo[$n] > 0 ) {
            return $memo[$n];
        }

        if ($n == 1) {
            $memo[$n] = 1;
        } else if ($n == 2) {
            $memo[$n] = 2;
        } else {
            $memo[$n] = $this->climbStairs($n-1, $memo) + $this->climbStairs($n -2, $memo);
        }

        return $memo[$n];
    }
}



