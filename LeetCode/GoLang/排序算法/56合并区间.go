package main

import (
	"fmt"
	"sort"
)

func main() {
	solution := new(CombineArr)
	ans := solution.handle([][]int{{1, 3}, {2, 6}, {8, 10}, {15, 18}})

	fmt.Println(ans)
}

type CombineArr struct {
}

func (c *CombineArr) handle(intervals [][]int) [][]int {
	return c.merge(intervals)
}

func (c *CombineArr) merge(intervals [][]int) [][]int {
	// 将二维数组进行排序
	sort.Slice(intervals, func(i, j int) bool {
		return intervals[i][0] < intervals[j][0] || intervals[i][0] == intervals[j][0] && intervals[i][1] < intervals[j][1]
	})

	// 初始化起点
	var left, right int
	left = -1
	right = -1

	// 最后的答案
	var ans [][]int
	for i := 0; i < len(intervals); i++ {
		// 如果数组的开始位置小于right，说明两个数组存在重叠部分，是延续部分
		if intervals[i][0] <= right {
			// 更新最右边
			right = c.max(right, intervals[i][1])
		} else {
			// 新开一段
			if right >= 0 {
				ans = append(ans, []int{left, right})
			}

			left = intervals[i][0]
			right = intervals[i][1]
		}
	}

	if right >= 0 {
		ans = append(ans, []int{left, right})
	}

	return ans
}

// max 返回最大值
func (c *CombineArr) max(i, j int) int {
	if i > j {
		return i
	}

	return j
}
