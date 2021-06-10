<?php

class ClimbingStairs70 {

    private $record = [];

    /**
     * 记忆优化递归解法
     *
     * @param Integer $totalStep
     * @return Integer
     */
    public function rememberClimbStairs(int $totalStep) : int {
        if ($totalStep <= 2) return $totalStep;
        if (isset($this->record[$totalStep])) {
            return $this->record[$totalStep];
        }

        echo $totalStep . '-';
        $this->record[$totalStep] = $this->rememberClimbStairs($totalStep - 1) + $this->rememberClimbStairs($totalStep - 2);

        return $this->record[$totalStep];
    }

    /**
     * 暴力递归解法
     *
     * @param Integer $totalStep
     * @return Integer
     */
    public function violenceClimbStairs(int $totalStep): int {
        if ($totalStep <= 2) return $totalStep;

        echo '-' . $totalStep . '-';

        return $this->violenceClimbStairs($totalStep - 1) + $this->violenceClimbStairs($totalStep - 2);
    }
}

// 记忆优化递归
var_dump((new ClimbingStairs70())->rememberClimbStairs(13));
// 暴力递归
var_dump((new ClimbingStairs70())->violenceClimbStairs(20));