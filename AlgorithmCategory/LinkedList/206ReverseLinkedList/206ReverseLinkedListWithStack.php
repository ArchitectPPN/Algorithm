<?php

namespace ReverseList;

class ReverseLinkedListWithStack
{
    /**
     * @param ListNode $head
     * @return ListNode|null
     */
    public function reverseList(ListNode $head): ?ListNode
    {
        $stack = [];
        while ($head != null) {
            $stack[] = $head->val;
            $head = $head->next;
        }

        $prev = new ListNode(-1);
        $tmp = $prev;
        while (!empty($stack)) {
            $val = array_pop($stack);
            $node = new ListNode($val);
            $tmp->next = $node;
        }

        return $prev->next;
    }
}