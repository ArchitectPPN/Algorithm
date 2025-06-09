<?php

# 49. 字母异位词分组 https://leetcode.cn/problems/group-anagrams/description/

class GroupAnagramsSolution
{
    /**
     * @param String[] $strs
     * @return String[][]
     */
    function groupAnagrams(array $strs): array
    {
        $groups = [];

        foreach ($strs as $str) {
            // 统计字符出现次数
            $count = array_fill_keys(range('a', 'z'), 0);
            for ($i = 0; $i < strlen($str); $i++) {
                $count[$str[$i]]++;
            }

            // 生成标准化哈希键（按字典序排列）
            $hashKey = '';
            foreach (range('a', 'z') as $char) {
                if ($count[$char] > 0) {
                    $hashKey .= $char . $count[$char];
                }
            }

            // 将字符串添加到对应组
            $groups[$hashKey][] = $str;
        }

        // 转换为索引数组返回
        return array_values($groups);
    }

    /**
     * @param String[] $strs
     * @return String[][]
     */
    function groupAnagramsWithSerialization(array $strs): array
    {
        $groups = [];

        foreach ($strs as $str) {
            // 初始化字符计数数组
            $count = array_fill(0, 26, 0);

            // 统计每个字符的出现次数
            for ($i = 0; $i < strlen($str); $i++) {
                $index = ord($str[$i]) - ord('a');
                $count[$index]++;
            }

            // 使用数组作为键（PHP 会自动将数组转换为字符串）
            $key = serialize($count);

            // 将字符串添加到对应的组
            $groups[$key][] = $str;
        }

        // 返回二维数组
        return array_values($groups);
    }
}

$questions = [
    ['eat', 'tea', 'tan', 'ate', 'nat', 'bat'],
];

$svc = new GroupAnagramsSolution();

foreach ($questions as $question) {
    $ans = $svc->groupAnagrams($question);
    var_dump($ans);
}