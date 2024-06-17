<?php
// 共三种解法
/**
 * 1. 数组，用数组下标存储对应链表的节点
 * 2. 单指针法，进行两次循环，第一次统计链表的长度N，那么第二次走到N/2时，直接返回对应的元素即可
 * 3. 快慢指针法，慢指针一次走一步，快指针一次走两步，那么等快指针走到末尾时，慢指针一定才走到中间的位置，直接返回慢指针指向的元素即可
 */

class ListNode {
    /**
     * @var int
     */
    public int $val = 0;

    /**
     * @var ListNode|null
     */
    public ?ListNode $next = null;

    function __construct(int $val = 0, ?ListNode $next = null) {
        $this->val = $val;
        $this->next = $next;
    }
}

class Solution {
    /**
     * @param ListNode $head
     * @return ListNode|null
     */
    function middleNode(ListNode $head): ?ListNode
    {
        // 使用两个快慢指针， 都从头开始
        $slow = $head;
        $quick = $head;

        // 快指针一次走两步， 慢指针一次走一步， 等快指针走到尾时， 慢指针刚好走到中间
        // 此时返回慢指针就可以了
        // 快指针当前节点和下一个节点不为null时
        while ($quick != null && $quick->next != null) {
            $slow = $head->next;
            $quick = $quick->next->next;
        }

        return $slow;
    }
}