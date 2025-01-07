<?php
/**
 * 关联数组排序
 */
$people = [
    [
        'name' => 'John',
        'age' => 28,
    ],
    [
        'name' => 'Jane',
        'age' => 22,
    ],
    [
        'name' => 'Doe',
        'age' => 32,
    ],
];

var_dump("before sort:", $people);

// 在这个例子中，<=> 运算符（太空船运算符）用于比较两个值并返回一个整数：如果 $a['age'] 小于 $b['age']，则返回 -1；如果相等，则返回 0；如果大于，则返回 1。
// 经过排序后的 $people 数组将变为：

usort($people, function ($a, $b) {
    return $a['age'] <=> $b['age']; // 按年龄升序排序
});
var_dump("after asc sort:", $people);

/**
 * 在这个例子中，<=> 运算符（太空船运算符）用于比较两个值，并根据它们的大小关系返回 -1、0 或 1：
 * 1. 如果 $a['age'] 小于 $b['age']，则返回 -1。
 * 2. 如果 $a['age'] 等于 $b['age']，则返回 0。
 * 3. 如果 $a['age'] 大于 $b['age']，则返回 1。
 * 由于 usort() 函数根据这个返回值来决定数组的排序顺序：
 * 1. 当返回 -1 时，$a 会排在 $b 之前（即升序）。
 * 2. 当返回 1 时，$b 会排在 $a 之前（即升序）。
 * 3. 当返回 0 时，$a 和 $b 的顺序保持不变。
 * 因此，$a['age'] <=> $b['age'] 这段代码会导致按 年龄升序 进行排序，也就是说，年龄较小的元素会排在前面，年龄较大的元素会排在后面。
 */
// 如果你想按降序排序，只需要调换 $a 和 $b 的顺序即可：
usort($people, function ($a, $b) {
    return $b['age'] <=> $a['age']; // 按年龄降序排序
});
var_dump("after desc sort:", $people);

/**
 * 非关联数组排序
 */
$indexArray = [
    [
        1,
        3,
    ],
    [
        8,
        10,
    ],
    [
        2,
        6,
    ],
    [
        15,
        18,
    ],
];
var_dump("before asc sort:", $indexArray);
usort($indexArray, function ($a, $b) {
    return $a[0] <=> $b[0]; // 按年龄降序排序
});
var_dump("after asc sort:", $indexArray);
usort($indexArray, function ($a, $b) {
    return $b[0] <=> $a[0]; // 按年龄降序排序
});
var_dump("after desc sort:", $indexArray);