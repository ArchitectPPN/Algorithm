package main

import "fmt"

func main() {
	str := "[]{}()"

	isValid(str)

	strArr := []string{"c", "L", "S"}

	// 获取字符串长度
	fmt.Printf("%v %v", len(str), strArr)
}

func isValid(checkString string) {

	for i, value := range checkString {
		fmt.Printf("%v %c\n", i, value)
	}
}
/*
前序遍历: FBADCEG(NULL)IH(NULL)
中序遍历: ABCDEFGHI
*/
