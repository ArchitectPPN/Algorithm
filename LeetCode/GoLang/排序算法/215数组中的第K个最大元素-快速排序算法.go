package main

import (
	"fmt"
	"math/rand"
)

func main() {
	nums := []int{1, 2, 3, 4, 5}

	k := 6

	solution := new(QuickSortSolution)
	kMaxNum := solution.handle(nums, len(nums)-k)

	fmt.Println("第K个大的数字为：", kMaxNum)
}

// QuickSortSolution 实现思路
/**
 * 快速排序的实现思想为， 找一个中间值p， 将一组数字分为两部分， 左边[0-P]比P小， 右边[P+1 - 数组末尾]为大于P的数字
 * 负责度：n+n/2+n/4+...<2n = O(N)
 */
type QuickSortSolution struct {
}

func (q *QuickSortSolution) handle(nums []int, k int) int {
	return q.quickSort(nums, k, 0, len(nums)-1)
}

// quickSort 快速排序
func (q *QuickSortSolution) quickSort(arr []int, k, left, right int) int {
	// 说明左右指针相遇了
	if left == right {
		return arr[left]
	}

	// 找到一个中间值
	pivot := q.partition(arr, left, right)
	if pivot >= k {
		return q.quickSort(arr, k, left, pivot)
	} else {
		return q.quickSort(arr, k, pivot+1, right)
	}
}

// partition
func (q *QuickSortSolution) partition(arr []int, left, right int) int {
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
