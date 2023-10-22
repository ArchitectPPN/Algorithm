package main

import "fmt"

func main() {
	grid := [][]int{{0, 0, 0}, {0, 1, 0}, {0, 0, 0}}

	solution := new(DiffGrid)
	ans := solution.handle(grid)

	fmt.Println("ans:", ans)
}

type DiffGrid struct {
}

func (d *DiffGrid) handle(grid [][]int) int {
	n := len(grid)
	m := len(grid[0])

	f := d.initF(m, n)
	fmt.Println("f:", f)

	for i := 0; i < n; i++ {
		for j := 0; j < m; j++ {
			// 说明碰到障碍物
			if f[i][j] == 1 {
				f[i][j] = 0
			} else if i == 0 && j == 0 {
				// 处于开始的位置
				f[i][j] = 1
			} else if i == 0 {
				// 向下移动
				f[i][j] = f[i][j-1]
			} else if j == 0 {
				f[i][j] = f[i-1][j]
			} else {
				f[i][j] = f[i][j-1] + f[i-1][j]
			}
		}
	}
	fmt.Println(f)
	return f[n-1][m-1]
}

func (d *DiffGrid) initF(m, n int) [][]int {
	var f [][]int
	f = make([][]int, n)

	for i := 0; i < n; i++ {
		f[i] = make([]int, m)
	}

	return f
}
