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
	solution := new(UpperBound2Solution)
	ans := solution.handle([]int{2, 3, 5, 8, 10, 12}, 90)

	fmt.Println(ans)
}

type UpperBound2Solution struct {
}

func (l *UpperBound2Solution) handle(problem []int, target int) int {
	var left, right, mid int
	left = 0
	right = len(problem) - 1

	// 开始二分查找
	for left+1 < right {
		// /2向下取整
		mid = (left + right) >> 1
		if problem[mid] <= target {
			left = mid
		} else {
			right = mid
		}
	}

	// 因为这里要取比target小的数
	if problem[right] <= target {
		return right
	} else if problem[left] <= target {
		return left
	}

	return -1
}
