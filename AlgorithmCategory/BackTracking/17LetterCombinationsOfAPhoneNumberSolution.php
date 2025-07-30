<?php

# 17. 电话号码的字母组合 https://leetcode.cn/problems/letter-combinations-of-a-phone-number/


class LetterCombinationsOfAPhoneNumberSolution
{
    /**
     * 生成电话号码的所有字母组合
     * @param String $digits
     * @return String[]
     */
    function letterCombinations(string $digits): array
    {
        $combinations = [];
        // 处理空输入
        if (strlen($digits) == 0) {
            return $combinations;
        }

        // 数字到字母的映射表（模拟手机键盘）
        $phoneMap = [
            '2' => 'abc',
            '3' => 'def',
            '4' => 'ghi',
            '5' => 'jkl',
            '6' => 'mno',
            '7' => 'pqrs',
            '8' => 'tuv',
            '9' => 'wxyz'
        ];

        // 调用回溯方法
        $this->backtrack($combinations, $phoneMap, $digits, 0, []);
        return $combinations;
    }

    /**
     * 回溯生成所有组合
     * @param array $combinations 存储所有结果
     * @param array $phoneMap 数字到字母的映射
     * @param string $digits 输入的数字字符串
     * @param int $index 当前处理的数字索引
     * @param array $current 当前正在构建的组合
     */
    private function backtrack(
        array  &$combinations,
        array  $phoneMap,
        string $digits,
        int    $index,
        array  $current
    ): void
    {
        // 终止条件：当前组合长度等于数字长度
        if ($index == strlen($digits)) {
            $combinations[] = implode('', $current);
            return;
        }

        // 获取当前数字对应的字母
        $digit = substr($digits, $index, 1);
        $letters = $phoneMap[$digit];
        $letterCount = strlen($letters);

        // 遍历所有可能的字母
        for ($i = 0; $i < $letterCount; $i++) {
            // 选择当前字母
            $current[] = substr($letters, $i, 1);
            // 递归处理下一个数字
            $this->backtrack($combinations, $phoneMap, $digits, $index + 1, $current);
            // 回溯：移除最后添加的字母
            array_pop($current);
        }
    }
}

$svc = new LetterCombinationsOfAPhoneNumberSolution();
$ans = $svc->letterCombinations("23");
var_dump($ans);