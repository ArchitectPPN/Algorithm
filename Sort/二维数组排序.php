<?php
/**
 * 关联数组排序
 */
$people = [
    ['name' => 'John', 'age' => 28],
    ['name' => 'Jane', 'age' => 22],
    ['name' => 'Doe', 'age' => 32],
];
// 在这个例子中，<=> 运算符（太空船运算符）用于比较两个值并返回一个整数：如果 $a['age'] 小于 $b['age']，则返回 -1；如果相等，则返回 0；如果大于，则返回 1。
// 经过排序后的 $people 数组将变为：
/**
 * [
 *   ['name' => 'Jane', 'age' => 22],
 *   ['name' => 'John', 'age' => 28],
 *   ['name' => 'Doe', 'age' => 32],
 * ]
 */

usort($people, function ($a, $b) {
    return $a['age'] <=> $b['age']; // 按年龄升序排序
});


/**
 *
 */