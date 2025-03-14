<?php

# 3340.检查平衡字符串 https://leetcode.cn/problems/check-balanced-string/
class CheckBalancedStringSolution
{
    /**
     * THINKING:
     * 如果是平衡树, 那么奇数-偶数上的值最后一定是0
     * 所以初始化一个diff=0; 这个diff用来保存每一对奇数与偶数的差值, 最后为0返回true,否则返回false;
     * 为了保证偶数累加, 奇数相减的, 所以初始化一个符号值sign=1; 在奇数时, 将其符号置为-1, 偶数时, 符号置为1;
     * 遍历字符串,
     * @param string $checkString
     * @return bool
     */
    public function checkBalancedString(string $checkString): bool
    {
        $diff = 0;
        $sign = 1;
        $sLen = strlen($checkString);

        for ($i = 0; $i < $sLen; $i++) {
            $diff += $sign * intval($checkString[$i]);
            $sign = -$sign;
        }

        return $diff == 0;
    }
}