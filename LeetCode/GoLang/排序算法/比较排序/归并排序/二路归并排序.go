package main

import "fmt"

func main() {
	solution := new(MergeSortSolution)
	solution.handle()
}

type MergeSortSolution struct {
}

func (m *MergeSortSolution) handle() {
	m.mergeSort([]int{9, 8, 7, 6, 5}, 0, 4)
}

func (m *MergeSortSolution) mergeSort(arr []int, left, right int) {
	if left >= right {
		return
	}

	var mid int
	// 求平均数向下取整
	mid = (left + right) >> 1
	m.mergeSort(arr, left, mid)
	m.mergeSort(arr, mid+1, right)
	m.merge(arr, left, mid, right)

	fmt.Println(arr)
}

func (m *MergeSortSolution) merge(arr []int, left, mid, right int) {
	var temp []int
	var i, j int
	i = left
	j = mid + 1
	temp = make([]int, right-left+1)

	for k := 0; k < len(temp); k++ { // 合并两个有序数组
		if j > right || i <= mid && arr[i] <= arr[j] {
			temp[k] = arr[i]
			i += 1
		} else {
			temp[k] = arr[j]
			j += 1
		}
	}

	for k := 0; k < len(temp); k++ {
		arr[left+k] = temp[k]
	}
}
