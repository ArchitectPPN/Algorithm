package main

import "fmt"

func main() {
	solution := new(FindPeakElement)
	ans := solution.handle([]int{1, 2, 3, 1})

	fmt.Println(ans)
}

type FindPeakElement struct {
}

func (f *FindPeakElement) handle(nums []int) int {
	var left, right, lMid, rMid int

	left = 0
	right = len(nums) - 1

	for left < right {
		lMid = (left + right) >> 1
		rMid = lMid + 1
		if nums[lMid] < nums[rMid] {
			left = lMid + 1
		} else {
			right = rMid - 1
		}
	}

	return left
}
