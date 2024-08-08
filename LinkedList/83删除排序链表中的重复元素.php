<?php

namespace DeleteDuplicatesSolution;

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


class Solution
{
    /**
     * @param ?ListNode $head
     * @return ?ListNode
     */
    function deleteDuplicates(?ListNode $head): ?ListNode
    {
        if (is_null($head)) {
            return null;
        }

        $cur = $head;

        while ($cur->next) {
            if ($cur->val == $cur->next->val) {
                $cur->next = $cur->next->next;
            } else {
                $cur = $cur->next;
            }
        }

        return $head;
    }
}