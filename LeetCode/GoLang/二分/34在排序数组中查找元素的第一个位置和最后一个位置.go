package main

import "fmt"

func main() {
	solution := new(FindFirstAndLastInTheOrderArrSolution)
	ans := solution.handle([]int{5, 7, 7, 8, 8, 10}, 8)

	fmt.Println(ans)
}

type FindFirstAndLastInTheOrderArrSolution struct {
}

// handle 处理
func (f *FindFirstAndLastInTheOrderArrSolution) handle(problem []int, target int) []int {
	var ans []int
	ans = make([]int, 2)

	var min, max, mid int
	// 查找lower_bound
	min = 0
	max = len(problem)
	for min < max {
		mid = (min + max) >> 1
		if problem[mid] >= target {
			max = mid
		} else {
			min = mid + 1
		}
	}
	ans[0] = max

	// 查找upper_bound
	min = -1
	max = len(problem) - 1
	for min < max {
		mid = (min + max + 1) >> 1
		if problem[mid] <= target {
			min = mid
		} else {
			max = mid - 1
		}
	}
	ans[1] = max

	if ans[0] > ans[1] {
		ans = []int{-1, -1}
		return ans
	}

	return ans
}
