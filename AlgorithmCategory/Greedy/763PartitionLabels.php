<?php

# 763. 划分字母区间 https://leetcode.cn/problems/partition-labels/description/

class PartitionLabelsSolution
{
    /**
     * @param string $s
     * @return array
     */
    public function partitionLabels(string $s): array
    {
        // 获取字符串长度
        $sLen = strlen($s);

        $charIndexMapping = [];
        for ($i = 0; $i < $sLen; $i++) {
            $charIndexMapping[$s[$i]] = $i;
        }

        $ans = [];
        $star = 0;
        $end = 0;
        for ($i = 0; $i < $sLen; $i++) {
            $end = max($end, $charIndexMapping[$s[$i]]);
            if ($i == $end) {
                $ans[] = $end - $star + 1;
                $star = $i + 1;
            }
        }

        return $ans;
    }
}

$questions = [
    "ababcbacadefegde",
    "eccbbbbdec",
    "abcvx"
];
$svc = new PartitionLabelsSolution();
foreach ($questions as $question) {
    var_dump($svc->partitionLabels($question));
}