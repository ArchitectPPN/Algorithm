package main

import "fmt"

func main() {
	solution := new(SkipGameSolution)
	ans := solution.handle([]int{2, 3, 1, 1, 4})

	fmt.Println("ans:", ans)
}

type SkipGameSolution struct {
}

func (s *SkipGameSolution) handle(nums []int) int {
	// 初始化答案和开始步数
	ans := 0
	now := 0

	// 还未走到尾部时，继续
	for now < len(nums)-1 {
		// 第一步小于1，无法向右移动一步
		if nums[now] == 0 {
			return ans
		}

		// right为向右最多可以走到的位置
		right := now + nums[now]
		// 检查向右是否超过范围， 超过范围答案+1
		if right >= len(nums)-1 {
			return ans + 1
		}

		// 下一步
		next := now + 1
		// 检查向右走的所有可能里面最大的那个值
		for i := now + 2; i <= right; i++ {
			// 在i位置最远可以到达的位置
			nextRight := i + nums[i]
			if nextRight > next+nums[next] {
				next = i
			}
		}
		now = next
		ans++
	}

	return ans
}
