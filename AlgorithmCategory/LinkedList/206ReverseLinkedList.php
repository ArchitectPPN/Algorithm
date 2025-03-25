<?php

# 206. 反转链表 https://leetcode-cn.com/problems/reverse-linked-list

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
 * 这里我们假设有一个链表: 1->2->3->4->null
 * 1. 我们要反转链表，需要一个变量来存储反转后的节点， 这里使用tail来存储， 一开始tail没有指向任何一个节点，所以它的值为null；
 * 2. 循环过程中需要一个临时变量来存储当前节点的下一个节点，用来向后循环节点，所以这里使用tmpHead来存储；
 * 3. 由于需要反转链表， 这里将当前节点与它的下一个节点断开连接， 这里使用head->next = $tail， 第一个元素时等价于head->next = null， 然后我们更新tail，tail=head；这样tail就保存了反转后的链表；
 * 用例子来解释： 原先的链表是1->2->3->4->null
 * 走到第一步时， tail = 1 -> null, 原先的链表为：2->3->4->null;
 * 接着循环2步时， tail = 2 -> 1 -> null, 原先的链表为：3->4->null;
 * 循环3步时， tail = 3 -> 2 -> 1 -> null, 原先的链表为：4->null;
 * 循环4步时， tail = 4 -> 3 -> 2 -> 1 -> null, 原先的链表为：null;
 *
 * 这个解法看起来就是，在循环过程中，一个一个处理当前节点：
 * 1. 将当前节点与之前的下一个节点断开连接；
 * 2. 将当前节点指向新的节点，指向新的节点就是断开之前节点的操作；
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