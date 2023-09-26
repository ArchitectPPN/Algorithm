package main

func main() {
	solution := new(MergeSortSolution)
	solution.handle([]int{1})
}

type MergeSortSolution struct {
	ans int
}

func (m *MergeSortSolution) handle(nums []int) int {
	m.ans = 0

	m.mergeSort(nums, 0, len(nums)-1)

	return m.ans
}

func (m *MergeSortSolution) mergeSort(arr []int, left, right int) []int {
	if left >= right {
		return arr
	}

	var mid int
	// 求平均数向下取整
	mid = (left + right) >> 1
	m.mergeSort(arr, left, mid)
	m.mergeSort(arr, mid+1, right)

	m.calculate(arr, left, mid, right)

	m.merge(arr, left, mid, right)

	return arr
}

func (m *MergeSortSolution) calculate(arr []int, left, mid, right int) {
	i := left
	j := mid
	for ; i <= mid; i++ {
		for j < right && arr[i] > 2*arr[j+1] {
			j++
		}
		m.ans += j - mid
	}
}

func (m *MergeSortSolution) merge(arr []int, left, mid, right int) {
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
}
