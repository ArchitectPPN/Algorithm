package main

import (
	"fmt"
)

// https://leetcode.cn/problems/letter-combinations-of-a-phone-number/
func main() {
	digits := "234"

	fmt.Println(string(digits[1]))

	l := new(LetterCombinationsOfAPhoneNumberSolution)

	fmt.Println(l.handle(digits))
}

type LetterCombinationsOfAPhoneNumberSolution struct {
	numberStrMap map[string]string
	answer       []string
	processStr   string
}

// handle
func (l *LetterCombinationsOfAPhoneNumberSolution) handle(digits string) []string {
	if digits == "" {
		return []string{}
	}

	l.initData()

	l.dfs(digits, 0)

	return l.answer
}

// initData 初始化
func (l *LetterCombinationsOfAPhoneNumberSolution) initData() {
	l.numberStrMap = make(map[string]string, 8)

	l.numberStrMap["2"] = "abc"
	l.numberStrMap["3"] = "def"
	l.numberStrMap["4"] = "ghi"
	l.numberStrMap["5"] = "jkl"
	l.numberStrMap["6"] = "mno"
	l.numberStrMap["7"] = "pqrs"
	l.numberStrMap["8"] = "tuv"
	l.numberStrMap["9"] = "wxyz"

	l.processStr = ""
}

// dfs
func (l *LetterCombinationsOfAPhoneNumberSolution) dfs(digits string, start int) {
	if start == len(digits) {
		l.answer = append(l.answer, l.processStr)
		return
	}

	for _, value := range l.numberStrMap[string(digits[start])] {
		l.processStr += string(value)
		l.dfs(digits, start+1)
		l.processStr = l.processStr[:len(l.processStr)-1]
	}
}
