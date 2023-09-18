package main

import "fmt"

func main() {
	solution := new(RelativeSortSolution)
	ans := solution.handle([]int{2, 3, 1, 3, 2, 4, 6, 7, 9, 2, 19}, []int{2, 1, 4, 3, 9, 6})

	fmt.Println(ans)
}

type RelativeSortSolution struct {
}

// handle
func (r *RelativeSortSolution) handle(arr1, arr2 []int) []int {
	var ans, count []int
	ans = make([]int, len(arr1))
	var n int
	n = 0

	count = make([]int, 1001)
	// 统计arr1中每个元素出现的个数
	for i := 0; i < len(arr1); i++ {
		count[arr1[i]]++
	}

	// 按照arr2的顺序来取元素
	for i := 0; i < len(arr2); i++ {
		for count[arr2[i]] > 0 {
			count[arr2[i]]--
			ans[n] = arr2[i]
			n++
		}
	}

	// 把没有在arr2中出现的数字写入到结果中
	for i := 0; i < 1001; i++ {
		for count[i] > 0 {
			count[i]--
			ans[n] = i
			n++
		}
	}

	return ans
}
