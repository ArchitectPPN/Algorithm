package main

import "fmt"

// https://leetcode.cn/problems/permutations/
func main() {
	nums := []int{1, 2, 3}

	p := new(PermutationsSolution)
	p.permute(nums)
}

type PermutationsSolution struct {
	used       map[int]bool
	numLen     int
	processVar []int
	answer     [][]int
}

// permute 计算
func (p *PermutationsSolution) permute(nums []int) [][]int {
	p.initData(nums)

	p.dfs(nums, 0)

	fmt.Println(p.answer)

	return p.answer
}

// initData 初始化
func (p *PermutationsSolution) initData(nums []int) {
	p.numLen = len(nums)
	p.used = make(map[int]bool, p.numLen)

	for i := 0; i < p.numLen; i++ {
		p.used[i] = false
	}

	fmt.Println(p.used)
}

// dfs
func (p *PermutationsSolution) dfs(nums []int, start int) {
	if start == p.numLen {
		sCopy := make([]int, len(p.processVar))
		copy(sCopy, p.processVar)

		p.answer = append(p.answer, sCopy)

		return
	}

	for i := 0; i < p.numLen; i++ {
		// 检查是否使用过
		if !p.used[i] {
			// 标记位已使用
			p.used[i] = true

			p.processVar = append(p.processVar, nums[i])
			p.dfs(nums, start+1)
			// 去除最后一个元素
			p.processVar = p.processVar[:len(p.processVar)-1]

			p.used[i] = false
		}
	}
}
