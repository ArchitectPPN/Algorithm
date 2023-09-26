package main

import (
	"fmt"
	"sort"
)

func main() {
	solution := new(FindContentChildren)
	ans := solution.handle([]int{1, 2, 3}, []int{1, 1, 1})

	fmt.Println("ans: ", ans)
}

type FindContentChildren struct {
}

func (f *FindContentChildren) handle(g, s []int) int {
	// 对孩子的胃口和饼干的大小进行排序
	sort.Ints(g)
	sort.Ints(s)

	// 初始化答案
	j, ans := 0, 0

	// 循环孩子的胃口
	for i := 0; i < len(g); i++ {
		// 从第一块饼干开始找, 如果饼干不能满足孩子的胃口，就开始找第二块
		// 一直找到能满足孩子胃口的饼干， 都无法满足， 那说明无法满足任何一个孩子的胃口
		for j < len(s) && s[j] < g[i] {
			j++
		}

		// 找到了， 从饼干列表中去掉当前的饼干， 答案+1
		if j < len(s) {
			j++
			ans++
		}
	}

	return ans
}
