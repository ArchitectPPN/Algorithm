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
	// 对nums进行排序, 从下标0开始, 一直到数组的末尾
	q.quickSort(nums, 0, len(nums)-1)
}

// quickSort 快速排序
func (q *quickSortSolution) quickSort(nums []int, left, right int) {
	// 说明左右指针相遇了, 排序结束
	if left == right {
		return
	}

	// 找到一个中间值
	pivot := q.partition(nums, left, right)
	// pivot 小于 left时，继续执行，会陷入死循环
	if pivot < left {
		return
	}
	// 给左边排序：left - pivot的值排序
	q.quickSort(nums, left, pivot)
	// 给pivot+1 - right的值进行排序
	q.quickSort(nums, pivot+1, right)
}

/**
 * 中轴选择逻辑，每次都选择到最小值时，会退化到n^2；
 *	1. 可以选择中间；
 *	2. 选择最左边；
 *	3. 选择最右边；
 *  4. 随机选择，通过随机可以达到期望的O(NlogN), 有时好有时坏，总体上比较好
 */
func (q *quickSortSolution) partition(nums []int, left, right int) int {
	pivot := q.randomPivot(left, right)
	pivotVal := nums[pivot]

	// 选择一个中轴数, 左边全部小于该数, 右边全部大于等于该数
	for left <= right {
		// nums[left] 小于中轴, 说明当前值(nums[left])无需移动, 但是下边需要继续向右移动, 一直移动到 nums[left] 不小于中轴值(pivotVal)
		// 然后开始移动中轴(pivotVal)右边的数字, 一直向左边移动, 移动到 nums[right]小于中轴值(pivotVal),
		// 交换左右两个小标的值,
		// 这里举个例子: 1 2 3 4 5 10 7 8 9, pivotVal 为 10
		// 上面这个例子可以看到无论左右两边都是小于10的, 所以第一次循环会在left = 5时停止, 此时, pivotVal为10; 所以说left永远不会越过pivot;
		// left无法移动后, right会开始移动, right此时为8, 值为9, 因为9小于10, 所以无需向左移动, 直接交换9和10的位置, pivotVal直接到达末尾, left = 6, right = 7
		// 第二次循环, left一直移动到7便会停止, 至此pivotVal左边都小于它的
		for nums[left] < pivotVal {
			// left向右移动
			left += 1
		}
		// 同上
		for nums[right] > pivotVal {
			// 向左移动
			right -= 1
		}

		// 上述左右两边都进行移动了, 且目前都已经无法移动了, 即左边的不小于中轴值(pivotVal), 右边的不大于中轴值(pivotVal)
		if left <= right {
			// 交换位置
			temp := nums[left]
			nums[left] = nums[right]
			nums[right] = temp
			left += 1
			right -= 1
		}
	}

	// 因为这里return right了，所以在外面 q.quickSort(arr, left, pivot) q.quickSort(arr, pivot+1, right)这里可以直接这样写
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
