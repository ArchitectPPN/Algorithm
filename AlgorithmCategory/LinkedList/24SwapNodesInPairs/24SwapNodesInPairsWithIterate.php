<?php

# [24. 两两交换链表中的节点](https://leetcode-cn.com/problems/swap-nodes-in-pairs)

namespace SwapPairsSolution;

require_once "ListNode.php";

/**
 * 迭代算法
 */
class SwapNodesInPairsWithIterate
{
    /**
     * @param ?ListNode $head
     * @return ?ListNode
     */
    function swapPairs(?ListNode $head): ?ListNode
    {
        if (is_null($head)) {
            return null;
        }

        $dummy = new ListNode(0, $head);
        $tmpHead = $dummy;

        while($tmpHead->next && $tmpHead->next->next) {
            $nodeOne = $tmpHead->next;
            $nodeTwo = $tmpHead->next->next;

            // 当前节点指向下一个节点
            $tmpHead->next = $nodeTwo;
            // 当前节点的下一个节点指向当前节点的下下个节点
            $nodeOne->next = $nodeTwo->next;
            // 当前节点的下下个节点指向当前节点
            $nodeTwo->next = $nodeOne;

            $tmpHead = $nodeOne;
        }

        return $dummy->next;
    }
}

$buildListNode = new BuildLinkedList();
$listNode = $buildListNode->buildLinkedList([1, 2, 3, 4]);

$svc = new SwapNodesInPairsWithIterate();
$svc->swapPairs($listNode);