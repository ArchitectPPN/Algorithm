package main

import "fmt"

func main() {
	arr1 := []int{4, 5, 6, 0, 0, 0}
	arr2 := []int{1, 2, 3}

	solution := new(mergeSortedArr)
	solution.merge(arr1, 3, arr2, 3)

	fmt.Println("merge arr:", arr1)
}

type mergeSortedArr struct {
}

// merge 合并两个有序集合
func (m *mergeSortedArr) merge(arr1 []int, arr1Length int, arr2 []int, arr2Length int) {
	i := arr1Length - 1
	j := arr2Length - 1

	for loop := (arr1Length + arr2Length) - 1; loop >= 0; loop-- {
		if j < 0 || i >= 0 && arr1[i] >= arr2[j] {
			arr1[loop] = arr1[i]
			i--
		} else {
			arr1[loop] = arr2[j]
			j--
		}
	}
}
