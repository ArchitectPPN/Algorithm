package main

import (
	"fmt"
	"math"
)

func main() {
	word1 := "horse"
	word2 := "ros"

	ans := minDistance(word1, word2)

	fmt.Println("最后答案：", ans)
}

func minDistance(word1 string, word2 string) int {
	// 获取长度
	w1Len := len(word1)
	w2Len := len(word2)

	// 其中有一个字符串为空时，到另一个字符串的长度就是两个字符串的长度和
	if w1Len*w2Len == 0 {
		return w1Len + w2Len
	}

	// 对零值进行初始化，我们两个字符串都是从第一个字符开始
	// 声明dp数组，dp[i][j],表示w1[i]字符变到w2[j]的次数
	dp := make([][]int, w1Len+1)
	for i := range dp {
		// 因为w1 -> w2，所以要声明一个能存储w2+1
		dp[i] = make([]int, w2Len+1)
		for j := 0; j <= w2Len; j++ {
			// 这里从w1和w2的第一个字符计算，所以0->0就是0，或者我们假想
			// 在word1和word2之前加一个空字符，如下所示：
			// 原本： word1 = "horse", word2 = "ros"
			// 修改： word1 = " horse", word2 = " ros"
			// 这样可以更方便理解
			if i == 0 {
				// 这个可以认为是 "" -> horse 需要执行多少次，就一直添加就好了，变为字符串就是字符串的长度
				dp[i][j] = j
			} else {
				// 这个可以认为是 horse -> "" 需要执行多少次，就一直删除就好了，变为空串就是字符串的长度
				dp[i][0] = i
			}
		}
	}

	for i := 1; i <= w1Len; i++ {
		for j := 1; j <= w2Len; j++ {
			increInLast := dp[i][j-1] + 1
			decreInLast := dp[i-1][j] + 1

			// 检查是否需要进行替换操作
			unChange := dp[i-1][j-1]
			// 如果不相等，则需要替换，然后操作数+1
			if word1[i-1] != word2[j-1] {
				unChange++
			}

			dp[i][j] = int(math.Min(float64(increInLast), math.Min(float64(decreInLast), float64(unChange))))
		}
	}

	return dp[w1Len][w2Len]
}
