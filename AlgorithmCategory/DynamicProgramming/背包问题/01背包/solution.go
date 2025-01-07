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
	//ans := bagQuestionSolutionOptimize(goodsNum, goodsVal, goodsCap, bagCap)

	fmt.Println("ans:", ans)
}

// bagQuestionSolution
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

	/**
	 * 状态转移方程：F[i][j]代表选择了前i个商品总体积为j的价值总和
	 * F[i][j]: 由两种状态转移得来
	 * - F[i-1][j]: 不选第i个物品，也就是说背包里没有新增物品，体积也没有变大，没有发生任何变化，和上一个状态的值一样
	 * - MAX(F[i][j], F[i-1][j-goodsCap[i-1]] + goodsVal[i-1]) 选择了当前的物品，那么背包的容量发生变化，上一个状态的值加上物品的价值
	 * F[i-1][j-goodsCap[i-1]]+goodsVal[i-1]的理解，因为选择了当前的物品，所以背包的体积和价值发生了变化
	 * 当前的物品为第1个，物品的容量为2，背包容量为4时，上一个状态的值可能是：
	 * - F[0][0]
	 * - F[0][1]
	 * - F[0][2]
	 * - F[0][3]
	 * - F[0][4]
	 */

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

	/**
	 * 由于进行了初始化，所以除了dp[0][0]之外其他的val均为-2147483648
	 * dp[4][5]
	 * dp[0][0] = 0 			dp[0][1] = -2147483648 		dp[0][2] = -2147483648 		dp[0][3] = -2147483648 		dp[0][4] = -2147483648
	 * dp[1][0] = -2147483648 	dp[1][1] = -2147483648 		dp[1][2] = -2147483648 		dp[1][3] = -2147483648 		dp[1][4] = -2147483648
	 * dp[2][0] = -2147483648 	dp[2][1] = -2147483648 		dp[2][2] = -2147483648 		dp[2][3] = -2147483648 		dp[2][4] = -2147483648
	 * dp[3][0] = -2147483648 	dp[3][1] = -2147483648 		dp[3][2] = -2147483648 		dp[3][3] = -2147483648 		dp[3][4] = -2147483648

	 * 状态转移方程：
	 *	第几个商品	商品容量			当前背包容量j		当前dp						前一dp状态					当前背包价值		最后的结果
	 *	1			2				0				dp[1][0] = 0				dp[0][0] = 0				5				dp[1][0] = 0
	 *	1			2				1				dp[1][1] = -2147483648		dp[0][1] = -2147483648		5				dp[1][1] = -2147483648
	 *	1			2				2				dp[1][2] = -2147483648		dp[0][0] = 0				5				dp[1][2] = 5
	 *	1			2				3				dp[1][3] = -2147483648		dp[0][1] = -2147483648		5				dp[1][3] = -2147483643
	 *	1			2				4				dp[1][4] = -2147483648		dp[0][2] = -2147483648		5				dp[1][4] = -2147483643

	 *	2			1				0				dp[2][0] = 0				dp[1][0] = 0				2				dp[2][0] = 0
	 *	2			1				1				dp[2][1] = -2147483648		dp[1][0] = 0				2				dp[2][1] = 2
	 *	2			1				2				dp[2][2] = -2147483648		dp[1][1] = 0				2				dp[2][2] = -2147483646
	 *	2			1				3				dp[2][3] = -2147483648		dp[1][2] = 5				2				dp[2][3] = 7
	 *	2			1				4				dp[2][4] = -2147483648		dp[1][3] = -2147483643		2				dp[2][4] = -2147483641

	 *	3			4				0				dp[3][0] = 0				dp[2][0] = 0				6				dp[3][0] = 0
	 *	3			4				1				dp[3][1] = -2147483648		dp[2][1] = 0				6				dp[3][1] = 2
	 *	3			4				2				dp[3][2] = -2147483648		dp[2][2] = -2147483646		6				dp[3][2] = -2147483646
	 *	3			4				3				dp[3][3] = -2147483648		dp[2][3] = 7				6				dp[3][3] = 7
	 *	3			4				4				dp[3][4] = -2147483648		dp[2][0] = 0				6				dp[3][4] = 6
	 */

	ans := math.MinInt32
	for j := 0; j <= bagCap; j++ {
		ans = max(ans, dp[goodsNum][j])
	}

	return ans
}

// bagQuestionSolution
func bagQuestionSolutionOptimize(goodsNum int, goodsVal []int, goodsCap []int, bagCap int) int {
	// 初始化dp数组
	// 因为dp[i][j]代表选择前i个物品时的背包总价值，所以将整个数组初始化int的最小值
	// 我们要从第一个元素开始，所以需要初始化容量为n+1，容量为m+1
	dp := make([]int, bagCap+1)
	for i := range dp {
		dp[i] = math.MinInt32
	}
	// 初始化背包里面没有物品时的价值
	dp[0] = 0

	/**
	 * 模拟循环结果：
	 *
	 */

	// 填充dp数组
	// 一共有三个物品，从0开始遍历
	for i := 0; i < goodsNum; i++ {
		// 假设背包容量是4，依次将商品放入背包，当前背包容量大于商品容量时，可以选择当前物品或者不选当前物品
		// j := bagCap 当前背包容量
		// j >= goodsCap[i] 当前背包容量大于商品容量，表示可以放入该商品，放入进去后加上该商品的价值
		for j := bagCap; j >= goodsCap[i]; j-- {
			// dp[j-goodsCap[i]]+goodsVal[i] 将商品放入背包，然后计算背包总价值
			dp[j] = max(dp[j], dp[j-goodsCap[i]]+goodsVal[i])
		}
	}

	ans := math.MinInt32
	for j := 0; j <= bagCap; j++ {
		ans = max(ans, dp[j])
	}

	return ans
}
