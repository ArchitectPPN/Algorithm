package main

import (
	"fmt"
	"math/rand"
)

func main() {

	waitSortNums := []int{9, 10, 50, 1, 5, 6, 30}

	solution := new(quickSortRepeat3)
	solution.quickSort(waitSortNums, 0, len(waitSortNums)-1)

	fmt.Println("ans:", waitSortNums)
}

type quickSortRepeat3 struct {
}

func (q *quickSortRepeat3) quickSort(nums []int, left, right int) {
	// 这样也能防止出现空数组的情况
	if left >= right {
		return
	}
	pivot := q.partition(nums, left, right)
	q.quickSort(nums, left, pivot)
	q.quickSort(nums, pivot+1, right)
}

// partition 处理分区内的排序
func (q *quickSortRepeat3) partition(nums []int, left, right int) int {
	pivot := q.midPivot(left, right)
	pivotVal := nums[pivot]

	for left <= right {
		for nums[left] < pivotVal {
			left++
		}

		for nums[right] > pivotVal {
			right--
		}

		if left <= right {
			tmpVal := nums[left]
			nums[left] = nums[right]
			nums[right] = tmpVal

			right--
			left++
		}
	}

	return right
}

// midPivot 选举中间值
func (q *quickSortRepeat3) midPivot(left, right int) int {
	// 获取一个0-1之间的随机数
	random := rand.Float64()
	// 这里加1，举例来说： 如果为上面第2中情况，如果不加1，拿不到 pivot = 6的情况，因为 diff 恒等于2， 然后 2*(0-1之间的数)，int之后一直等于1或者0，
	// 加1之后变为3*(0-1之间的数),有很大概率拿到2，最后pivot=6
	diff := float64(right - left + 1)
	// random * float64(right-left+1) ， 举例：0.5 * （4-3+1） = 0.5 * 2 = 1， 最后的结果会在 0-2之间
	temVal := random * diff
	//
	pivot := left + int(temVal)

	return pivot
}
