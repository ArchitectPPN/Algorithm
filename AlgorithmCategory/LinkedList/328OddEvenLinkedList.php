<?php

# [328. 奇偶链表](https://leetcode-cn.com/problems/odd-even-linked-list)

namespace OddEvenListSolution;

class ListNode
{
    public int $val = 0;
    public ?ListNode $next = null;

    function __construct(int $val = 0, ListNode $next = null)
    {
        $this->val = $val;
        $this->next = $next;
    }
}

class Solution
{
    /**
     * @param ListNode|null $head
     * @return ListNode|null
     */
    function oddEvenList(?ListNode $head): ?ListNode
    {
        if (is_null($head)) {
            return null;
        }

        // 由于第一个元素就是奇数, 所以直接指向第一个节点
        $odd = $head;

        // 第二个元素为偶数, 所以指向第二个节点
        $event = $eventHead = $head->next;

        while($odd && $event->next) {
            // 偶数的下一个节点一定是奇数
            $odd->next = $event->next;
            $odd = $odd->next;

            // 奇数的下一个节点一定是偶数
            $event->next = $odd->next;
            $event = $event->next;
        }

        // 将奇数节点和偶数节点分别连接起来
        $odd->next = $eventHead;

        return $head;
    }
}