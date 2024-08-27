<?php

namespace ReadBinaryWatch;

class Solution
{
    /**
     * @param Integer $turnedOn
     * @return String[]
     */
    public function readBinaryWatch(int $turnedOn): array
    {
        $result = [];

        // 遍历所有小时（0-11）和分钟（0-59）的组合
        for ($hour = 0; $hour < 12; $hour++) {
            for ($minute = 0; $minute < 60; $minute++) {
                // 使用内置的 bit_count 函数来统计 1 的数量
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