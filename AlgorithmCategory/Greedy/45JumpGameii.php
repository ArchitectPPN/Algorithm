<?php

# 45. 跳跃游戏 II https://leetcode.cn/problems/jump-game-ii/

class JumpGameIISolutionOne
{
    /**
     * 永远找最远的位置
     * @param array $ans
     * @return int
     */
    public function jump(array $ans): int
    {
        // 数组长度
        $ansLength = count($ans);
        // 如果数组长度小于等于 1，无需跳跃，直接返回 0
        if ($ansLength <= 1) {
            return 0;
        }
        // 步数
        $step = 0;
        // 当前跳跃能到达的最远位置
        $maxPosition = 0;
        // 当前跳跃覆盖的边界
        $end = 0;
        // [1, 0, 0, 5, 1]
        for ($i = 0; $i < $ansLength - 1; $i++) {
            // 更新当前能到达的最远位置
            $maxPosition = max($maxPosition, $i + $ans[$i]);
            // 到达当前跳跃覆盖的边界
            if ($i == $end) {
                // 增加步数
                $step++;
                // 更新下一次跳跃覆盖的边界
                $end = $maxPosition;
            }
        }

        return $step;
    }
}

$svc = new JumpGameIISolutionOne();
echo $svc->jump(
        [
            2,
            3,
            1,
            1,
            4,
        ]
    ) . PHP_EOL;

/**
 * 从左向右遍历, 一直找能跳到末尾位置最小的下标, 然后不断缩小下标, 知道这个下标为0, 循环结束
 * @author niujunqing
 */
class JumpGameIISolutionTwo
{
    /**
     * 永远找最远的位置
     * @param array $ans
     * @return int
     */
    public function jump(array $ans): int
    {
        // 跳到最后一步
        $maxPosition = count($ans) - 1;
        // $maxPosition = count($ans); 这个表示, 到达最后一步以后, 仍然需要往后跳一步
        $step = 0;

        while ($maxPosition > 0) {
            for ($i = 0; $i < $maxPosition; $i++) {
                $tmpIndex = $i + $ans[$i];
                if ($tmpIndex >= $maxPosition) {
                    $maxPosition = $i;
                    $step++;
                    break;
                }
            }
        }

        return $step;
    }
}

$svc = new JumpGameIISolutionTwo();
echo $svc->jump(
        [
            2,
            3,
            1,
            1,
            4,
        ]
    ) . PHP_EOL;

/**
 * THINKING
 * 永远找最远的位置, 循环到当前可以达到的最远位置时, 更新跳跃的步数, 然后更新跳跃的边界位置
 * @author niujunqing
 */
class JumpGameIISolutionReviewOne
{
    /**
     * 永远找最远的位置
     * @param array $ans
     * @return int
     */
    public function jump(array $ans): int
    {
        // 数组长度
        $ansLength = count($ans);
        // 长度小于等于 1，无需跳跃，直接返回 0
        if ($ansLength <= 1) {
            return 0;
        }
        // 当前跳跃能到达的最远位置
        $nowSkipPosition = 0;
        // 本次跳跃的边界位置
        $nowSkipEnd = 0;
        // 步数
        $step = 0;
        $skipIndex = [];

        // 因为最后一步不需要跳跃, 所以循环到倒数第二步
        for ($i = 0; $i < $ansLength - 1; $i++) {
            // 更新能跳跃到的最远位置
            $nowSkipPosition = max($nowSkipPosition, $i + $ans[$i]);

            // 循环位置到达跳跃边界时, 更新下一次跳跃边界位置
            if ($i == $nowSkipEnd) {
                $skipIndex[] = $i;
                $nowSkipEnd = $nowSkipPosition;
                $step++;
            }
        }

        var_dump($skipIndex);
        return $step;
    }
}

$svc = new JumpGameIISolutionReviewOne();
echo $svc->jump(
        [
            2,
            3,
            1,
            1,
            4,
        ]
    ) . PHP_EOL;

class JumpGameIISolutionReviewTwo
{
    /**
     * @param array $ans
     * @return int
     */
    public function jump(array $ans): int
    {
        // 元素总数
        $ansLength = count($ans);
        $step = 0;
        if ($ansLength <= 1) {
            return $step;
        }
        // 当前跳跃能到达的最远位置
        $nowSkipPosition = 0;
        // 上一次的跳跃边界位置
        $lastSkipEnd = 0;

        // 从第一步开始跳, 最后一步不需要跳跃
        for ($i = 0; $i < $ansLength - 1; $i++) {
            $nowSkipPosition = max($nowSkipPosition, $nowSkipPosition + $ans[$i]);

            // 到达跳跃边界时, 更新跳跃步数和跳跃边界位置
            if ($lastSkipEnd == $i) {
                $lastSkipEnd = $nowSkipPosition;
                $step++;
            }
        }

        return $step;
    }
}

$svc = new JumpGameIISolutionReviewTwo();
echo $svc->jump(
        [
            2,
            3,
            1,
            1,
            4,
        ]
    ) . PHP_EOL;

class JumpGameIISolutionTwoReviewOne
{
    /**
     * @param array $ans
     * @return int
     */
    public function jump(array $ans): int
    {
        // 从后往前遍历
        $maxStepPosition = count($ans) - 1;

        // 步数
        $step = 0;
        while ($maxStepPosition > 0) {
            for ($i = 0; $i < $maxStepPosition; $i++) {
                // 当前步数向后移
                $tmpIndex = $i + $ans[$i];
                if ($tmpIndex >= $maxStepPosition) {
                    $maxStepPosition = $i;
                    $step++;
                    break;
                }
            }
        }

        return $step;
    }
}

$svc = new JumpGameIISolutionTwoReviewOne();
echo $svc->jump(
        [
            2,
            3,
            1,
            1,
            4,
        ]
    ) . PHP_EOL;