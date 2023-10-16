package main

import "fmt"

func main() {
	nums := []int{1, 3, 2, 4, 6, 1, 18, 10, 21, 19}

	handle := new(QuickSortRepeat1)
	handle.quickSort(nums, 0, len(nums)-1)

	fmt.Println("answer: ", nums)
}

type QuickSortRepeat1 struct {
}

// quickSort 快速排序
func (q *QuickSortRepeat1) quickSort(nums []int, left, right int) {
	// 左右相遇，结束
	if left == right {
		return
	}
	pivot := q.partition(nums, left, right)
	if pivot < left {
		return
	}

	q.quickSort(nums, left, pivot)
	q.quickSort(nums, pivot+1, right)
}

func (q *QuickSortRepeat1) partition(nums []int, left, right int) int {
	// 选举中间值比较值
	pivot := q.midPivot(left, right)
	pivotVal := nums[pivot]

	//
	for left <= right {
		// 向右移动
		for nums[left] < pivotVal {
			left += 1
		}

		// 向左移动
		for nums[right] > pivotVal {
			right -= 1
		}

		// 交换位置
		if left <= right {
			tmpVal := nums[left]
			nums[left] = nums[right]
			nums[right] = tmpVal

			// 移动
			left += 1
			right -= 1
		}
	}

	return right
}

// 使用中间值当作pivot
func (q *QuickSortRepeat1) midPivot(left, right int) int {
	return (left + right) >> 1
}
