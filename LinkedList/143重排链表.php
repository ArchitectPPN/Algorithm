<?php

namespace ReorderList;

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

/**
 * 线性表解题方法
 *
 * 思路：
 * 1. 将链表转为线性数组，转为数组时，清除掉next指向，防止其影响结果，这样可以使用下标来快速访问每个节点的元素
 * 2. 通过节点下标来重新构建链表
 *
 */
class LinearListSolution
{
    /**
     * @param ?ListNode $head
     * @return NULL
     */
    public function reorderList(?ListNode $head)
    {
        // head为null时，直接返回
        if (is_null($head)) {
            return;
        }

        // 存储链表的每个节点，给节点加上下标，加快访问节点的速度
        $linerList = [];
        // 临时变量
        $tmp = new ListNode();
        while ($head) {
            // 取出head的下一个节点
            $p = $head->next;
            // head->next指向null
            $head->next = null;
            $linerList[] = $head;

            // 继续循环下一个节点
            $head = $p;
        }

        $left = 0;
        $right = count($linerList) - 1;
        while ($left < $right) {
            // 第一个元素指向最后一个元素 L0->Ln
            $linerList[$left]->next = $linerList[$right];
            // 防止成环
            if ($left + 1 != $right) $linerList[$right]->next = $linerList[$left + 1];

            $left++;
            $right--;
        }

        return;
    }
}