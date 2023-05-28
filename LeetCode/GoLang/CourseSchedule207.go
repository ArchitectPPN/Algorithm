package main

import "fmt"

// 题目链接: https://leetcode.cn/problems/course-schedule/
func main() {

	numCourses := 3
	prerequisites := make([][]int, numCourses)
	prerequisites[0] = []int{1, 0}
	prerequisites[1] = []int{1, 2}
	prerequisites[2] = []int{0, 1}

	fmt.Println(prerequisites)

	s := new(SolutionCourseSchedule)
	s.handle(numCourses, prerequisites)

	fmt.Println("结果: ", s.topSort() == numCourses)
}

type SolutionCourseSchedule struct {
	n     int
	edges [][]int //
	inDeg []int
}

// handle
func (s *SolutionCourseSchedule) handle(numCourses int, prerequisites [][]int) {
	// 初始化数据
	s.initData(numCourses)

	// 加边
	for _, value := range prerequisites {
		ai := value[0]
		bi := value[1]

		// 学习ai之前必须学习bi
		s.addEdges(bi, ai)
	}

	fmt.Println("n:", s.n, "; edges: ", s.edges, "; inDeg:", s.inDeg)
}

// initData 初始化数据
func (s *SolutionCourseSchedule) initData(numCourses int) {
	// 课程数
	s.n = numCourses
	s.edges = make([][]int, s.n)
	s.inDeg = make([]int, s.n)

	for i := 0; i < numCourses; i++ {
		s.inDeg[i] = 0
	}

	fmt.Println("n:", s.n, "; edges: ", s.edges, "; inDeg:", s.inDeg)
}

// addEdges 加边
func (s *SolutionCourseSchedule) addEdges(x, y int) {
	s.edges[x] = append(s.edges[x], y)
	s.inDeg[y]++
}

// topSort
func (s *SolutionCourseSchedule) topSort() int {
	learned := 0
	// 拓扑排序基于BFS, 需要对列
	queue := make([]int, 0)
	// 从所有零入度点出发
	for i := 0; i < s.n; i++ {
		if s.inDeg[i] == 0 {
			queue = append(queue, i)
		}
	}

	// 执行BFS
	for len(queue) != 0 {
		x := queue[0]
		queue = queue[1:]
		learned++
		// 考虑所有出边
		for _, value := range s.edges[x] {
			// 去掉约束关系
			s.inDeg[value]--
			if s.inDeg[value] == 0 {
				queue = append(queue, value)
			}
		}
	}

	return learned
}
