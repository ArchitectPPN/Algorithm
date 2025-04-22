<?php

# 349. 两个数组的交集 https://leetcode.cn/problems/intersection-of-two-arrays/description/

/**
 * THINKING
 * 将nums1中的所有元素都添加到hash表中，再遍历nums2，可以用O(1)的时间复杂度判断nums2的元素是否在ht中。
 * 若存在，将其添加到ans中，添加之后，要记得删除ht中的元素，避免后续nums2中有相同元素时重复计算
 */
class HashIntersectionOfTwoArrays
{
    /**
     * @param Integer[] $nums1
     * @param Integer[] $nums2
     * @return Integer[]
     */
    function intersection(array $nums1, array $nums2): array
    {
        $ht = [];
        // 把nums1中的元素加入hash表中
        foreach ($nums1 as $val) {
            $ht[$val] = true;
        }

        $ans = [];
        foreach ($nums2 as $val) {
            if (isset($ht[$val])) {
                // 移除掉都有的元素， 防止添加重复的元素
                unset($ht[$val]);
                $ans[] = $val;
            }
        }
        return $ans;
    }
}