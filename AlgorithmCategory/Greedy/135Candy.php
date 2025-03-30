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
        if ($count <= 1) {
            return $count;
        }

        // 最少需要的糖果数
        $lessNeed = 1;
        // 每一个孩子最少可以得到一个糖果，所以$pre默认为1
        $pre = 1;
        // 我们把每一个孩子单独看成一个上升序列，所以长度默认为1
        $incr = 1;
        // 相应的需要把递减序列长度看作0
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

$ratings = [1, 2, 2, 2, 3, 2];
$ratings = [1,2,3,2,1];
$svc = new CandySolution();
echo $svc->candy($ratings) . PHP_EOL;

/**
 * $candies[$i] = max($candies[$i], $candies[$i + 1] + 1); 这行看不懂时，可以看下面的例子
 *
 * 评分数组 $ratings = [1, 3, 2, 2, 1]。
 * 第一次从左向右遍历
 * 初始化 $candies = [1, 1, 1, 1, 1]。
 * 当 $i = 1 时，$ratings[1] = 3 大于 $ratings[0] = 1，所以 $candies[1] = $candies[0] + 1 = 2。
 * 当 $i = 2 时，$ratings[2] = 2 小于 $ratings[1] = 3，所以 $candies[2] 保持为 1。
 * 当 $i = 3 时，$ratings[3] = 2 等于 $ratings[2] = 2，所以 $candies[3] 保持为 1。
 * 当 $i = 4 时，$ratings[4] = 1 小于 $ratings[3] = 2，所以 $candies[4] 保持为 1。
 * 此时，$candies = [1, 2, 1, 1, 1]。
 * 第二次从右向左遍历
 * 当 $i = 3 时，$ratings[3] = 2 大于 $ratings[4] = 1，$candies[3] 应该至少为 $candies[4] + 1 = 2，而此时 $candies[3] = 1，所以更新 $candies[3] = max($candies[3], $candies[4] + 1) = 2。
 * 当 $i = 2 时，$ratings[2] = 2 等于 $ratings[3] = 2，不需要调整 $candies[2]，$candies 数组变为 [1, 2, 1, 2, 1]。
 * 当 $i = 1 时，$ratings[1] = 3 大于 $ratings[2] = 2，$candies[1] 原本为 2，$candies[2] + 1 = 2，所以 $candies[1] 保持为 2。
 * 当 $i = 0 时，$ratings[0] = 1 小于 $ratings[1] = 3，不需要调整 $candies[0]。
 * 最终，$candies = [1, 2, 1, 2, 1]，总共需要的糖果数为 1 + 2 + 1 + 2 + 1 = 7。
 * 通过这个例子可以看出，$candies[$i] = max($candies[$i], $candies[$i + 1] + 1); 这行代码在第二次遍历时起到了关键作用，它确保了在满足规则的同时，尽可能少地分配糖果。
 */
class CandySolutionForLoopTwice
{
    /**
     * @param array $ratings
     * @return int
     */
    public function candy(array $ratings): int
    {
        $count = count($ratings);
        if ($count === 0) {
            return 0;
        }

        // 初始化每个孩子至少有一颗糖果
        $candies = array_fill(0, $count, 1);

        // 第一次遍历：从左到右
        for ($i = 1; $i < $count; $i++) {
            if ($ratings[$i] > $ratings[$i - 1]) {
                $candies[$i] = $candies[$i - 1] + 1;
            }
        }

        // 第二次遍历：从右到左
        // $i = $count - 2, 最大的下标为 $count - 1, 然后最后一个元素后面没有元素了, 所以去掉, 最后: $i = $count - 2
        for ($i = $count - 2; $i >= 0; $i--) {
            if ($ratings[$i] > $ratings[$i + 1]) {
                // 这里的max保证了最少的原则，在i大于i+1时，我们只要保证i分得的糖果比i+1大就可以了
                $candies[$i] = max($candies[$i], $candies[$i + 1] + 1);
            }
        }

        // 计算总共需要的糖果数
        return array_sum($candies);
    }
}

$candy = [
    1,
    0,
    2,
];
$svc = new CandySolutionForLoopTwice();
echo $svc->candy($candy);

#-----------------------------------------------------------------------------------------------------------------------
# Review Start

/**
 * 使用两次for循环
 * 第一次循环计算出：从左到右每一个孩子应该得到的糖果，第一个元素会被计算为1个
 */
class CandySolutionForLoopTwiceForLoopReviewOne
{
    /**
     * @param array $ratings
     * @return int
     */
    public function candy(array $ratings): int
    {
        // 计算孩子的数量
        $count = count($ratings);
        if ($count <= 1) {
            return $count;
        }

        // 每一个孩子默认都能得到一颗
        $totalLessNeed = array_fill(0, $count, 1);

        // 从第二个孩子开始遍历
        for ($i = 1; $i < $count; $i++) {
            // 当前孩子评分高于前一个孩子，当前孩子就需要多获取一颗糖果
            if ($ratings[$i] > $ratings[$i - 1]) {
                $totalLessNeed[$i] = $totalLessNeed[$i - 1] + 1;
            }
        }

        // $count - 2 $count - 1表示最大的下标，再减去1表示最后一个元素最右边没有孩子了， 不需要计算
        for ($i = $count - 2; $i >= 0; $i--) {
            if ($ratings[$i] > $ratings[$i + 1]) {
                $totalLessNeed[$i] = max($totalLessNeed[$i], $totalLessNeed[$i + 1] + 1);
            }
        }

        return array_sum($totalLessNeed);
    }
}

