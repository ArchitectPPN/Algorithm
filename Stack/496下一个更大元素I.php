<?php

namespace NextGreaterElementSolution;

class HashSolution
{
    /**
     * @var int[] 数组元素索引
     */
    private array $nums2Index = [];

    /**
     * @param Integer[] $nums1
     * @param Integer[] $nums2
     * @return Integer[]
     */
    function nextGreaterElement(array $nums1, array $nums2): array
    {
        if (empty($nums1)) {
            return [];
        }

        $this->initIndex($nums2);

        $ans = [];
        foreach ($nums1 as $val) {
            $ans[] = $this->getNextGreaterElement($val, $nums2);
        }

        return $ans;
    }

    private function getNextGreaterElement(int $target, array $nums2): int
    {
        $index = $this->nums2Index[$target];
        $maxIndex = count($nums2) - 1;
        if ($index == $maxIndex) {
            return -1;
        }

        // 从下一个元素开始
        for($i = $index + 1; $i <= $maxIndex; $i++) {
            if($nums2[$i] > $target) {
                return $nums2[$i];
            }
        }

        return -1;
    }

    /**
     * 初始化数组下标索引
     *
     * @param array $nums2
     * @return void
     */
    private function initIndex(array $nums2): void
    {
        foreach ($nums2 as $index => $value) {
            $this->nums2Index[$value] = $index;
        }
    }
}

