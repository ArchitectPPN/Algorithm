package main

import (
	"fmt"
	"math"
)

func main() {
	question := []int{1, 2, 3, 4, 5, 6}

	maxRobMoney := rob(question)

	fmt.Println("最大的抢劫金额：", maxRobMoney)
}

func rob(nums []int) int {
	// 	数组长度为0时，说明没有房子，那么最大的偷盗金额只能是0
	if len(nums) == 0 {
		return 0
	}
	// 只有一间房时，那么最大的偷盗金额只能是第一间房的金额
	if len(nums) == 1 {
		return nums[1]
	}

	// 声明一个切片
	dp := make([][]int, len(nums)+1)
	// 对dp进行初始化
	for i := range dp {
		dp[i] = make([]int, 2)
	}
	// 初始化第零天没有偷的情况
	dp[0][0] = 0
	// 初始化第零天偷盗的情况，因为不存在偷盗，所以为int的最小值
	dp[0][1] = int(math.MinInt32)

	// 当天天数, 这里定义一个当天天数只是为了更好的理解，其实可以直接删除的
	currentDay := 0
	for i := 0; i < len(nums); i++ {
		currentDay = i + 1
		// 当天没有偷
		dp[currentDay][0] = max(dp[currentDay-1][0], dp[currentDay-1][1])
		// 当天偷，那么前一天必须为不偷
		dp[currentDay][1] = dp[currentDay-1][0] + nums[i]
	}

	return max(dp[len(nums)][0], dp[len(nums)][1])
}
