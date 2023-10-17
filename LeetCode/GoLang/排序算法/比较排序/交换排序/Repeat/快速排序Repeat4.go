package main

import (
	"fmt"
	"math/rand"
)

func main() {
	waitSortNums := []int{99, 100, 8, 21, 1, 0}

	solution := new(quickSortRepeat4)
	solution.quickSort(waitSortNums, 0, len(waitSortNums)-1)

	fmt.Println("ans:", waitSortNums)
}

type quickSortRepeat4 struct {
}

// quickSort
func (q *quickSortRepeat4) quickSort(nums []int, left, right int) {
	// 左右, 获取pivot会存在left>right的情况, 碰到这种场景, 需要直接跳出
	if left >= right {
		return
	}
	pivot := q.partition(nums, left, right)

	// left -- pivot 位置
	q.quickSort(nums, left, pivot)
	// pivot+1 -- right 位置
	q.quickSort(nums, pivot+1, right)
}

// partition
func (q *quickSortRepeat4) partition(nums []int, left, right int) int {
	pivot := q.getPivot(left, right)
	pivotVal := nums[pivot]

	// 交换左右两边的值,
	for left <= right {
		for nums[left] < pivotVal {
			left++
		}

		for nums[right] > pivotVal {
			right--
		}

		if left <= right {
			tmpVal := nums[left]
			nums[left] = nums[right]
			nums[right] = tmpVal

			left++
			right--
		}
	}

	return right
}

// 获取中间值
func (q *quickSortRepeat4) getPivot(left, right int) int {
	return left + rand.Intn(right-left+1)
}
