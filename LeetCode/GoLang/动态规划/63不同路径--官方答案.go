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

	// 把每一层的结果都扫描出来
	for i := 0; i < n; i++ {
		for j := 0; j < m; j++ {
			// 当前位置是障碍物， 所以路径总和为0， 障碍物无法到达
			if obstacleGrid[i][j] == 1 {
				f[j] = 0
				continue
			}
			// 因为当前层数当前位置的路径总和为： 上一层j加当前层j-1得到答案；也就是：(i, j) = (i - 1, j) + (i, j - 1)
			if j-1 >= 0 && obstacleGrid[i][j-1] == 0 {
				f[j] += f[j-1]
			}
		}
	}
	return f[len(f)-1]
}
