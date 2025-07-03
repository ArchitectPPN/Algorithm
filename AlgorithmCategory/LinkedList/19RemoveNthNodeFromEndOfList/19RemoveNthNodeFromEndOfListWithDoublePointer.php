<?php

namespace RemoveNthNodeFromEndOfList;

/**
 * 1. 核心思想
 *  使用双指针法（快慢指针）从链表末尾删除第 n 个节点，关键步骤如下：
 *  让第一个指针（first）先移动 n 步，与第二个指针（second）形成 n 步的距离。
 *  当第一个指针到达链表末尾时，第二个指针正好指向要删除节点的前一个节点。
 *  通过调整指针指向，删除目标节点。
 * 2. 关键步骤
 *  哑节点初始化：创建值为 0 的哑节点（dummy），其next指向头节点，避免处理头节点删除的特殊情况。
 *  指针移动：
 *  first指针先移动 n 步，作为 “领先指针”。
 *  然后first和second指针同时移动，直到first指向null（链表末尾）。
 *  删除节点：此时second指向要删除节点的前一个节点，通过$second->next = $second->next->next完成删除。
 * 3. 边界条件处理
 *  头节点删除：若要删除的是头节点（如链表长度为 n），哑节点确保second指针能正确指向头节点的前一个位置（哑节点自身）。
 *  空链表处理：若输入链表为空，$head为null，但题目保证输入有效，实际使用中可添加额外判断。
 *
 * @author niujunqing
 */
class RemoveNthNodeFromEndOfListWithDoublePointer
{
    /*
     * 双指针删除法
     */
    function removeNthFromEnd(?ListNode $head, int $n): ?ListNode
    {
        if (is_null($head)) {
            return null;
        }

        // 创建哑节点，方便处理头节点删除的情况
        $dummy = new ListNode(0, $head);
        $first = $head;
        $second = $dummy;

        // 让first指针先移动n步
        for ($i = 0; $i < $n; $i++) {
            if ($first !== null) {
                $first = $first->next;
            }
        }

        // 同时移动first和second指针，直到first指向null
        while ($first !== null) {
            $first = $first->next;
            $second = $second->next;
        }

        // 删除目标节点
        $second->next = $second->next->next;

        // 获取结果链表（哑节点的下一个节点）
        // PHP无需手动释放内存，此处省略删除操作
        return $dummy->next;
    }
}