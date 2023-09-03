package main

import "fmt"

func main() {
	sqrtX := new(SqrtX)

	ans := sqrtX.handle(2147395599)

	fmt.Println("answer: ", ans)
}

type SqrtX struct {
}

func (s *SqrtX) handle(x int) int {
	var left, right, mid, tmpX uint64
	tmpX = uint64(x)
	left = 0
	right = tmpX

	for left < right {
		// 取中间数
		mid = (left + right + 1) >> 1
		if mid*mid <= tmpX {
			left = mid
		} else {
			right = mid - 1
		}
	}

	return int(left)
}
