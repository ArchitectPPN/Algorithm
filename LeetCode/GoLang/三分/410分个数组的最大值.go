package main

import "fmt"

func main() {
	solution := new(SplitArrayLargestSum)
	ans := solution.handle([]int{7, 2, 5, 10, 8}, 3)

	fmt.Println(ans)
}

type SplitArrayLargestSum struct {
}

func (s *SplitArrayLargestSum) handle(nums []int, m int) int {
	// 初始化left（最差也要这个数组最大值，才可能分为m组）， right（所有数字加起来最大值分一组）
	var left, right, mid int
	left = 0
	right = 0
	for i := 0; i < len(nums); i++ {
		left = s.max(left, nums[i])
		right += nums[i]
	}

	for left < right {
		mid = (left + right) >> 1
		if s.isValid(nums, m, mid) {
			right = mid
		} else {
			left = mid + 1
		}
	}

	return right
}

// 返回最大值
func (s *SplitArrayLargestSum) max(x, y int) int {
	if x > y {
		return x
	}

	return y
}

// 校验t是否合法
func (s *SplitArrayLargestSum) isValid(nums []int, m, t int) bool {
	// 初始化分组 groupSum用来记录当前分组总和，groupCount用来记录当前分组数
	var groupSum, groupCount int
	// 最开始分组和加起来为0
	groupSum = 0
	// 最少一组
	groupCount = 1

	for i := 0; i < len(nums); i++ {
		// 当前第i个数+groupNum小于T，说明还可以继续向当前分组添加元素
		if groupSum+nums[i] <= t {
			groupSum += nums[i]
		} else {
			// 已经超过预设值了，重开一个数组
			groupCount++
			groupSum = nums[i]
		}
	}

	// 因为限制可以分m组，所以小于等于m时，认为可行
	return groupCount <= m
}
