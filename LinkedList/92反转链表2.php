<?php

namespace ReverseBetween;

class ListNode
{
    public int $val = 0;
    public ListNode|null $next = null;

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

class Solution
{
    /**
     * 查看题解： https://leetcode.cn/problems/reverse-linked-list-ii/solutions/634701/fan-zhuan-lian-biao-ii-by-leetcode-solut-teyq/
     *
     * @param ?ListNode $head
     * @param Integer $left
     * @param Integer $right
     * @return ListNode|null
     */
    function reverseBetween(?ListNode $head, int $left, int $right): ?ListNode
    {
        // 建立一个保护节点
        $dummyNode = new ListNode(-1);
        $dummyNode->next = $head;

        $prev = $dummyNode;
        // 找到left前一个节点
        for ($i = 0; $i < $left - 1; $i++) {
            $prev = $prev->next;
        }

        // 找到right前一个节点
        $rightNode = $prev;
        for ($i = 0; $i < $right - $left + 1; $i++) {
            $rightNode = $rightNode->next;
        }

        // 切断出一个链表(截取链表)
        $leftNode = $prev->next;
        $curr = $rightNode->next;

        // 切断链接
        $prev->next = null;
        $rightNode->next = null;

        $this->reverseLinkedList($leftNode);

        // 链接三个部分, 这里rightNode已经被反转了
        $prev->next = $rightNode;
        $leftNode->next = $curr;

        return $dummyNode->next;
    }

    /**
     * @param ListNode $head
     * @return void
     */
    private function reverseLinkedList(ListNode $head): void
    {
        $pre = null;
        $cur = $head;
        while ($cur) {
            // 保留下一个节点
            $next = $cur->next;

            // 断开之前的链接
            $cur->next = $pre;
            // 更新pre
            $pre = $cur;

            // 继续循环下一个节点
            $cur = $next;
        }
    }
}

class ReverseOneTimeSolution
{
    /**
     * 查看题解： https://leetcode.cn/problems/reverse-linked-list-ii/solutions/634701/fan-zhuan-lian-biao-ii-by-leetcode-solut-teyq/
     *
     * @param ?ListNode $head
     * @param Integer $left
     * @param Integer $right
     * @return ListNode|null
     */
    function reverseBetween(?ListNode $head, int $left, int $right): ?ListNode
    {

    }

    /**
     * @param ListNode $head
     * @return void
     */
    private function reverseLinkedList(ListNode $head): void
    {
        $pre = null;
        $cur = $head;
        while ($cur) {
            // 保留下一个节点
            $next = $cur->next;

            // 断开之前的链接
            $cur->next = $pre;
            // 更新pre
            $pre = $cur;

            // 继续循环下一个节点
            $cur = $next;
        }
    }
}