<?php
# 321. 拼接最大数 https://leetcode.cn/problems/create-maximum-number/description/ D

class CreateMaximumNumberSolution
{
    public function maxNumber($nums1, $nums2, $k)
    {
        $m = count($nums1);
        $n = count($nums2);
        // 填充一个数组 [0, 0, 0, 0]，数组元素数量为K
        $maxSubsequence = array_fill(0, $k, 0);

        // 从nums1中至少选出$start个元素
        $start = max(0, $k - $n);
        // 从nums1中最多选出$end个元素
        $end = min($k, $m);

        for ($i = $start; $i <= $end; $i++) {
            $subsequence1 = $this->maxSubsequence($nums1, $i);
            $subsequence2 = $this->maxSubsequence($nums2, $k - $i);
            $curMaxSubsequence = $this->merge($subsequence1, $subsequence2);
            if ($this->compare($curMaxSubsequence, 0, $maxSubsequence, 0) > 0) {
                $maxSubsequence = $curMaxSubsequence;
            }
        }
        return $maxSubsequence;
    }

    /**
     * 得到最大的子序列
     * @param array $nums
     * @param int $k
     * @return array
     */
    public function maxSubsequence(array $nums, int $k): array
    {
        // 获取当前的长度
        $length = count($nums);

        // 使用0填充数组的长度
        $stack = array_fill(0, $k, 0);
        // 设置栈顶为-1
        $top = -1;
        // 剩余可以删除的数量
        $remain = $length - $k;
        for ($i = 0; $i < $length; $i++) {
            // 拿到当前元素
            $num = $nums[$i];
            // 栈顶元素大于0，
            while ($top >= 0 && $stack[$top] < $num && $remain > 0) {
                $top--;
                $remain--;
            }
            if ($top < $k - 1) {
                $stack[++$top] = $num;
            } else {
                $remain--;
            }
        }
        return $stack;
    }

    public function merge($subsequence1, $subsequence2)
    {
        // 统计两个数组的changed
        $x = count($subsequence1);
        $y = count($subsequence2);
        // 其中一个长度为0时，直接返回另外一个数组
        if ($x == 0) {
            return $subsequence2;
        }
        if ($y == 0) {
            return $subsequence1;
        }
        // 要合并的长度
        $mergeLength = $x + $y;
        // 初始化最后合并的数组
        $merged = array_fill(0, $mergeLength, 0);
        // 两个数组都从0开始
        $index1 = 0;
        $index2 = 0;
        for ($i = 0; $i < $mergeLength; $i++) {
            if ($this->compare($subsequence1, $index1, $subsequence2, $index2) > 0) {
                $merged[$i] = $subsequence1[$index1++];
            } else {
                $merged[$i] = $subsequence2[$index2++];
            }
        }
        return $merged;
    }

    /**
     * @param $subsequence1
     * @param $index1
     * @param $subsequence2
     * @param $index2
     * @return int|mixed
     */
    public function compare($subsequence1, $index1, $subsequence2, $index2): mixed
    {
        $x = count($subsequence1);
        $y = count($subsequence2);
        while ($index1 < $x && $index2 < $y) {
            $difference = $subsequence1[$index1] - $subsequence2[$index2];
            if ($difference != 0) {
                return $difference;
            }
            $index1++;
            $index2++;
        }
        return ($x - $index1) - ($y - $index2);
    }
}