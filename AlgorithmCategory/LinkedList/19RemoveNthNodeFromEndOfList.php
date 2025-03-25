<?php

# [19. 删除链表的倒数第N个节点 ](https://leetcode-cn.com/problems/remove-nth-node-from-end-of-list)

namespace RemoveNthFromEndSolution;

class ListNode
{
    public int $val = 0;
    public ?ListNode $next = null;

    function __construct(int $val = 0, ?ListNode $next = null)
    {
        $this->val = $val;
        $this->next = $next;
    }
}

class Solution
{

    /**
     * 思路: 将链表放到数组中, 这样就能根据下标去操作链表
     * 时间复杂度: O(n)
     * 需要注意:
     * 1. 删除节点的前一个节点不存在
     * 2. 删除节点的后一个节点不存在
     * 3. 前后节点都存在
     *
     * @param ListNode $head
     * @param Integer $n
     * @return ?ListNode
     */
    function removeNthFromEnd(ListNode $head, int $n): ?ListNode
    {
        // 不删除, 直接返回
        if ($n == 0) {
            return $head;
        }

        $protectedNode = $head;
        $hashTable = [];
        while ($head) {
            $hashTable[] = $head;
            $head = $head->next;
        }

        $totalINum = count($hashTable);
        $delIndex = $totalINum - $n;

        // 删除的节点不在数组中, 直接返回头节点
        if (!isset($hashTable[$delIndex])) {
            return $protectedNode;
        } elseif (!isset($hashTable[$delIndex - 1])) {
            // 删除节点的前一个节点不存在
            return $protectedNode->next;
        } elseif (!isset($hashTable[$delIndex + 1])) {
            // 删除节点的后一个节点不存在
            $hashTable[$delIndex - 1]->next = null;
        } else {
            $hashTable[$delIndex - 1]->next = $hashTable[$delIndex]->next;
        }

        return $protectedNode;
    }
}
