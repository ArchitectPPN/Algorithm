<?php

namespace RemoveNthNodeFromEndOfList;

# [19. 删除链表的倒数第N个节点 ](https://leetcode-cn.com/problems/remove-nth-node-from-end-of-list)

class RemoveNthNodeFromEndOfListWithStack
{
    /*
     * 用栈删除
     */
    function removeNthFromEnd(?ListNode $head, int $n): ?ListNode
    {
        if (is_null($head)) {
            return null;
        }

        $dummy = new ListNode(0);
        $dummy->next = $head;

        // 入栈
        $stack = [];
        $current = $dummy; // 从dummy开始入栈
        while ($current) {
            $stack[] = $current;
            $current = $current->next;
        }

        // n大于链表长度, 说明要删除的节点不在数组中, 直接返回头节点
        // count($stack) - 1 去掉哑节点
        if ($n > count($stack) - 1) {
            return $dummy->next;
        }

        $preNode = new ListNode();
        // 出栈找到要删除节点的前一个节点
        for ($i = 0; $i <= $n; $i++) {
            $preNode = array_pop($stack);
        }

        $preNode->next = $preNode->next->next;

        return $dummy->next;
    }
}