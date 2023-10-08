package main

import "fmt"

func main() {
	solution := new(MaxProfitSolution)
	maxProfit := solution.handle([]int{1, 2, 3, 4, 5})

	fmt.Println("maxProfit: ", maxProfit)
}

type MaxProfitSolution struct {
}

func (m *MaxProfitSolution) handle(prices []int) int {
	// 初始化最大利润
	maxProfit := 0

	if len(prices) < 2 {
		return 0
	}

	for i := 1; i < len(prices); i++ {
		// 计算后一天和前一天的差价
		diff := prices[i] - prices[i-1]
		if diff > 0 {
			// 只要后一天的价格大于0，我们就卖出获取收益
			maxProfit += diff
		}
	}

	return maxProfit
}
