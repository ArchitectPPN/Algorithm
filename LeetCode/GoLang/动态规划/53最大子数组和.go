package main

import "fmt"

func main() {
	nums := []int{-2, 1, -3, 4, -1, 2, 1, -5, 4}

	solution := new(maxSubArray)
	ans := solution.handle(nums)

	fmt.Println("ans: ", ans)
}

type maxSubArray struct {
}

func (m *maxSubArray) handle(nums []int) int {
	// 获取切片长度
	sliceLen := len(nums)

	// dp[i]表示以i结尾，前i个数字的最大和
	dp := make([]int, sliceLen)
	// dp[0]因为只有一个元素，所以最大值便是自身

	// 由于dp[i]表示前i个数组的最大值，所以前i个数字大于0时加上当前值，便可得到一个更大的值，
	// 如果前i个数的值小于0，加上当前值也不会得到比当前值更大的数，所以我们直接另起炉灶，丢掉前一个值
	for i := 1; i < sliceLen; i++ {
		if dp[i-1] > 0 {
			dp[i] = dp[i-1] + nums[i]
		} else {
			dp[i] = nums[i]
		}
	}

	// 默认最大值为dp[0]
	res := dp[0]
	// 最后我们将dp[i]循环一次，便可得到最大值
	for i := 0; i < sliceLen; i++ {
		res = m.maxArr(dp[i], res)
	}

	return res
}

func (m *maxSubArray) maxArr(x, y int) int {
	if x > y {
		return x
	}

	return y
}
