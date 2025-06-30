<?php

namespace ReverseList;

class ReverseLinkedListRecursion
{
    /**
     * @param ?ListNode $head
     * @return ?ListNode
     */
    public function reverseList(?ListNode $head): ?ListNode
    {
        if (is_null($head) || is_null($head->next)) {
            return $head;
        }

        // 递归翻转当前节点的下一个节点
        $newHead = $this->reverseList($head->next);

        // 翻转当前节点与下一个节点的指向
        // 下一个节点的next 指向当前节点
        $head->next->next = $head;
        $head->next = null;

        return $newHead;
    }
}