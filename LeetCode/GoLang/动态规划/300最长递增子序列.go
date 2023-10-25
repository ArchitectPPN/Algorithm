package main

import "fmt"

func main() {
	nums := []int{10, 9, 2, 5, 3, 7, 101, 18}

	solution := new(LongestIncreasingSub)
	ans := solution.handle(nums)

	fmt.Println("ans: ", ans)
}

type LongestIncreasingSub struct {
}

func (l *LongestIncreasingSub) handle(nums []int) int {
	var ans int
	var tmp []int
	tmp = make([]int, len(nums))

	for i := 1; i < len(nums); i++ {
		for j := 0; j < i; j++ {
			if nums[j] < nums[i] {
				tmp[i] = l.Max(tmp[i], tmp[j]+1)
			}
		}
	}

	ans = 0
	for i := 0; i < len(tmp); i++ {
		ans = l.Max(ans, tmp[i])
	}

	return ans + 1
}

func (l *LongestIncreasingSub) Max(x, y int) int {
	if x < y {
		return y
	}
	return x
}
