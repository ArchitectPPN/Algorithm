<?php

namespace ReverseLinkedListLinkedList;


//leetcode submit region begin(Prohibit modification and deletion)

/**
 * Definition for a singly-linked list.
 * class ListNode {
 *     public $val = 0;
 *     public $next = null;
 *     function __construct($val = 0, $next = null) {
 *         $this->val = $val;
 *         $this->next = $next;
 *     }
 * }
 */
class ListNode
{
    /**
     * @var int
     */
    public int $val = 0;

    /**
     * @var ListNode|null
     */
    public ListNode|null $next = null;

    /**
     * @param int $val
     * @param ListNode|null $next
     */
    function __construct(int $val = 0, ?ListNode $next = null)
    {
        $this->val = $val;
        $this->next = $next;
    }
}

/**
 * 构建一个链表
 * @author niujunqing
 */
class BuildLinkedList
{

    /**
     * 构建一个链表
     * @param array $arr
     * @return ListNode|null
     */
    public function buildLinkedList(array $arr): ?ListNode
    {
        if (empty($arr)) {
            return null;
        }

        // 创建一个空的头节点
        $head = new ListNode();
        // 头节点复制到临时节点上
        $temp = $head;
        foreach ($arr as $value) {
            $temp->next = new ListNode($value);
            $temp = $temp->next;
        }

        return $head->next;
    }
}

$question = [
    1,
    2,
    3,
    4,
    5,
];
$buildLinkedList = new BuildLinkedList();
$linkedList = $buildLinkedList->buildLinkedList($question);
var_dump($linkedList);

class ReverseList
{
    /**
     * 反转链表
     *
     * @param ListNode|null $head
     * @return ListNode|null
     */
    public function reverseList(?ListNode $head): ?ListNode
    {
        if (is_null($head)) {
            return $head;
        }

        return $head;
    }
}

class Solution
{

    /**
     * @param ListNode $head
     * @return ListNode
     */
    function reverseList($head)
    {

    }
}