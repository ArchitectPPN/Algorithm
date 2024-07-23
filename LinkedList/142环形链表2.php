<?php

namespace HasCycleIISolution;

class ListNode
{
    public int $val = 0;

    public ?ListNode $next = null;

    function __construct($val)
    {
        $this->val = $val;
    }
}

class HashSetSolution
{
    /**
     * @param ?ListNode $head
     * @return ?ListNode
     */
    function detectCycle(?ListNode $head): ?ListNode
    {
        if (is_null($head)) {
            return null;
        }

        $hashSet = [];
        while ($head) {
            $nodeHash = spl_object_hash($head);
            if (isset($hashSet[$nodeHash])) {
                return $hashSet[$nodeHash];
            }

            $hashSet[$nodeHash] = $head;
            $head = $head->next;
        }

        return null;
    }
}

class QuickSlowSolution
{
    /**
     * @param ?ListNode $head
     * @return ?ListNode
     */
    function detectCycle(?ListNode $head): ?ListNode
    {
        if (is_null($head)) {
            return null;
        }

        // 3    2    0    -4
        //      |__________|
    //  f                  |
    //  s                  |

        $slow = $quick = $head;
        while ($slow && $quick->next) {
            $slow = $slow->next;
            $quick = $quick->next->next;
            if ($quick === $slow) {
                break;
            }
        }

        if ($quick->next && $slow) {
            // 快指针从头开始
            $quick = $head;
            while ($quick != $slow) {
                $quick = $quick->next;
                $slow = $slow->next;
            }

            return $quick;
        }

        return null;
    }
}