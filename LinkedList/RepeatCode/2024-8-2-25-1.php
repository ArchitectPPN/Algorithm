<?php
namespace Repeat202482251;

class ListNode
{
    /**
     * @var int
     */
    public int $val = 0;

    /**
     * @var ?ListNode
     */
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

class Solution {
    /**
     * @param ?ListNode $head
     * @param Integer $k
     * @return ListNode
     */
    function reverseKGroup(?ListNode $head, int $k): ListNode
    {
        $protectedNode = new ListNode(-1);
        $last = $protectedNode;

        while(!is_null($head)) {
            $end = $this->getEnd($head, $k);
            if (is_null($end)) break;

            $nextGroupHead = $end->next;
            // reverse
            $this->reverseList($head, $end);

            // 上一组的尾节点指向下一组新的头节点
            $last->next = $end;
            // 指向下一组的头节点
            $head->next = $nextGroupHead;
            $last = $head;

            $head = $nextGroupHead;
        }

        return $protectedNode->next;
    }

    private function getEnd(?ListNode $head, int $k): ?ListNode
    {
        while ($head) {
            $k--;
            if($k == 0) break;
            $head = $head->next;
        }

        return $head;
    }

    private function reverseList(ListNode $head, ListNode $end): void
    {
        if($head === $end) return;

        $last = $head;
        $head = $head->next;

        while ($head !== $end) {
            $nextHead = $head->next;

            $head->next = $last;
            $last = $head;

            $head = $nextHead;
        }

        $end->next = $last;
    }
}