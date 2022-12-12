package main

import "fmt"

// 递归方法
func main() {
	fmt.Printf("%v\n", factorial(2))
}

/**递归*/
func factorial(n int) int {
	if n < 3 {
		return n
	}

	return factorial(n-1) + factorial(n-2)
}

/**记忆递归*/