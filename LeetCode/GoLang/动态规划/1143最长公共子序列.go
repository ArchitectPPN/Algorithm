package main

import (
	"fmt"
)

func main() {
	solution := new(LongestCommonSubsequence)

	ans := solution.handle("abcde", "ace")

	fmt.Println("ans: ", ans)
}

type LongestCommonSubsequence struct {
}

func (l *LongestCommonSubsequence) handle(text1, text2 string) int {
	m, n := len(text2), len(text1)

	text2 = " " + text2
	text1 = " " + text1

	var f [][]int
	f = make([][]int, n+1)

	for index, _ := range f {
		f[index] = make([]int, m+1)
	}

	for i := 1; i <= n; i++ {
		for j := 1; j <= m; j++ {
			if text1[i] == text2[j] {
				f[i][j] = f[i-1][j-1] + 1
			} else {
				// str1 i-1个字符和 str2 前j个字符串的公共长度
				// str1前0个字符和str2前1个字符， 必定是0
				// str1前1个字符和str2前0个字符， 必定是0
				// str1前1个字符和str2前1个字符， 是1
				f[i][j] = l.Max(f[i-1][j], f[i][j-1])
				//
			}
		}
	}

	return f[n][m]
}

func (l *LongestCommonSubsequence) Max(x, y int) int {
	if x < y {
		return y
	}
	return x
}
