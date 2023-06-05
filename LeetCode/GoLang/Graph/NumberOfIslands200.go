package main

import "fmt"

// https://leetcode.cn/problems/number-of-islands/ 岛屿数量
func main() {
	grid := [][]byte{
		{49, 49, 49, 49, 48},
		{49, 49, 48, 49, 48},
		{49, 49, 48, 48, 48},
		{48, 48, 48, 48, 48},
	}

	resolve := new(NumberOfIslandsSolution)
	resolve.handle(grid)

	fmt.Println(resolve.ans)
}

type NumberOfIslandsSolution struct {
	m     int
	n     int
	visit [][]bool
	dx    [4]int
	dy    [4]int

	ans int
}

func (n *NumberOfIslandsSolution) initData(grid [][]byte) {
	n.m = len(grid)
	n.n = len(grid[0])

	n.ans = 0

	n.dx = [4]int{-1, 0, 0, 1}
	n.dy = [4]int{0, -1, 1, 0}

	n.visit = make([][]bool, n.m)

	for i := 0; i < n.m; i++ {
		temp := make([]bool, n.n)
		for j := 0; j < n.n; j++ {
			temp[j] = false
		}
		n.visit[i] = temp
	}
}

func (n *NumberOfIslandsSolution) handle(grid [][]byte) int {
	// 初始化数据
	n.initData(grid)

	for x := 0; x < n.m; x++ {
		for y := 0; y < n.n; y++ {
			if grid[x][y] == 49 && !n.visit[x][y] {
				n.dfs(grid, x, y)
				n.ans++
			}
		}
	}

	return n.ans
}

func (n *NumberOfIslandsSolution) dfs(grid [][]byte, x, y int) {
	// 第一步, 标记已访问
	n.visit[x][y] = true

	for i := 0; i < 4; i++ {
		nx := x + n.dx[i]
		ny := y + n.dy[i]
		if nx < 0 || ny < 0 || nx >= n.m || ny >= n.n {
			continue
		}

		if grid[nx][ny] == 49 && !n.visit[nx][ny] {
			n.dfs(grid, nx, ny)
		}
	}
}
