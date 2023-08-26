package main

import (
	"fmt"
	"math"
)

func main() {
	searchSolution := new(SearchSolution)
	ans := searchSolution.handle([]int{-1, 0, 3, 5, 9, 12}, 12)

	fmt.Println(ans)
}

type SearchSolution struct {
}

func (s *SearchSolution) handle(nums []int, target int) int {
	return s.search(nums, target)
}

func (s *SearchSolution) search(nums []int, target int) int {
	// 数组长度小于1，说明没有元素，直接返回未找到即可
	if len(nums) < 1 {
		return -1
	}

	var minIndex, maxIndex, midIndex int
	minIndex = 0
	maxIndex = len(nums) - 1

	// 最小下标小于最大下标时，一直循环， 直到不小于， 要么未找到，要么找到了
	for minIndex <= maxIndex {
		// 获取中间的下标
		midIndex = int(math.Ceil(float64((minIndex + maxIndex) / 2)))
		if nums[midIndex] == target {
			return midIndex
		} else if nums[midIndex] < target {
			// 中间值小于目标，说明需要向右移动
			minIndex = midIndex + 1
		} else {
			maxIndex = midIndex - 1
		}
	}

	return -1
}
