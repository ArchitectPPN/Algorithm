<?php

# 题目321拼接最大数的子问题
class MergeTwoArray
{
    /**
     * 合并两个子序列，生成最大的序列
     * @param array $subsequence1
     * @param array $subsequence2
     * @return array
     */
    public function merge(array $subsequence1, array $subsequence2)
    {
        // 获取两个数组的长度
        $x = count($subsequence1);
        $y = count($subsequence2);

        // 如果 subsequence1 为空，直接返回 subsequence2
        if ($x == 0) {
            return $subsequence2;
        }

        // 如果 subsequence2 为空，直接返回 subsequence1
        if ($y == 0) {
            return $subsequence1;
        }

        // 合并后的长度
        $mergeLength = $x + $y;
        // 初始化合并后的数组
        $merged = array_fill(0, $mergeLength, 0);
        // 初始化两个数组的下标值
        $index1 = $index2 = 0;
        for ($i = 0; $i < $mergeLength; $i++) {
            // 比较两个子序列的当前元素，选择较大的一个
            if ($this->compare($subsequence1, $index1, $subsequence2, $index2) > 0) {
                $merged[$i] = $subsequence1[$index1++];
            } else {
                $merged[$i] = $subsequence2[$index2++];
            }
        }
        return $merged; // 返回合并后的最大序列
    }

    // 比较两个子序列的大小
    private function compare($subsequence1, $index1, $subsequence2, $index2)
    {
        // 统计两个数组的长度
        $x = count($subsequence1);
        $y = count($subsequence2);

        // 逐个比较元素
        while ($index1 < $x && $index2 < $y) {
            $difference = $subsequence1[$index1] - $subsequence2[$index2];
            if ($difference != 0) {
                return $difference; // 如果元素不相等，返回差值
            }
            // 两个元素相等的情况下，我们接着向后看，选择后面元素较大的
            $index1++;
            $index2++;
        }

        // 如果一个子序列已经遍历完，返回剩余长度的差值
        // 如果($x - $index1) 遍历完了，返回的数字必定小于等于0，0 <= ($x - $index1) <= $x
        // 如果($y - $index2) 遍历完了，返回的数字必定大于等于0，0 <= ($y - $index2) <= $y
        return ($x - $index1) - ($y - $index2);
    }
}

$arr1 = [9, 8, 2];
$arr2 = [8, 3];

$svc = new MergeTwoArray();
$arr = $svc->merge($arr1, $arr2);

var_dump($arr);