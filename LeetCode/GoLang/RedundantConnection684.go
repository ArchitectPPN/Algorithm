package main

import "fmt"

// 题目链接: https://leetcode.cn/problems/redundant-connection/
func main() {
	question := [][]int{{1, 2}, {2, 3}, {3, 4}, {1, 4}, {1, 5}}

	solution := Solution{}
	fmt.Println(solution)
	res := solution.Handle(question)

	fmt.Println("结果：", res)
}

type Solution struct {
	HasCircle bool    // 是否有环
	IsVisit   []bool  // 是否访问过
	Edges     [][]int // 存放边
	MaxNum    int     // 最大值
}

// Handle 入口函数
func (s *Solution) Handle(question [][]int) []int {
	// 初始化最大值
	s.getMaxNum(question)
	// 初始化数据
	s.initData()

	// 添加边
	for _, value := range question {
		u := value[0]
		v := value[1]

		// 无向图， 添加边
		s.addEdges(u, v)
		s.addEdges(v, u)

		// 初始化visit
		s.initVisit()

		s.dfs(1, -1)
		if s.HasCircle {
			return value
		}
	}

	fmt.Println("s.Edges", s.Edges)

	return []int{}
}

// addEdges 加边
func (s *Solution) addEdges(x, y int) {
	s.Edges[x] = append(s.Edges[x], y)
}

// dfs
func (s *Solution) dfs(x, fa int) {
	// 设置以访问
	s.IsVisit[x] = true
	for _, y := range s.Edges[x] {
		if y == fa { // 返回了父亲， 不是环
			continue
		} else if s.IsVisit[y] {
			s.HasCircle = true
		} else {
			s.dfs(y, x)
		}
	}
}

// initData 初始化数据
func (s *Solution) initData() {
	// 初始化没有环
	s.HasCircle = false
	// 初始化变量
	s.initVisit()
	s.Edges = make([][]int, s.MaxNum+1)

	fmt.Println("IsVisit 初始化: ", s.IsVisit, "s.Edges: ", s.Edges)
	// 队每个元素进行初始化

	//for key, _ := range s.Edges {
	//	for i := 0; i < s.MaxNum; i++ {
	//		s.Edges[key] = append(s.Edges[key], 0)
	//	}
	//}

	fmt.Println("IsVisit 初始化： ", s.IsVisit, "s.MaxNum: ", s.MaxNum)
	fmt.Println("Edges 初始化： ", s.Edges, "s.MaxNum: ", s.MaxNum)
}

// getMaxNum 获得最大的数
func (s *Solution) getMaxNum(question [][]int) {
	s.MaxNum = 0
	for _, value := range question {
		fmt.Println("value: ", value)

		tmp := s.getMaxInt(value[0], value[1])
		s.MaxNum = s.getMaxInt(s.MaxNum, tmp)
	}
}

// initVisit 初始化visit
func (s *Solution) initVisit() {
	s.IsVisit = make([]bool, s.MaxNum+1)
}

// getMaxInt 比较两个int大小
func (s *Solution) getMaxInt(x, y int) int {
	if x > y {
		return x
	}

	return y
}
