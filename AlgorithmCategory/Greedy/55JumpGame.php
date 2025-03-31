<?php

# 55. 跳跃游戏 https://leetcode.cn/problems/jump-game/

class JumpGameSolutionOne
{
    /**
     * 要求判断是否可以跳跃到末尾, 我们需要思考:
     *  什么情况下不能跳跃到末尾, 那就是我们的停止条件. 可以想到, 当前位置元素的值为0时, 我们就无法移动.
     *  所以, 当前位置能跳跃到最远位置上的值为0时, 我们就无法跳跃到末尾.
     * @param array $ans
     * @return bool
     */
    public function canJump(array $ans): bool
    {
        // 数据长度小于等于1, 不用跳跃, 本身已经是末尾
        if (count($ans) <= 1) {
            return true;
        }

        $last = $maxPosition = count($ans) - 1;
        while ($maxPosition > 0) {
            for ($i = 0; $i < $maxPosition; $i++) {
                if ($i + $ans[$i] >= $maxPosition) {
                    $maxPosition = $i;
                    $last = $maxPosition;
                    break;
                }
            }

            if ($last == $maxPosition) {
                return false;
            }
        }

        return true;
    }
}

$svc = new JumpGameSolutionOne();
echo $svc->canJump(
        [
            2,
            3,
            1,
            1,
            4,
        ]
    ) . PHP_EOL;

class JumpGameSolutionWithMaxPosition
{
    /**
     * @param array $ans
     * @return bool
     */
    public function canJump(array $ans): bool
    {
        $maxIndex = count($ans);
        if ($maxIndex <= 1) {
            return true;
        }

        //
        $maxIndex -= 1;
        $canJumpMaxIndex = 0;
        for ($i = 0; $i <= $maxIndex; $i++) {
            //  当前下标位置已经超过最远能跳跃的距离
            if ($i > $canJumpMaxIndex) {
                return false;
            }

            // $i + $ans[$i] 表示， 从$i 的位置最远可以跳跃到的位置
            $canJumpMaxIndex = max($canJumpMaxIndex, $i + $ans[$i]);
            if ($canJumpMaxIndex >= $maxIndex) {
                return true;
            }
        }

        return false;
    }
}

$svc = new JumpGameSolutionWithMaxPosition();
$ans = [1, 3, 1, 1, 0, 4];
$res = $svc->canJump($ans);
var_dump($res);