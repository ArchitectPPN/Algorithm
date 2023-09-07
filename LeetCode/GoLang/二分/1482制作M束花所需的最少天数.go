package main

import "fmt"

func main() {
	solution := new(MakeMBouquetsSolution)
	ans := solution.handle([]int{1, 10, 3, 10, 2}, 3, 1)

	fmt.Println(ans)
}

type MakeMBouquetsSolution struct {
}

func (make *MakeMBouquetsSolution) handle(nums []int, m, k int) int {
	var left, right, mid, max int
	max = 1000000001
	left = 0
	right = max

	for left < right {
		mid = (left + right) >> 1
		if make.isValidOnDay(nums, m, k, mid) {
			right = mid
		} else {
			left = mid + 1
		}
	}

	if right == max {
		right = -1
	}

	return right
}

// isValidOnDay 验证当前天数可以制作的花束数量
func (make *MakeMBouquetsSolution) isValidOnDay(nums []int, m, k, t int) bool {
	// bouquets 连续的花朵数 flowers 已经制作出来的花朵数量
	var bouquets, flowers int
	bouquets = 0
	flowers = 0

	for i := 0; i < len(nums); i++ {
		if nums[i] <= t {
			// 连续的花朵数量+1
			bouquets++
			// 连续的花朵数量足以制作花束，花束数量+1，连续的花朵置为0
			if bouquets == k {
				bouquets = 0
				flowers++
			}
		} else {
			// 没有开花， 所以连续的花朵需要被重置
			bouquets = 0
		}
	}

	return flowers >= m
}
