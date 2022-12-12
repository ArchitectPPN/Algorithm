package main

import (
	"fmt"
	"sort"
	"strings"
)

// 排序解法
func main() {
	question := [6]string{"eat", "tea", "tan", "ate", "nat", "bat"}

	// 申请一个map
	strMapping := make(map[string][]string, 10)

	// 循环处理
	for _, value := range question {
		key := sortStrings(value)
		if _, ok := strMapping[key]; !ok {
			strMapping[key] = []string{}
		}
		strMapping[key] = append(strMapping[key], value)
	}

	// 将答案塞入到最后答案数组中
	var ans [][]string
	for _, value := range strMapping {
		ans = append(ans, value)
	}

	fmt.Println(strMapping, ans)
}

// 给字符串排序
func sortStrings(str string) string {
	// 切分字符串
	split := strings.Split(str, "")

	// 排序
	sort.Strings(split)

	// 组装string
	return strings.Join(split, "")
}
