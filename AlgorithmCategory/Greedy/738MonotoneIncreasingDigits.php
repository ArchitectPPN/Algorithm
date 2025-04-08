<?php

# 738. 单调递增的数字 https://leetcode.cn/problems/monotone-increasing-digits/description/

class MonotoneIncreasingDigitsSolution
{
    /**
     * @param Integer $n
     * @return Integer
     */
    function monotoneIncreasingDigits(int $n): int
    {
        // 转为字符数组
        $nStr = str_split(strval($n));
        // 获取数组长度
        $sCount = count($nStr);

        for ($i = 1; $i < $sCount; $i++) {
            // 当前元素小于前一个元素, 说明有降序, 需要调整
            if ($nStr[$i] < $nStr[$i - 1]) {
                // 找到 i 之后, 把 i - 1 位置的数字减 1, 因为 i - 1 位置上的数字变化了, 要检查 i - 1 之前的数字是不是小于 i - 1
                // 所以需要依次向前看
                while ($i > 0 && $nStr[$i] < $nStr[$i - 1]) {
                    $nStr[$i - 1] = chr(ord($nStr[$i - 1]) - 1);
                    $i--;
                }

                // 将 i 后面的数字都置为 9
                for ($j = $i + 1; $j < $sCount; $j++) {
                    $nStr[$j] = '9';
                }
                break;  // 找到第一个需要调整的位置后就退出循环
            }
        }

        // 处理特殊情况：如果第一个数字减为 0，需要去掉前面的 0
        while ($nStr[0] === '0') {
            array_shift($nStr);
        }

        return intval(implode('', $nStr));
    }
}

$questions = [
    12056,
    1234,
    10,
    332,
    20,
];
$svc = new MonotoneIncreasingDigitsSolution();
foreach ($questions as $question) {
    echo $svc->monotoneIncreasingDigits($question) . PHP_EOL;
}