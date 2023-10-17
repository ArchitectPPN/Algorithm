package main

import "fmt"

func main() {
	waitSortArr := []int{3, 5, 6, 1, 2, 8, 9}

	solution := new(MergeSortSolutionRepeat1)
	solution.mergeSort(waitSortArr, 0, len(waitSortArr)-1)

	fmt.Println("answer:", waitSortArr)
}

type MergeSortSolutionRepeat1 struct {
}

// mergeSort 排序
func (m *MergeSortSolutionRepeat1) mergeSort(nums []int, left, right int) {
	// 终止条件
	if left >= right {
		return
	}

	// 获取中值, 取平均值
	mid := (left + right) >> 1
	// 处理left到mid之间的值
	m.mergeSort(nums, left, mid)
	// 处理mid+1到right的值
	m.mergeSort(nums, mid+1, right)

	// 最后合并有序数组
	m.merge(nums, left, mid, right)
}

// merge 合并有序数组
func (m *MergeSortSolutionRepeat1) merge(nums []int, left, mid, right int) {
	// 声明一个临时数组来存放排好序的数据
	var tempSlice []int
	minIndex := left
	maxIndex := mid + 1
	// 初始化切片的长度，刚好可以放下两组数据
	tempSlice = make([]int, right-left+1)

	// 对数组进行排序，放入临时数组
	for k := 0; k < len(tempSlice); k++ {
		if maxIndex > right || minIndex <= mid && nums[minIndex] <= nums[maxIndex] {
			tempSlice[k] = nums[minIndex]
			minIndex++
		} else {
			tempSlice[k] = nums[maxIndex]
			maxIndex++
		}
	}

	// 将排好序的临时数组写会原来的数组
	for i := 0; i < len(tempSlice); i++ {
		nums[left+i] = tempSlice[i]
	}
}
