package main

import "fmt"

/**
* arr = [-1,0,3,5,8,10,12], target = 9

left=0 right=7 mid=3向下取整 arr[mid] = 5, 因为5小于9，所以left要向右移动，left=3+1=4
第一次查找：mid = 3，小于目标值, left = mid + 1 = 4

第二次查找：
left=4 right=7 mid=11/2=5 arr[mid] = 10
right = mid = 5, 向右移动， 移动到mid位置

第三次查找：
left=4 right=5 mid=9/2=4 arr[mid]=9, right=mid=4

return 4
*/

/**
 * 查找比目标值第一个大的数，类似二叉树里面的后继
 */
func main() {
	solution := new(LowerBoundSolution)
	ans := solution.handle([]int{-1, 0, 3, 5, 8, 10, 12}, 9)

	fmt.Println(ans)
}

type LowerBoundSolution struct {
}

func (l *LowerBoundSolution) handle(problem []int, target int) int {
	var left, right, mid int
	left = 0
	right = len(problem)

	// 开始二分查找
	for left < right {
		// /2向下取整
		mid = (left + right) >> 1
		if problem[mid] >= target {
			// 中间值大于目标值时，说明right需要向右移动，因为右边的值都比mid大，一定不是正确答案，正确答案一定再left-mid中间
			// 直接把right设置为mid
			right = mid
		} else {
			// 这个说明 mid 左边的值都小于target，所以需要向左移动，因为mid也小于target，所以跳过mid
			left = mid + 1
		}
	}

	// 为什么返回right，因为right = mid, 它包含答案
	return right
}
