package main

import "fmt"

func main() {
	grid := [][]int{{0, 0, 0}, {0, 1, 0}, {0, 0, 0}}

	solution := new(DiffGrid2)
	ans := solution.handle(grid)

	fmt.Println("ans:", ans)
}

type DiffGrid2 struct {
}

/*
	0 0 0
	0 1 0
	0 0 0
*/

func (d *DiffGrid2) handle(obstacleGrid [][]int) int {
	n, m := len(obstacleGrid), len(obstacleGrid[0])
	f := make([]int, m)
	// 起始位置是0，默认为1
	if obstacleGrid[0][0] == 0 {
		f[0] = 1
	}
	for i := 0; i < n; i++ {
		for j := 0; j < m; j++ {
			// 如果当前位置是障碍物，就是0
			if obstacleGrid[i][j] == 1 {
				f[j] = 0
				continue
			}
			if j-1 >= 0 && obstacleGrid[i][j-1] == 0 {
				f[j] += f[j-1]
			}
		}
	}
	return f[len(f)-1]
}
