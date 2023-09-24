package main

import (
	"fmt"
	"sort"
)

func main() {
	solution := new(FoundAddressSolution)
	ans := solution.handle([]int{1, 2, 3, 4, 5, 6, 7})

	fmt.Println("中位数为：", ans)
}

type FoundAddressSolution struct {
}

/*
 * 1 2 3 * 4 5
 * 假设仓库建到2的位置： 1 2* 3 4 5 仓库 到 1 2 的位置会减少1 到3 4 5的位置都会加1， 两个减一， 三个加一， 总距离是增加的， 所以相对中间的位置是距离增加了
 * 所以该题目就是找中位数
 * 1. 数组为奇数时，找中间值即可；
 * 2. 数组为偶数时，中间数都可以， 比如 1 2 3 4 5 6， 在3 4的位置上都可以
 */
func (f *FoundAddressSolution) handle(nums []int) int {
	return f.findMidNums(nums)
}

func (f *FoundAddressSolution) findMidNums(nums []int) int {
	sort.Ints(nums)

	midNum := (len(nums) - 1) / 2

	return midNum
}
