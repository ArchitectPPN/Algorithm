<?php

namespace EnumReadBinaryWatch;

# https://leetcode.cn/problems/binary-watch/description/
# 解法1: 枚举
# 解法2: 回溯, 请在回溯目录中查看
class Solution
{
    /**
     * 思路:
     * 这种思路, 就是枚举每一个小时的每一分钟是否符合条件;
     * 对于符合条件的组合, 添加到结果数组中.
     *
     * 改题目循环的次数和输入的大小是没有关系的, 循环次数是固定的,
     *
     *
     * @param Integer $turnedOn
     * @return String[]
     */
    public function readBinaryWatch(int $turnedOn): array
    {
        $result = [];

        // 遍历所有小时（0-11）和分钟（0-59）的组合
        for ($hour = 0; $hour < 12; $hour++) {
            for ($minute = 0; $minute < 60; $minute++) {
                // 使用内置的 bitCount 函数来统计 1 的数量
                if ($this->bitCount($hour) + $this->bitCount($minute) === $turnedOn) {
                    // 格式化时间，确保分钟总是两位数
                    $result[] = sprintf("%d:%02d", $hour, $minute);
                }
            }
        }

        return $result;
    }

    /**
     * @param int $num
     * @return int
     */
    private function bitCount(int $num): int
    {
        // decbin($num) 将十进制数转换为二进制字符串, 1 -> 0001; 5 -> 101; 7 -> 111
        // substr_count 统计字符串中子串出现的次数, 这里就是统计1出现的次数
        return substr_count(decbin($num), '1');
    }
}

$solution = new Solution();
$res = $solution->readBinaryWatch(1);
var_dump($res);