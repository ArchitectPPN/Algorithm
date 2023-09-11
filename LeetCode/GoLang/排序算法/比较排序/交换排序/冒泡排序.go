package main

import "fmt"

func main() {
	solution := new(popSortSolution)
	arr := solution.handle([]int{9, 8, 7, 9, 5, 4, 3, 2, 1})

	fmt.Println("ans: ", arr)
}

type popSortSolution struct {
}

// handle 冒泡排序，两两比较
func (p *popSortSolution) handle(arr []int) []int {

	var temp int
	for i := 0; i < len(arr); i++ {
		for j := 0; j < len(arr)-1; j++ {
			if arr[j] > arr[j+1] {
				// 交换顺序
				temp = arr[j]
				arr[j] = arr[j+1]
				arr[j+1] = temp
			}
		}
	}

	return arr
}
