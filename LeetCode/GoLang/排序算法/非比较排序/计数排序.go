package main

import "fmt"

func main() {
	solution := new(CountSortHandle)
	solution.handle([]int{1, 1, 1, 2, 2, 2})
}

type CountSortHandle struct {
}

func (c *CountSortHandle) handle(arr []int) {
	c.countSort(arr)
}

/**
 * 适用场景：适用范围较小的整数序列
 */
func (c *CountSortHandle) countSort(arr []int) {
	// 找出arr内部最大的值，找出arr数组中最大的那个元素
	var max int
	for arrLength := 0; arrLength < len(arr); arrLength++ {
		max = c.max(max, arr[arrLength])
	}

	var tmpCount []int
	// 初始化的数组本身值就是0，所以无需初始化
	tmpCount = make([]int, max+1)

	// 统计每个元素出现的次数
	for i := 0; i < len(arr); i++ {
		tmpCount[arr[i]]++
	}

	// 计算累加数组，因为从1开始，所以max+1，下标才不会越界
	for i := 1; i < max+1; i++ {
		tmpCount[i] += tmpCount[i-1]
	}

	// 定义一个累计的累加数组
	var countSum []int
	countSum = make([]int, len(arr))
	// 将结果写回原数组
	for i := 0; i < len(arr); i++ {
		countSum[tmpCount[arr[i]]-1] = arr[i]
		tmpCount[arr[i]]--
	}

	fmt.Println("arr: ", arr, tmpCount, countSum)
}

func (c *CountSortHandle) max(x, y int) int {
	if x > y {
		return x
	}
	return y
}
