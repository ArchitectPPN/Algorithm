package main

import (
	"fmt"
	"math"
)

func main() {
	goodsNum := 3
	goodsVal := []int{5, 2, 6}
	goodsCap := []int{2, 1, 4}
	bagCap := 4

	ans := bagQuestionSolution(goodsNum, goodsVal, goodsCap, bagCap)

	fmt.Println("ans:", ans)
}

func bagQuestionSolution(goodsNum int, goodsVal []int, goodsCap []int, bagCap int) int {
	// 初始化dp数组
	// 因为dp[i][j]代表选择前i个物品时的背包总价值，所以将整个数组初始化int的最小值
	// 我们要从第一个元素开始，所以需要初始化容量为n+1，容量为m+1
	dp := make([][]int, goodsNum+1)
	for i := range dp {
		dp[i] = make([]int, bagCap+1)
		for j := range dp[i] {
			dp[i][j] = math.MinInt32
		}
	}
	// 初始化背包里面没有物品时的价值
	dp[0][0] = 0

	// 填充dp数组
	for i := 1; i <= goodsNum; i++ {
		for j := 0; j <= bagCap; j++ {
			// 当前背包容量大于物品的容量时，可以选择当前物品或者不选当前物品
			if j >= goodsCap[i-1] {
				dp[i][j] = max(dp[i][j], dp[i-1][j-goodsCap[i-1]]+goodsVal[i-1])
			} else {
				// 如果当前容量j小于当前物品的容量，则只能选择不选当前物品，由于没有选择当前的物品，所以还是上一个状态的值，也就是：dp[i-1][j]
				// dp[i][j] = dp[i-1][j]
				dp[i][j] = dp[i-1][j]
			}
		}
	}

	ans := math.MinInt32
	for j := 0; j <= bagCap; j++ {
		ans = max(ans, dp[goodsNum][j])
	}

	return ans
}
