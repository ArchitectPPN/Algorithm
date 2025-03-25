<?php

# [143. 重排链表](https://leetcode-cn.com/problems/reorder-list)

namespace ReorderList;

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

/**
 * 线性表解题方法
 *
 * 思路：
 * 1. 将链表转为线性数组，转为数组时，清除掉next指向，防止其影响结果，这样可以使用下标来快速访问每个节点的元素
 * 2. 通过节点下标来重新构建链表
 *
 */
class LinearListSolution
{
    /**
     * @param ?ListNode $head
     * @return NULL
     */
    public function reorderList(?ListNode $head)
    {
        // head为null时，直接返回
        if (is_null($head)) {
            return;
        }

        // 存储链表的每个节点，给节点加上下标，加快访问节点的速度
        $linerList = [];
        // 临时变量
        $tmp = new ListNode();
        while ($head) {
            // 取出head的下一个节点
            $p = $head->next;
            // head->next指向null
            $head->next = null;
            $linerList[] = $head;

            // 继续循环下一个节点
            $head = $p;
        }

        $left = 0;
        $right = count($linerList) - 1;
        while ($left < $right) {
            // 第一个元素指向最后一个元素 L0->Ln
            $linerList[$left]->next = $linerList[$right];
            // 防止成环
            if ($left + 1 != $right) $linerList[$right]->next = $linerList[$left + 1];

            $left++;
            $right--;
        }

        return;
    }
}

/**
 * 解题思路 寻找中间节点 + 反转链表 + 合并链表
 * 1. 寻找中间节点：使用快慢指针来做
 */
class FindMidAndReverseLinkedAndMergeLinkedListSolution
{
    /**
     * @param ?ListNode $head
     * @return NULL
     */
    public function reorderList(?ListNode $head)
    {
        if (is_null($head)) {
            return;
        }

        // 拿到中间节点
        $midNode = $this->findMid($head);
        $l1 = $head;
        // l2为中间节点的下一个节点， 也就是l2从中间节点的下一个节点开始
        $l2 = $midNode->next;
        // 将中间节点的下一个节点指向null
        $midNode->next = null;

        // 反转l2，如果只有一个元素时，l2一定为null例如：[1] 1 -> null
        $l2 = $this->reverseLinkedList($l2);

        // 合并链表
        $this->mergeLinked($l1, $l2);

        return ;
    }

    /**
     * 找到中间节点
     *
     * @param ListNode|null $head
     * @return ListNode|null
     */
    private function findMid(?ListNode $head): ?ListNode
    {
        // 一开始两者都指向头节点
        $quick = $slow = $head;
        while ($quick && $quick->next) {
            // 一次走一步
            $slow = $slow->next;
            // 一次走两步
            $quick = $quick->next->next;
        }

        return $slow;
    }

    /**
     * 这段代码解释请查看206题，反转的链表
     *
     * @param ListNode|null $head
     * @return ?ListNode
     */
    private function reverseLinkedList(?ListNode $head): ?ListNode
    {
        // 临时变量
        $tail = null;

        while ($head) {
            $next = $head->next;

            // 指向新的节点
            $head->next = $tail;
            $tail = $head;

            $head = $next;
        }

        return $tail;
    }

    /**
     * 合并两个链表
     *
     * @param ListNode $linked1
     * @param ?ListNode $linked2
     * @return void
     */
    private function mergeLinked(ListNode $linked1, ?ListNode $linked2): void
    {
        $head1 = $head2 = null;

        // 两个节点都不为null时循环
        while($linked1 && $linked2) {
            $head1 = $linked1->next;
            $head2 = $linked2->next;

            // 链表1的下一个节点指向链表2
            // 1->5->4->null
            $linked1->next = $linked2;
            // 2->3->4->5->null;
            $linked1 = $head1;

            // 5->2->3->4->null
            $linked2->next = $linked1;
            $linked2 = $head2;
        }
    }
}