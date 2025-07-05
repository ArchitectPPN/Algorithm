<?php

# [141. 环形链表](https://leetcode-cn.com/problems/linked-list-cycle)

namespace HasCycleSolution;

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