<?php

namespace AddTwoNumbersSolution;

class ListNode
{
    /** @var int */
    public int $val = 0;

    /** @var ?ListNode */
    public ?ListNode $next = null;

    /**
     * @param int $val
     * @param ?ListNode $next
     */
    function __construct(int $val = 0, ?ListNode $next = null)
    {
        $this->val = $val;
        $this->next = $next;
    }
}

/**
 * 整体思路：
 * 模拟人为计算过程，123 + 456 = 579
 */
class Solution
{
    /**
     * 两数相加
     *
     * @param ?ListNode $l1
     * @param ?ListNode $l2
     * @return ?ListNode
     */
    public function addTwoNumbers(?ListNode $l1, ?ListNode $l2): ?ListNode
    {
        if (is_null($l1) && is_null($l2)) {
            return null;
        }

        // head用来返回最后得答案，tail用来控制链表向后移动
        $head = $tail = null;

        // 进位
        $carry = 0;

        while ($l1 || $l2) {
            // 获取两个链表得当前节点的值，第一个为个位，依次往后
            $one = $l1 ? $l1->val : 0;
            $two = $l2 ? $l2->val : 0;

            // 将两个值相加
            $sum = $one + $two + $carry;
            // 如果两个数相加大于10，进位加1
            $carry = $sum >= 10 ? 1 : 0;

            // 头节点为null，创建头节点
            if (is_null($head)) {
                $head = $tail = new ListNode($sum % 10);
            } else {
                // 头节点不为null，创建下一个节点
                $tail->next = new ListNode($sum % 10);
                // 向后移动
                $tail = $tail->next;
            }

            if ($l1) $l1 = $l1->next;
            if ($l2) $l2 = $l2->next;
        }

        // 进位大于0，在链表末尾添加一个元素
        if ($carry > 0) {
            $tail->next = new ListNode(1);
        }

        return $head;
    }
}
