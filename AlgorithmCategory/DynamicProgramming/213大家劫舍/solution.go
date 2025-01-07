package main

import (
	"fmt"
	"math"
)

func main() {
	question := []int{1, 2, 3, 1}

	robMaxMoney := rob(question)
	fmt.Println("rob最大抢劫金额", robMaxMoney)

	robMaxMoney = rob1(question)
	fmt.Println("rob1最大抢劫金额", robMaxMoney)
}

// rob 下标从1开始
func rob(nums []int) int {
	numsLength := len(nums)
	// 	数组长度为0时，说明没有房子，那么最大的偷盗金额只能是0
	if numsLength == 0 {
		return 0
	}
	// 只有一间房时，那么最大的偷盗金额只能是第一间房的金额
	if numsLength == 1 {
		return nums[0]
	}

	question := make(map[int]int, numsLength)
	for key, value := range nums {
		question[key+1] = value
	}

	// 声明一个切片
	dp := make([][]int, numsLength+1)
	// 对dp进行初始化
	for i := range dp {
		dp[i] = make([]int, 2)
	}

	dp[1][0] = 0
	dp[1][1] = int(math.MinInt32)

	// 当天天数, 这里定义一个当天天数只是为了更好理解，其实可以直接删除的
	for i := 2; i <= numsLength; i++ {
		// 当天没有偷
		dp[i][0] = max(dp[i-1][0], dp[i-1][1])
		// 当天偷，那么前一天必须为不偷
		dp[i][1] = dp[i-1][0] + question[i]
	}

	ans := max(dp[numsLength][0], dp[numsLength][1])

	dp[1][0] = 0
	dp[1][1] = question[1]

	for i := 2; i <= numsLength; i++ {
		// 当天没有偷
		dp[i][0] = max(dp[i-1][0], dp[i-1][1])
		// 当天偷，那么前一天必须为不偷
		dp[i][1] = dp[i-1][0] + question[i]
	}

	return max(ans, dp[numsLength][0])
}

// rob 下标从0开始
func rob1(nums []int) int {
	numsLength := len(nums)
	// 	数组长度为0时，说明没有房子，那么最大的偷盗金额只能是0
	if numsLength == 0 {
		return 0
	}
	// 只有一间房时，那么最大的偷盗金额只能是第一间房的金额
	if numsLength == 1 {
		return nums[0]
	}

	// 声明一个切片
	dp := make([][]int, numsLength)
	// 对dp进行初始化
	for i := range dp {
		dp[i] = make([]int, 2)
	}

	dp[0][0] = 0
	dp[0][1] = int(math.MinInt32)

	// 当天天数, 这里定义一个当天天数只是为了更好理解，其实可以直接删除的
	for i := 1; i < numsLength; i++ {
		// 当天没有偷
		dp[i][0] = max(dp[i-1][0], dp[i-1][1])
		// 当天偷，那么前一天必须为不偷
		dp[i][1] = dp[i-1][0] + nums[i]
	}

	ans := max(dp[numsLength-1][0], dp[numsLength-1][1])

	dp[0][0] = 0
	dp[0][1] = nums[0]

	for i := 1; i < numsLength; i++ {
		// 当天没有偷
		dp[i][0] = max(dp[i-1][0], dp[i-1][1])
		// 当天偷，那么前一天必须为不偷
		dp[i][1] = dp[i-1][0] + nums[i]
	}

	return max(ans, dp[numsLength-1][0])
}
