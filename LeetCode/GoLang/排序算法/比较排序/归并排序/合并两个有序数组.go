package main

import "fmt"

func main() {
	arr := []int{7, 8, 9, 5, 6}

	mid := (0 + len(arr) - 1) >> 1

	arr = merge(arr, 0, mid, len(arr)-1)

	fmt.Println(arr)
}

/*
1. 有个数组 []int{7, 8, 9, 5, 6}
2. 我们将该数组切分为两个 {7, 8, 9} {5,6}
3. 所以merge函数入参就是 arr: []int{7, 8, 9, 5, 6} left:0 从0开始 right:4 下标到4
第一步：mid = 2; j = 3, i = 0 比较两个数组的开头 i = 0 对应的值为 7 , j = 3 对应的值为 5， 因为7 > 5,
所以将 arr[j] 放入到 temp[0]的位置，将j++，当前j=4，也就是将第二个数组{5, 6}的下标往右移动，再次和第一个数组的第一个位置比较
第二步：i = 0 j = 4 arr[0] = 7 依旧大于 arr[4] = 6, 所以j继续向右移动，此时j已将超过下标4，所以说明数组{5, 6}元素已经移动完毕了，里面已经
没有其他元素了，我们将{7, 8, 9}所有的元素都移动到temp中就完成移动；
*/
func merge(arr []int, left, mid, right int) []int {
	var temp []int
	var i, j int
	i = left
	j = mid + 1
	temp = make([]int, right-left+1)

	for k := 0; k < len(temp); k++ { // 合并两个有序数组
		if j > right || i <= mid && arr[i] <= arr[j] {
			temp[k] = arr[i]
			i += 1
		} else {
			temp[k] = arr[j]
			j += 1
		}
	}

	for k := 0; k < len(temp); k++ {
		arr[left+k] = temp[k]
	}

	return arr
}
