<?php

class ListNode
{
    public $val = 0;
    public $next = null;

    function __construct($val = 0, $next = null)
    {
        $this->val = $val;
        $this->next = $next;
    }
}


class Solution
{
    public function twoNumberSum($link1, $link2)
    {
        $finalObj = null;

        $additional = 0;

        do {

            $value = $link1->val + $link2->val + $additional;
            // 判断是否大于10, 大于10 进位
            if ($value < 10) {
                $additional = 0;
            } else {
                $value -= 10;

                $additional = 1;
            }

            $tempLink = new ListNode($value);

            $next = $tempLink;
            if (is_null($finalObj)) {
                $finalObj = $tempLink;
            } else {
                $next->next = $tempLink;
            }

            $link1 = $link1->next;
            $link2 = $link2->next;
        } while ($link1 || $link2 || $additional);

        return $finalObj;
    }
}