<?php

# [21. 合并两个有序链表](https://leetcode-cn.com/problems/merge-two-sorted-lists)

namespace MergeTwoSortedLists;

class RecursionListNode
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

class MergeTwoListsSolutionWithRecursion
{
    /**
     * 递归算法
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

        if ($l1->val <= $l2->val) {
            $l1->next = $this->mergeTwoLists($l1->next, $l2);
            return $l1;
        } else {
            $l2->next = $this->mergeTwoLists($l1, $l2);
            return $l2;
        }
    }
}