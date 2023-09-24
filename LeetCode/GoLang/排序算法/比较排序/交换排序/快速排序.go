package main

import (
	"fmt"
	"math/rand"
)

// 快速排序是冒泡排序的优化思路
func main() {
	waitSortArr := []int{3, 5, 4, 2, 6, 1, 18, 20, 21, 19}
	
	solution := new(quickSortSolution)
	solution.handle(waitSortArr)

	fmt.Println("sort arr:", waitSortArr)

	// 单独测试pivot选取
	res := 0
	for {
		if res == 6 {
			fmt.Println("res: ", res)
			return
		}
		res = solution.randomPivot(4, 6)
	}
}

type quickSortSolution struct {
}

func (q *quickSortSolution) handle(nums []int) {
	q.quickSort(nums, 0, len(nums)-1)
}

// quickSort 快速排序
func (q *quickSortSolution) quickSort(arr []int, left, right int) {
	// 说明左右指针相遇了
	if left == right {
		return
	}

	// 找到一个中间值
	pivot := q.partition(arr, left, right)
	// pivot 小于 left时，继续执行，会陷入死循环
	if pivot < left {
		return
	}
	// 给左边排序：left - pivot的值排序
	q.quickSort(arr, left, pivot)
	// 给pivot+1 - right的值进行排序
	q.quickSort(arr, pivot+1, right)
}

/**
 * 中轴选择逻辑，每次都选择到最小值时，会退化到n^2；
 *	1. 可以选择中间；
 *	2. 选择最左边；
 *	3. 选择最右边；
 *  4. 随机选择，通过随机可以达到期望的O(NlogN), 有时好有时坏，总体上比较好
 */
func (q *quickSortSolution) partition(arr []int, left, right int) int {
	pivot := q.randomPivot(left, right)
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

	// 因为这里return right了，所以在外面 q.quickSort(arr, left, pivot) 这里可以直接这样写
	// 如果这里要return left，那么 q.quickSort(arr, left, pivot - 1) q.quickSort(arr, right, pivot)
	return right
}

/**
 * 选取中间值作为 pivot
 */
func (q *quickSortSolution) midPivot(min, max int) int {
	return (min + max) >> 1
}

/**
 * 选取最左边的值作为 pivot
 */
func (q *quickSortSolution) leftPivot(left, right int) int {
	return left
}

/**
 * 选取最右边的值作为 pivot
 */
func (q *quickSortSolution) rightPivot(left, right int) int {
	return right
}

/**
 * 随机选取一个值作为 pivot，就是为了拿到left / right中间的一个值
 * 分以下几种情况：
 * 1. left = 4， right = 5，这种情况， 拿 4/5 都可以
 * 2. left = 4， right = 6，这种情况，最好可以拿到5，或者拿到4/6其中一个
 */
func (q *quickSortSolution) randomPivot(left, right int) int {
	// 获取一个0-1之间的随机数
	random := rand.Float64()
	// 这里加1，举例来说： 如果为上面第2中情况，如果不加1，拿不到 pivot = 6的情况，因为 diff 恒等于2， 然后 2*(0-1之间的数)，int之后一直等于1或者0，
	// 加1之后变为3*(0-1之间的数),有很大概率拿到2，最后pivot=6
	diff := float64(right - left + 1)
	// random * float64(right-left+1) ， 举例：0.5 * （4-3+1） = 0.5 * 2 = 1， 最后的结果会在 0-2之间
	temVal := random * diff
	//
	pivot := left + int(temVal)

	return pivot
}
