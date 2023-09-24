package main

import (
	"fmt"
	"sort"
)

func main() {
	test := [][]int{{1, 3}, {1, 2}, {8, 10}, {15, 18}}
	sort.Slice(test, func(i, j int) bool {
		return test[i][0] < test[j][0] || test[i][0] == test[j][0] && test[i][1] < test[j][1]
	})

	fmt.Println(test)
}
