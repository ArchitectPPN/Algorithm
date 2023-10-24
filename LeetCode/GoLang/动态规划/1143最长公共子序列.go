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
				f[i][j] = l.Max(f[i-1][j], f[i][j-1])
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
