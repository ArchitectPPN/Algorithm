<?php

# [141. 环形链表](https://leetcode-cn.com/problems/linked-list-cycle)

namespace HasCycleSolution;

class ListNode
{
    public int $val = 0;

    public ?ListNode $next = null;

    function __construct($val)
    {
        $this->val = $val;
    }
}

class HashSolution
{
    /**
     * hash set解法
     *
     * @param ?ListNode $head
     * @return Boolean
     */
    function hasCycle(?ListNode $head): bool
    {
        if (is_null($head)) {
            return false;
        }

        $hashSet = [];

        while ($head) {
            // 获取对象得唯一标识
            // php7之后可以使用 spl_object_id
            $nodeHash = spl_object_hash($head);
            if (isset($hashSet[$nodeHash])) {
                return true;
            }
            $hashSet[$nodeHash] = 1;

            $head = $head->next;
        }

        return false;
    }
}

class QuickAndSlowSolution
{
    /**
     * 快慢指针解法
     *
     * @param ?ListNode $head
     * @return Boolean
     */
    function hasCycle(?ListNode $head): bool
    {
        if (is_null($head)) {
            return false;
        }

        // 快慢指针解法
        $slow = $quick = $head;

        while ($slow && $quick->next) {
            // 这里一定是先向前走一步， 然后再判断是否相等
            $slow = $slow->next;
            $quick = $quick->next->next;

            // 如果先判断然后再执行，  1->2->1 这种，就不会再进入第二次循环了， 所以就无法检测到

            if ($slow === $quick) {
                return true;
            }
        }

        return false;
    }
}


class ConstNumSolution
{
    /**
     * 常数解法
     *
     * @param ?ListNode $head
     * @return Boolean
     */
    function hasCycle(?ListNode $head): bool
    {
        if (is_null($head)) {
            return false;
        }

        // 因为题目条件， 说明链表长度小于10000
        $count = 10001;
        while ($count) {
            // 没有环得情况下， 链表一定不会循环超过count
            // 1->2->3 循环2次就结束了，然后就返回了， 说明没有环
            if (is_null($head)) {
                return false;
            }

            $head = $head->next;
            $count--;
        }

        // 如果链表长度大于10000， 说明有环
        return true;
    }
}
