package main

import "fmt"

/**
解题思路：
	1. 首先获取到整数矩阵的长度和每个元素的长度
	2. 定义移动方向
	3. 创建一个数组来存储已经走过的点，直接存储该点的最长距离， 一开始将其所有值初始化为 -1， 代表未走到

*/

func main() {
	problem := [][]int{{3, 4, 5}, {3, 2, 6}, {2, 2, 1}}

	solution := new(longestIncreasingPathInAMatrixSolution)
	ans := solution.solution(problem)

	fmt.Println("ans :", ans)
}

// longestIncreasingPathInAMatrixSolution 矩阵中的最长递增路径解决方案
type longestIncreasingPathInAMatrixSolution struct {
	m, n        int
	dx, dy      [4]int  // 方向
	howFarSlice [][]int // 用来记录每个坐标可以走多远
	ans         int     // 最终的答案
}

// solution 解决方案
func (s *longestIncreasingPathInAMatrixSolution) solution(matrix [][]int) int {
	// 初始化
	s.initData(matrix)
	// 初始答案
	s.ans = 0

	// 开始遍历
	for i := 0; i < s.m; i++ {
		for j := 0; j < s.n; j++ {
			s.ans = s.max(s.ans, s.howFar(matrix, i, j))
		}
	}

	return s.ans
}

// initAnswerData
func (s *longestIncreasingPathInAMatrixSolution) initData(matrix [][]int) {
	// 计算 matrix 总的元素个数
	s.m = len(matrix)
	// 每个子元素的个数
	s.n = len(matrix[0])

	// 初始化方向数组
	// 这里的方向和坐标轴方向不要混为一谈， 参考二维数组下标
	// x 代表行 y 代表列
	// 行数-1 列数不变 就是向上 U
	// 行数不变 列数-1 就是向左 L
	// 行数不变 列数+1 就是向右 R
	// 行数+1 列数不变 就是向下 D
	s.dx = [4]int{-1, 0, 0, 1}
	s.dy = [4]int{0, -1, 1, 0}

	s.howFarSlice = make([][]int, s.m)

	// 初始化每个坐标的结果
	for i := 0; i < s.m; i++ {
		if s.howFarSlice[i] == nil {
			s.howFarSlice[i] = make([]int, s.n)
		}
		for j := 0; j < s.n; j++ {
			s.howFarSlice[i][j] = -1
		}
	}
}

// howFar 计算每个坐标能走多远
func (s *longestIncreasingPathInAMatrixSolution) howFar(matrix [][]int, x, y int) int {
	// 如果已经走过了， 直接返回结果
	if s.howFarSlice[x][y] != -1 {
		return s.howFarSlice[x][y]
	}
	// 默认就是一步
	s.howFarSlice[x][y] = 1

	// 开始向每个方向出发
	for i := 0; i < 4; i++ {
		nx := x + s.dx[i]
		ny := y + s.dy[i]

		// 防止越界
		if nx < 0 || ny < 0 || nx >= s.m || ny >= s.n {
			continue
		}

		// 下个点必须大于当前点的值才可以继续
		if matrix[nx][ny] > matrix[x][y] {
			s.howFarSlice[x][y] = s.max(s.howFarSlice[x][y], s.howFar(matrix, nx, ny)+1)
		}

	}

	return s.howFarSlice[x][y]
}

func (s *longestIncreasingPathInAMatrixSolution) max(x, y int) int {
	if x > y {
		return x
	}

	return y
}
