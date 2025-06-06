<?php

# [21. 合并两个有序链表](https://leetcode-cn.com/problems/merge-two-sorted-lists)

namespace MergeTwoSortedLists;

class ListNode
{
    /**
     * @var int
     */
    public int $val = 0;

    /**
     * @var RecursionListNode|null
     */
    public null|RecursionListNode $next = null;

    /**
     * @param int $val
     * @param RecursionListNode|null $next
     */
    function __construct(int $val = 0, ?RecursionListNode $next = null)
    {
        $this->val = $val;
        $this->next = $next;
    }
}

class Solution
{
    /**
     * 暴力循环解法
     * @param RecursionListNode|null $l1
     * @param RecursionListNode|null $l2
     * @return RecursionListNode|null
     */
    public function mergeTwoLists(?RecursionListNode $l1, ?RecursionListNode $l2): ?RecursionListNode
    {
        // 两个有一个为null, 直接返回另外一个
        if (is_null($l1)) {
            return $l2;
        } elseif (is_null($l2)) {
            return $l1;
        }

        // $head 最后要返回的节点
        $head = new RecursionListNode(-1);
        // next操作的节点
        $next = $head;

        while (!is_null($l1) && !is_null($l2)) {
            if ($l1->val <= $l2->val) {
                // next的下一个节点指向l1
                $next->next = $l1;
                // l1向后移动
                $l1 = $l1->next;
            } else {
                $next->next = $l2;
                $l2 = $l2->next;
            }
            // $next向后移动
            $next = $next->next;
        }
        $next->next = is_null($l2) ? $l1 : $l2;

        return $head->next;
    }
}