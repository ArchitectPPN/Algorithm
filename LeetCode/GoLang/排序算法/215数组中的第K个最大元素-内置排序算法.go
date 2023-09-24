package main

import "sort"

func main() {

}

// SortSolution 使用内置函数对切片进行排序后， 找第k大个数， 就是找第n-k个最小的数， n为数组的长度， 从数组末尾开始数
type SortSolution struct {
}

func (s *SortSolution) handle(nums []int, k int) int {
	if len(nums) == 0 {
		return -1
	}

	sort.Ints(nums)

	return nums[len(nums)-k]
}
