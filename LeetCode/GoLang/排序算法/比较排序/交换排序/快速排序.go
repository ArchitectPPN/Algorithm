package main

import (
	"fmt"
	"math/rand"
)

// 快速排序是冒泡排序的优化思路
func main() {
	waitSortArr := []int{3, 5, 4, 2, 6, 1}

	solution := new(quickSortSolution)
	solution.handle(waitSortArr)

	fmt.Println("sort arr:", waitSortArr)
}

type quickSortSolution struct {
}

func (q *quickSortSolution) handle(nums []int) {
	q.quickSort(nums, 0, 5)
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
	// 给左边排序：left - pivot的值排序
	q.quickSort(arr, left, pivot)
	// 给pivot+1 - right的值进行排序
	q.quickSort(arr, pivot+1, right)
}

func (q *quickSortSolution) partition(arr []int, left, right int) int {
	// 获取一个0-1之间的随机数
	random := rand.Float64()
	// random * float64(right-left+1) ， 举例：0.5 * （4-3+1） = 0.5 * 2 = 1， 最后的结果会在 0-2之间
	temVal := random * float64(right-left+1)
	//
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
