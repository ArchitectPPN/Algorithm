<?php

namespace PalindromeLinkedList;

class ListNode
{
    public int $val = 0;
    public ListNode|null $next = null;

    function __construct($val = 0, $next = null)
    {
        $this->val = $val;
        $this->next = $next;
    }
}

/**
 * 解体思路：
 *  由于回文链表的特性，头尾的值是一样的，所以我们把链表从头到尾依次入栈，
 *  然后再依次比较栈顶元素和链表从头到尾的值，如果不一样，说明不是回文链表。
 * 例如：1221 入栈后为 1221，依次比较，值是一样的，所以是回文链表
 */
class Solution
{
    /**
     * @param ?ListNode $head
     * @return Boolean
     */
    function isPalindrome(?ListNode $head): bool
    {
        // 如果头节点为null，返回true
        if (is_null($head)) {
            return true;
        }
        $stack = [];
        $tmp = $head;
        while (!is_null($head)) {
            $stack[] = $head->val;
            $head = $head->next;
        }

        while (!is_null($tmp)) {
            $stackTop = array_pop($stack);
            if ($stackTop != $tmp->val) {
                return false;
            }
            $tmp = $tmp->next;
        }

        return true;
    }
}
