package main

import "fmt"

func main() {
	solution := new(MergeSortSolution)
	solution.handle()
}

type MergeSortSolution struct {
}

func (m *MergeSortSolution) handle() {
	arr := m.mergeSort([]int{9, 8, 7, 6, 5}, 0, 4)
	fmt.Println("arr:", arr)
}

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
	minVal := left
	maxVal := mid + 1
	temp = make([]int, right-left+1)

	// 合并两个有序数组
	for k := 0; k < len(temp); k++ {
		// 由于min==left，所以i的值一定小于mid，所以在min<=mid并且min<max时，将小的值放入到临时数组重
		// max是中间值+1得到的，max>right就说明，已经走到数组的末尾，所以直接将i的值放入临时数据就可以
		// 举个例子：
		// [3, 4, 5, 1, 2], mid为2，max一定会大于right，所以将mid之前的数字依次放入临时数组重即可
		if maxVal > right || minVal <= mid && arr[minVal] <= arr[maxVal] {
			temp[k] = arr[minVal]
			minVal += 1
		} else {
			temp[k] = arr[maxVal]
			maxVal += 1
		}
	}

	// 将排好序的分组写入原先的数组
	for k := 0; k < len(temp); k++ {
		arr[left+k] = temp[k]
	}
}
