<?php

/**
 * 问题分析
 * 1. 循环变量使用错误：
 * 代码中使用了 for ($i = 0; $i < $n; $i++)，但循环体中完全没有使用 $i，而是依赖 $right 进行遍历。这导致 $right 的递增与循环变量 $i 无关，造成逻辑混乱。
 * 2. 返回值与参数引用冲突：
 * 函数通过引用传递数组（&$nums），并在函数内部直接修改数组。此时返回 $nums 虽然技术上可行，但容易引起混淆，因为调用者可以直接访问修改后的原数组，无需返回值。
 * 3. 题目要求可能被误解：
 * LeetCode 题目通常要求在原数组上操作，不需要返回值（函数返回类型为 void）。但此代码定义了返回类型为 array，可能与题目要求不符。
 *
 * @author niujunqing
 */
class MoveZerosSolution
{
    /**
     * @param Integer[] $nums
     * @return Integer[]
     */
    function moveZeroes(array &$nums): array
    {
        $right = $left = 0;
        $n = count($nums);

        for ($i = 0; $i < $n; $i++) {
            if ($nums[$right] != 0) {
                $tmp = $nums[$left];
                $nums[$left] = $nums[$right];
                $nums[$right] = $tmp;
                $left++;
            }
            $right++;
        }

        return $nums;
    }
}

class MoveZerosSolutionWithAiOptimize
{
    /**
     * @param Integer[] $nums
     * @return void
     */
    function moveZeroes(array &$nums): void
    {
        $left = 0;
        $n = count($nums);

        for ($right = 0; $right < $n; $right++) {
            if ($nums[$right] !== 0) {
                // 交换非零元素到左侧
                $temp = $nums[$left];
                $nums[$left] = $nums[$right];
                $nums[$right] = $temp;
                $left++;
            }
        }
    }
}

$questions = [
    [1, 0, 2, 0, 3, 0, 4, 5],
    [0, 0, 2, 3, 6, 0, 7, 5],
];

$svc = new MoveZerosSolution();
foreach ($questions as $question) {
    $svc->moveZeroes($question);
    var_dump($question);
}

