<?php

# [19. 删除链表的倒数第N个节点 ](https://leetcode-cn.com/problems/remove-nth-node-from-end-of-list)

namespace RemoveNthNodeFromEndOfList;

/**
 * 将链表放到数组中, 这样就能根据下标去操作链表
 * @author niujunqing
 */
class RemoveNthNodeFromEndOfListWithRecursion
{

    /**
     * @param ?ListNode $head
     * @param Integer $n
     * @return ?ListNode
     */
    function removeNthFromEnd(?ListNode $head, int $n): ?ListNode
    {
        if (is_null($head)) {
            return null;
        }

        $dummy = new ListNode(0, $head);
        $dummy->next = $head;
        $this->getLength($dummy, $n);
        return $dummy->next;
    }

    /**
     * @param ListNode|null $head
     * @param int $n
     * @return int
     */
    private function getLength(?ListNode $head, int $n): int
    {
        if (is_null($head)) {
            return 0;
        }

        $pos = $this->getLength($head->next, $n);
        if ($pos === $n) {
            $head->next = $head->next->next;
        }

        return $pos + 1;
    }
}
