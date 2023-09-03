package main

import "fmt"

func main() {
	solution := new(findMinSolution)
	ans := solution.handle([]int{})

	fmt.Println(ans)
}

type findMinSolution struct {
}

func (f *findMinSolution) handle(nums []int) int {
	if len(nums) == 0 {
		return -1
	}

	var left, right, mid int

	left = 0
	right = len(nums) - 1

	/**
	3 4 5 | 1 2
	左端：> nums[right]
	右端：< nums[right]

	4 5 6 7 | 0 1 2
	所以这道题就可以转化为： 查找第一个<=末尾的数
	*/

	for left < right {
		mid = (left + right) >> 1
		if nums[mid] <= nums[right] {
			right = mid
		} else {
			left = mid + 1
		}
	}

	return nums[right]
}
