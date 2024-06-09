<?php

namespace ReverseList;

class ListNode
{
    /**
     * @var int
     */
    public int $val = 0;

    /**
     * @var ListNode|null
     */
    public null|ListNode $next = null;

    /**
     * @param int $val
     * @param ListNode|null $next
     */
    function __construct(int $val = 0, ?ListNode $next = null)
    {
        $this->val = $val;
        $this->next = $next;
    }
}

/**
 * 构建一个链表
 * @author niujunqing
 */
class BuildLinkedList
{

    /**
     * 构建一个链表
     * @param array $arr
     * @return \ReverseLinkedListLinkedList\ListNode|null
     */
    public function buildLinkedList(array $arr): ?ListNode
    {
        if (empty($arr)) {
            return null;
        }

        // 创建一个空的头节点
        $head = new ListNode();
        // 头节点复制到临时节点上
        $temp = $head;
        foreach ($arr as $value) {
            $temp->next = new ListNode($value);
            $temp = $temp->next;
        }

        return $head->next;
    }
}

/**
 * 反转链表
 */
class Solution {
    /**
     * 反转链表
     *
     * @param ListNode $head
     * @return ListNode|null
     */
    function reverseList(ListNode $head): ?ListNode
    {
        $tail = null;

        while ($head != null) {
            // 暂时先存储当前head的next
            $tmpHead = $head->next;

            // 断开next指向的节点
            $head->next = $tail;
            // 当前节点赋值给$tail，以便下一个head->next指向当前head
            $tail = $head;

            // 指向下一个节点
            $head = $tmpHead;
        }

        return $tail;
    }
}