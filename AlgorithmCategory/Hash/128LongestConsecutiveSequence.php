<?php
# 128. 最长连续序列 https://leetcode.cn/problems/longest-consecutive-sequence/

class LongestConsecutiveSequence
{
    /**
     * 整体思路：
     * 1. 将整个数组的key和value进行翻转
     * 2. 遍历整个数组，我们把每一个元素认为是连续数组中最小的那个元素
     * 3. 如果这个元素的值-1不在翻转的数组中，就可以断定，这个元素的左边已经没有相邻的数字
     * 4. 检查一下这个值+1在不在翻转的数组中，如果不在，说明左右都没有相邻的元素
     * 5. 如果右边存在相邻的元素，就继续往下执行
     * 6. 将当前元素的值一直+1，并判断是否在翻转的数组中，如果存在，说明连续，如果不存在，说明断掉了，将当前的长度保存下来
     *
     * @param Integer[] $nums
     * @return Integer
     */
    function longestConsecutive(array $nums): int
    {
        // 把数组元素存入哈希表（使用 PHP 数组模拟），方便快速查找元素是否存在
        $numSet = array_flip($nums);
        $longestStreak = 0;

        foreach ($numSet as $num => $value) {
            // 若 num - 1 不在哈希表中，说明 num 是一个连续序列的起始数字
            if (!isset($numSet[$num - 1])) {
                $currentNum = $num;
                $currentStreak = 1;

                // 不断检查当前数字的下一个连续数字是否存在于哈希表中
                while (isset($numSet[$currentNum + 1])) {
                    $currentNum++;
                    $currentStreak++;
                }

                // 更新最长连续序列的长度
                $longestStreak = max($longestStreak, $currentStreak);
            }
        }

        return $longestStreak;
    }
}

$nums = [100, 4, 200, 1, 3, 2];
$svc = new LongestConsecutiveSequence();

//echo $svc->longestConsecutive($nums) . PHP_EOL;


class LongestConsecutiveSequenceOutPutArray
{
    /**
     * 整体思路：
     * 1. 将整个数组的key和value进行翻转
     * 2. 遍历整个数组，我们把每一个元素认为是连续数组中最小的那个元素
     * 3. 如果这个元素的值-1不在翻转的数组中，就可以断定，这个元素的左边已经没有相邻的数字
     * 4. 检查一下这个值+1在不在翻转的数组中，如果不在，说明左右都没有相邻的元素
     * 5. 如果右边存在相邻的元素，就继续往下执行
     * 6. 将当前元素的值一直+1，并判断是否在翻转的数组中，如果存在，说明连续，如果不存在，说明断掉了，将当前的长度保存下来
     *
     * @param Integer[] $nums
     * @return Integer
     */
    function longestConsecutive(array $nums): int
    {
        // 把数组元素存入哈希表（使用 PHP 数组模拟），方便快速查找元素是否存在
        $numSet = array_flip($nums);
        $maxStack = [];

        foreach ($numSet as $num => $value) {
            $currentStack = [];
            // 若 num - 1 不在哈希表中，说明 num 是一个连续序列的起始数字
            if (!isset($numSet[$num - 1])) {
                $currentNum = $num;
                $currentStack[] = $currentNum;
                // 不断检查当前数字的下一个连续数字是否存在于哈希表中
                while (isset($numSet[$currentNum + 1])) {
                    $currentNum++;
                    $currentStack[] = $currentNum;
                }

                if (count($currentStack) > count($maxStack)) {
                    $maxStack = $currentStack;
                }
            }
        }

        return count($maxStack);
    }
}

$nums = [-6, -1, -1, 9, -8, -6, -6, 4, 4, -3, -8, -1];
$svc = new LongestConsecutiveSequenceOutPutArray();
echo $svc->longestConsecutive($nums) . PHP_EOL;

/**
 * 对于上述方案的优化，在找到一个连续序列后，直接从这个连续序列里移除已遍历的元素。
 * 因为后续再碰到这些元素时，它们不可能是新的更长连续序列的起始元素，这样就避免了重复计算，也能实现提前终止遍历。
 */
class LongestConsecutiveSequenceOutPutArrayOutPut
{
    /**
     * @param array $nums
     * @return int
     */
    function longestConsecutive(array $nums): int
    {
        // 如果输入数组为空，直接返回 0
        if (empty($nums)) {
            return 0;
        }
        // 将数组元素存入哈希表，方便快速查找元素是否存在
        $numSet = array_flip($nums);
        $longestStreak = 0;

        foreach ($numSet as $num => $value) {
            // 若 num - 1 不在哈希表中，说明 num 是一个连续序列的起始数字
            if (!isset($numSet[$num - 1])) {
                $currentNum = $num;
                $currentStreak = 1;
                // 不断检查当前数字的下一个连续数字是否存在于哈希表中
                while (isset($numSet[$currentNum + 1])) {
                    $currentNum++;
                    $currentStreak++;
                }
                // 更新最长连续序列的长度
                if ($currentStreak > $longestStreak) {
                    $longestStreak = $currentStreak;
                }
                // 移除当前连续序列中的元素，避免后续重复检查
                for ($i = $num; $i <= $currentNum; $i++) {
                    unset($numSet[$i]);
                }
            }
        }
        return $longestStreak;
    }
}