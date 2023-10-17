package main

import "fmt"

func main() {
	solution := new(MergeSortSolution)
	solution.handle()
}

type MergeSortSolution struct {
}

func (m *MergeSortSolution) handle() {
	waitSortNums := []int{9, 8, 7, 6, 5}

	arr := m.mergeSort(waitSortNums, 0, len(waitSortNums)-1)

	fmt.Println("arr:", arr)
}

// mergeSort
func (m *MergeSortSolution) mergeSort(arr []int, left, right int) []int {
	if left >= right {
		return arr
	}

	var mid int
	// 求平均数向下取整
	mid = (left + right) >> 1
	// 合并[left,mid]
	m.mergeSort(arr, left, mid)
	// 合并[mid+1, right]
	m.mergeSort(arr, mid+1, right)

	m.merge(arr, left, mid, right)

	return arr
}

func (m *MergeSortSolution) merge(arr []int, left, mid, right int) {
	// 声明临时数组, 用来存储
	var temp []int
	var min, max int
	min = left
	max = mid + 1
	temp = make([]int, right-left+1)

	// 合并两个有序数组
	for k := 0; k < len(temp); k++ {
		if max > right || min <= mid && arr[min] <= arr[max] {
			temp[k] = arr[min]
			min += 1
		} else {
			temp[k] = arr[max]
			max += 1
		}
	}

	// 将排好序的分组写入原先的数组
	for k := 0; k < len(temp); k++ {
		arr[left+k] = temp[k]
	}
}
