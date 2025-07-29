<?php

namespace SubsetsSolution;

/**
 * 核心逻辑
 * 对于包含 n 个元素的数组 nums，总共有 2ⁿ 个子集（每个元素有 “选” 或 “不选” 两种可能）。
 * 代码通过二进制数的每一位来表示 “是否选中对应元素”：
 * 第 j 位为 1 → 选中 nums[j]
 * 第 j 位为 0 → 不选中 nums[j]
 *
 * 示例演示
 * 以 nums = [1,2,3]（n=3）为例：
 * start = 2³ = 8，end = 2⁴ = 16，循环处理 i=8~15。
 *
 * $i    二进制（decbin ($i)）    $bitmask（去首位）    对应子集
 * 8     1000                   000                 []
 * 9     1001                   001                 [1]
 * 10    1010                   010                 [2]
 * 11    1011                   011                 [1,2]
 * 12    1100                   100                 [3]
 * 13    1101                   101                 [1,3]
 * 14    1110                   110                 [2,3]
 * 15    1111                   111
 *
 */
class SubsetsWithBinaryNumber
{
    /**
     * @param Integer[] $nums
     * @return Integer[][]
     */
    public function subsets(array $nums): array
    {
        $result = array();
        $n = count($nums);
        // 计算2的n次方和2的n+1次方
        $start = pow(2, $n);
        $end = pow(2, $n + 1);

        for ($i = $start; $i < $end; $i++) {
            // 将数字转为二进制并去除首位
            $bitmask = substr(decbin($i), 1);
            $cur = array();

            for ($j = 0; $j < $n; $j++) {
                // 检查对应位是否为'1'
                if ($bitmask[$j] == '1') {
                    $cur[] = $nums[$j];
                }
            }

            $result[] = $cur;
        }

        return $result;
    }
}

$svc = new SubsetsWithForeach();
$ans = $svc->subsets([1, 2, 3]);

var_dump($ans);