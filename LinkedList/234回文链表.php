<?php

namespace IsPalindrome;

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

class TranslateArraySolution
{
    /**
     * @param ListNode|null $head
     * @return Boolean
     */
    function isPalindrome(?ListNode $head): bool
    {
        if (is_null($head)) {
            return false;
        }

        $linkedArr = [];
        while ($head) {
            $linkedArr[] = $head;
            $head = $head->next;
        }

        $length = count($linkedArr);
        for ($i = 0; $i < $length / 2; $i++) {
            if($linkedArr[$i]->val !== $linkedArr[$length - $i - 1]->val) {
                return false;
            }
        }

        return true;
    }
}