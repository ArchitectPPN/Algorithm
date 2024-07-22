<?php

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
