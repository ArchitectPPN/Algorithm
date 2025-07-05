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