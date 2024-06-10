<?php

namespace ReverseKGroup;


class ListNode
{
    /**
     * @var int
     */
    public int $val = 0;

    /**
     * @var ListNode|null
     */
    public null|ListNode $next = null;

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
     * @return \ReverseLinkedListLinkedList\ListNode|null
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

/**
 * 整体思路分为三部分：
 * 1. 分组，拿到每组的最后一个元素
 * 2. 对分组的链表进行反转
 * 3。处理反转后，需要处理两部分：
 *  1). 上一组的尾部和新一组头部的关联
 *  2). 新一组的尾部和下一组头部的关联
 * 4. 需要考虑第一组的情况是怎么样的？ 明天记得看下
 *  1. 初始状态：1->2->3->4->5->null, k=2
 *  2. 添加了保护点之后，变为：0->1->2->3->4->5->null, k=2
 *  3. 那么第一组的情况就会变为和正常的无差了
 */

class Solution {
    /**
     * @param ListNode|null $head
     * @param Integer $k
     * @return ListNode
     */
    function reverseKGroup(?ListNode $head, int $k): ListNode
    {
        $protectNode = new ListNode(0, $head);
        /**
         * 画个图可以看到， 假设当前k为2：
         * 初始: 1 -> 2 -> 3 -> 4 -> 5
         * 反转: 2 -> 1 -> 4 -> 3 -> 5
         * 我们可以得到一个信息， 初始的头变为反转的末尾【1->2 反转为 2 -> 1】 指向 初始的末尾反转的头【3 -> 4 反转为 4 -> 3】, 现在 【 1 -> 4】
         * 所以我们需要保留初始头部的信息， 以便和反转后的链表建立联系
         */
        $last = $protectNode;
        while($head != null) {
            // 获取当前这组的末尾
            $end = $this->getEnd($head, $k);
            // 判断结束条件
            if ($end == null) break;
            // 下一组开始的位置，因为end为当前组最后一个节点，那么下一组开始的节点就是end的next节点
            // 以便于我知道下一个节点从哪儿开始
            $nextGroupHead = $end->next;

            // 处理head到end之间的k-1条反转
            // 指向head的节点和end的next节点不关心，在下面进行处理
            $this->reverseList($head, $end);

            // 上一组跟本组的新head（之前旧的end）建立联系
            $last->next = $end;
            // 本组的新结尾(head)跟下一组的建立联系
            $head->next = $nextGroupHead;
            // 更新last，当前节点保存下来，以便后续使用
            $last = $head;
            // 继续往下一个节点处理
            $head = $nextGroupHead;
        }

        return $protectNode->next;
    }

    /**
     * 获取当前这组的末尾
     *
     * @param ListNode|null $head
     * @param int $k 多少组分割一次
     * @return ListNode|null
     */
    private function getEnd(?ListNode $head, int $k): ?ListNode
    {
        while($head != null) {
            $k--;
            if ($k == 0) break;
            $head = $head->next;
        }

        return $head;
    }

    /**
     * 对部分链表进行反转
     *
     * @param ListNode $head
     * @param ListNode $end
     * @return void
     */
    private function reverseList(ListNode $head, ListNode $end): void
    {
        if($head === $end) return;

        $last = $head;
        $head = $head->next;

        // 开始反转
        while ($head !== $end) {
            $nextHead = $head->next;
            // head 的next指向当前head
            // 举例:
            // 初始: 1 -> 2 -> 3
            // 反转: 1 <- 2 <- 3
            // 使用代码表达就是：
            // 第一步更新head, 当前head为1, 保留当前head, $lastHead = $head; $head = $head->next 现在head变为2
            // 然后head->next = $lastHead; 此时 1 <-> 2, 1 -> 2, 2 -> 1
            $head->next = $last;
            // 更新tail为当前节点
            $last = $head;
            // 指向下一个节点
            $head = $nextHead;
        }

        // 改变end的指向，走到这个位置，last必然为end之前的一个节点
        // 因为上面的循环会保证在end == head时结束，那么last会停留在end的上一个节点
        $end->next = $last;
    }
}

$linkedListAr = [1,2,3,4,5];
$builder = new BuildLinkedList();
$linkedList = $builder->buildLinkedList($linkedListAr);

$solution = new Solution();
$ans = $solution->reverseKGroup($linkedList, 2);

var_dump($ans);