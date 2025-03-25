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
     * @var ListNode|null
     */
    public null|ListNode $next = null;

    /**
     * @param int $val
     * @param ListNode|null $next
     */
    function __construct(int $val = 0, ?ListNode $next = null)
    {
        $this->val = $val;
        $this->next = $next;
    }
}

class Solution
{
    /**
     * 暴力循环解法
     *
     * @param ListNode|null $l1
     * @param ListNode|null $l2
     * @return ListNode|null
     */
    public function mergeTwoLists(?ListNode $l1, ?ListNode $l2): ?ListNode
    {
        // 两个有一个为null, 直接返回另外一个
        if (is_null($l1)) {
            return $l2;
        } else if (is_null($l2)) {
            return $l1;
        }

        // $head 最后要返回的节点
        $head = new ListNode(-1);
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

    /**
     * 递归算法
     *
     * @param ListNode|null $l1
     * @param ListNode|null $l2
     * @return ListNode|null
     */
    public function mergeTwoLists2(?ListNode $l1, ?ListNode $l2): ?ListNode
    {
        // 两个有一个为null, 直接返回另外一个
        if (is_null($l1)) {
            return $l2;
        } else if (is_null($l2)) {
            return $l1;
        }

        if ($l1->val <= $l2->val) {
            $l1->next = $this->mergeTwoLists2($l1->next, $l2);
            return $l1;
        } else {
            $l2->next = $this->mergeTwoLists2($l1, $l2);
            return $l2;
        }
    }
}