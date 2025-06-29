<?php

# [160. 相交链表](https://leetcode-cn.com/problems/intersection-of-two-linked-lists)

# 走到尽头见不到你，于是走过你来时的路，等到相遇时才发现，你也走过我来时的路。
# 对的人错过了还是会相遇， 错的人相遇了也是NULL。

namespace LinkedListGetIntersectionNode;

class ListNode {
    /**
     * @var int
     */
    public int $val = 0;

    /**
     * @var ListNode|null
     */
    public ?ListNode $next = null;

    /**
     * @param int $val
     */
    function __construct(int $val) {
        $this->val = $val;
    }
}

/**
 * 情况 1：链表相交
 * A: 1 → 2 → 3 ↘
 *                7 → 8 → 9 （交点为7）
 * B: 4 → 5 → 6 ↗
 *
 * 指针移动路径：
 * $headPa: 1→2→3→7→8→9→4→5→6→7 （相遇）
 * $headPb: 4→5→6→7→8→9→1→2→3→7 （相遇）
 *
 * 情况 2：链表不相交
 * A: 1 → 2 → 3
 * B: 4 → 5 → 6
 *
 * 指针移动路径：
 * $headPa: 1→2→3→null→4→5→6→null （终止）
 * $headPb: 4→5→6→null→1→2→3→null （终止）
 */
class Solution {
    /**
     * @param ListNode|null $headA
     * @param ListNode|null $headB
     * @return ListNode|null
     */
    function getIntersectionNode(?ListNode $headA, ?ListNode $headB): ?ListNode
    {
        // 初始化头
        $pA = $headA;
        $pB = $headB;

        // 两个节点都走到结尾是null
        while ($pA !== $pB) {
            // 如果pA走到结尾后，然后开始接着$headB走
            // 如果不是null，则继续走pA的节点
            $pA = $pA == null ? $headB : $pA->next;
            $pB = $pB == null ? $headA : $pB->next;
        }

        return $pA;
    }
}