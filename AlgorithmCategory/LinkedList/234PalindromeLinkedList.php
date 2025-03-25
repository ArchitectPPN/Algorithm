<?php

# 234. 回文链表 https://leetcode-cn.com/problems/palindrome-linked-list

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

// 转数组后检查是否为回文链表
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
            if ($linkedArr[$i]->val !== $linkedArr[$length - $i - 1]->val) {
                return false;
            }
        }

        return true;
    }
}

class RecursivelyCheckSolution
{
    private ?ListNode $frontPointer = null;

    /**
     * @param ListNode|null $head
     * @return Boolean
     */
    function isPalindrome(?ListNode $head): bool
    {
        $this->frontPointer = $head;
        return $this->recursivelyCheck($head);
    }

    /**
     *
     *
     * @param ListNode|null $currentNode
     * @return bool
     */
    private function recursivelyCheck(?ListNode $currentNode): bool
    {
        if ($currentNode) {
            if (!$this->recursivelyCheck($currentNode->next)) {
                return false;
            }

            if ($this->frontPointer->val != $currentNode->val) {
                return false;
            }

            $this->frontPointer = $this->frontPointer->next;
        }

        return true;
    }
}