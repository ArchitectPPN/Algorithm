<?php

# 455. 分发饼干 https://leetcode.cn/problems/assign-cookies/description/

class AssignCookiesSolution
{
    /**
     * @param array $g
     * @param array $s
     * @return int
     */
    function findContentChildren(array $g, array $s): int
    {
        // 排序
        sort($g);
        sort($s);

        // 从头开始遍历
        $childIndex = 0;
        $cookieIndex = 0;
        while ($childIndex < count($g) && $cookieIndex < count($s)) {
            // 饼干满足,就看下一个孩子
            if ($s[$cookieIndex] >= $g[$childIndex]) {
                $childIndex++;
            }
            // 饼干分两种情况:
            // 1. 满足, 这个饼干已经分配给一个孩子, 不能在使用了
            // 2. 不满足, 说明这个饼干也无法满足后续孩子, 也不能再使用了
            // 不管满不满足, 饼干都要继续看下一个
            $cookieIndex++;
        }
        return $childIndex;
    }
}

$questionArr = [
    [
        [
            1,
            2,
            3,
        ],
        [
            1,
            1,
            1,
        ],
    ],
    [
        [
            1,
            2,
            3,
            4,
            5,
        ],
        [
            1,
            2,
            3,
            4,
            5,
        ],
    ],
    [
        [
            3,
        ],
        [
            1,
        ],
    ],
    [
        [
        ],
        [
            1,
        ],
    ],
    [
        [
            1,
        ],
        [],
    ],
    [
        [
            1,
            2,
            3,
        ],
        [
            1,
            1,
        ],
    ],
    [
        [
            1,
            2,
        ],
        [
            1,
            2,
            3,
        ],
    ],
    [
        [
            1,
            2,
            3,
        ],
        [
            3,
        ],
    ],
];

$svc = new AssignCookiesSolution();

foreach ($questionArr as $value) {
    echo $svc->findContentChildren($value[0], $value[1]) . PHP_EOL;
}