package main

import (
	"fmt"
	"math/rand"
)

// 快速排序是冒泡排序的优化思路
func main() {
	solution := new(quickSortSolution)
	solution.handle()
}

type quickSortSolution struct {
}

func (q *quickSortSolution) handle() {
	q.quickSort([]int{3, 5, 4, 2, 6, 1}, 0, 5)

	//fmt.Println(arr)
}

// quickSort 快速排序
func (q *quickSortSolution) quickSort(arr []int, left, right int) {
	// 说明左右指针相遇了
	if left == right {
		return
	}

	// 找到一个中间值
	pivot := q.partition(arr, left, right)
	if pivot < left {
		return
	}
	q.quickSort(arr, left, pivot)
	q.quickSort(arr, pivot+1, right)

	fmt.Println(arr)
	//return arr
}

func (q *quickSortSolution) partition(arr []int, left, right int) int {
	// 随机选出一个数
	random := rand.Float64()
	temVal := random * float64(right-left+1)
	pivot := left + int(temVal)
	pivotVal := arr[pivot]

	for left <= right {
		for arr[left] < pivotVal {
			// left向左移动
			left += 1
		}
		for arr[right] > pivotVal {
			// 向右移动
			right -= 1
		}
		if left <= right {
			// 交换位置
			temp := arr[left]
			arr[left] = arr[right]
			arr[right] = temp
			left += 1
			right -= 1
		}
	}

	return right
}
