package main

import "fmt"

/**
* arr = [2, 3, 5, 8, 10, 12], target = 1

第一次查找
left=-1 right=5 mid=2向下取整 arr[mid] = 5, 因为5大于1，
所以right要向左移动，right= mid - 1 = 1，因为mid都大于1，所以丢弃mid，
拿不确定是否大于target的mid前一个下标的值

第二次查找，因为left上次没有移动，所以还是保持等于-1
left=-1 right=1 mid=(-1+1+1) / 2=0，arr[mid] = 2
arr[mid]依旧大于target，所以继续向左移动， right = 0 - 1 = -1

第三次查找：
left=-1 right=-1 因为-1 不小于 -1，循环停止，查找的数字不存在

return -1
*/

/**
 * 查找比目标值小的最后一个值，类比前驱
 */
func main() {
	solution := new(LowerBound2Solution)
	ans := solution.handle([]int{2, 3, 5, 8, 10, 12}, 1)

	fmt.Println(ans)
}

type LowerBound2Solution struct {
}

func (l *LowerBound2Solution) handle(problem []int, target int) int {
	var left, right, mid int
	left = -1
	right = len(problem) - 1

	// 开始二分查找
	for left < right {
		// /2向下取整
		mid = (left + right + 1) >> 1
		if problem[mid] <= target {
			// 中间值小于目标值时，left到mid中间的数都小于target，但不一定时最大的那个，让left继续向右移动，
			// 因为右边的值都比mid大，所以答案一定存在mid到right之间
			// 将left移动到中间位置
			left = mid
		} else {
			// 说明right到mid之间的值都是大于target，正确答案一定不在此区间内，所以将right向左移动，mid大于target不是正确答案，丢弃
			// 所以将right赋值为mid前一位
			right = mid - 1
		}
	}

	return right
}
