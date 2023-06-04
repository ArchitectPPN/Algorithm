package main

import "fmt"

// https://leetcode.cn/problems/n-queens/ N 皇后
func main() {
	// questionNum
	questionNum := 1

	solveNQueens := new(solveNQueensSolution)
	fmt.Println(solveNQueens.handle(questionNum))
}

type solveNQueensSolution struct {
	n          int          // 题目给定的数字
	allList    [][]int      // 存放所有的排列
	used       []bool       // 检查i是否被使用
	processVal []int        // 存放计算过程
	usedIPlusJ map[int]bool // 坐标是否被使用
	usedIMinJ  map[int]bool // 坐标是否被使用

	result [][]string
}

func (s *solveNQueensSolution) initData(n int) {
	s.n = n
	s.processVal = make([]int, 0)
	s.used = make([]bool, s.n)
	s.usedIMinJ = make(map[int]bool)
	s.usedIPlusJ = make(map[int]bool)
}

func (s *solveNQueensSolution) handle(n int) [][]string {
	s.initData(n)
	s.find(0)

	temp := make([]string, s.n)
	for _, per := range s.allList {

		tmpRes := make([]string, 0)
		for row := 0; row < s.n; row++ {
			// 初始化
			for tmpIndex, _ := range temp {
				temp[tmpIndex] = "."
			}
			col := per[row]
			temp[col] = "Q"
			fmt.Println(temp)

			str := ""
			for _, res := range temp {
				str += res
			}
			tmpRes = append(tmpRes, str)
		}

		s.result = append(s.result, tmpRes)
	}

	fmt.Println(s.result)

	return s.result
}

func (s *solveNQueensSolution) find(row int) {
	if row == s.n {
		// 把结果添加到里面去
		sCopy := make([]int, len(s.processVal))
		copy(sCopy, s.processVal)

		s.allList = append(s.allList, sCopy)
		return
	}

	for col := 0; col < s.n; col++ {
		// 未被使用过时, 添加进去
		if !s.used[col] && !s.usedIPlusJ[row+col] && !s.usedIMinJ[row-col] {
			// 设置已被使用
			s.used[col] = true
			s.usedIPlusJ[row+col] = true
			s.usedIMinJ[row-col] = true

			s.processVal = append(s.processVal, col)
			s.find(row + 1)
			s.processVal = s.processVal[:len(s.processVal)-1]

			// 还原现场
			s.used[col] = false
			s.usedIPlusJ[row+col] = false
			s.usedIMinJ[row-col] = false
		}
	}
}
