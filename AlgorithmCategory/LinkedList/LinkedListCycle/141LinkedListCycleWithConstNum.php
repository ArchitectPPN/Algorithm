<?php

# [141. 环形链表](https://leetcode-cn.com/problems/linked-list-cycle)

namespace HasCycleSolution;

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
