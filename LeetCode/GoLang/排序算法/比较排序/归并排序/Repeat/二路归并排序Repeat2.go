package main

import "fmt"

func main() {
	waitSortArr := []int{3, 5, 6, 1, 2, 8, 9}

	solution := new(MergeSortSolutionRepeat2)
	solution.mergeSort(waitSortArr, 0, len(waitSortArr)-1)

	fmt.Println("answer:", waitSortArr)
}

type MergeSortSolutionRepeat2 struct {
}

// mergeSort
func (m *MergeSortSolutionRepeat2) mergeSort(nums []int, left, right int) {
	// left == right时, 说明只有一个数字, 无需排序
	// 如果left > right时, 说明已经到末尾了, 无需排序
	if left >= right {
		return
	}

	// 获取中间值, 通过该值将数组分为两组
	mid := (left + right) >> 1

	// 处理left -- mid之间的排序
	m.mergeSort(nums, left, mid)
	// 处理mid+1 -- 到right之间的排序
	m.mergeSort(nums, mid+1, right)

	// 合并两个数组
	m.merge(nums, left, mid, right)
}

// 合并两个数组
func (m *MergeSortSolutionRepeat2) merge(nums []int, left, mid, right int) {
	// 从left开始
	min := left
	// max从mid+1开始
	max := mid + 1

	// 初始化一个临时数组存放排好序的数组
	var tmpVal []int
	tmpVal = make([]int, (right-left)+1)

	for i := 0; i < len(tmpVal); i++ {
		if max > right || min <= mid && nums[min] <= nums[max] {
			tmpVal[i] = nums[min]
			min++
		} else {
			tmpVal[i] = nums[max]
			max++
		}
	}

	for k := 0; k < len(tmpVal); k++ {
		nums[left+k] = tmpVal[k]
	}
}
