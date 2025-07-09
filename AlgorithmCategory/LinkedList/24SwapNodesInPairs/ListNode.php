<?php

namespace SwapPairsSolution;

class ListNode
{
    public int $val = 0;
    public ListNode|null $next = null;

    function __construct($val = 0, $next = null)
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
     * @return ListNode
     */
    public function buildLinkedList(array $arr): ListNode
    {
        if (empty($arr)) {
            return new ListNode();
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