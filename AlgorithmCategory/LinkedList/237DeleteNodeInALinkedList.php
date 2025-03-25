<?php

# 237. 删除链表中的节点 https://leetcode.cn/problems/delete-node-in-a-linked-list/description/

namespace DeleteNode;

class ListNode
{
    public int $val = 0;
    public ?ListNode $next = null;

    /**
     * @param int $val
     */
    function __construct(int $val)
    {
        $this->val = $val;
    }
}

class Solution
{
    /**
     * 删除节点：
     * 1. 题目要求，下一个节点一定不是null
     * 2. 所以我们删除节点，就可以直接将当前节点得值改为当前节点指向的下一节点的值
     * 3. 然后将当前节点指向的下一节点设置为当前节点的下个节点
     * 示例：
     * 1->2->3->4->null
     * 要删除2
     * 我们可以将2的值改为3
     * 1->3->3->4->null
     * 然后将2指向4
     * 1->3->4->null
     *
     * @param ListNode $node
     * @return ListNode
     */
    function deleteNode(ListNode $node): ListNode
    {
        $node->val = $node->next->val;
        $node->next = $node->next->next;

        return $node;
    }
}