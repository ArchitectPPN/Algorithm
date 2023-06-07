package main

import "fmt"

func main() {
	m := new(MinimumFeneticMutation)

	countRes := m.handle("AACCGGTT", "AACCGGTA", []string{"AACCGGTA"})

	fmt.Println(countRes)
}

type MinimumFeneticMutation struct {
	queue          []string
	depth, bankMap map[string]int
	gene           string
}

// handle
func (m *MinimumFeneticMutation) handle(startGene, endGene string, bank []string) int {
	m.initData(bank)

	m.queue = append(m.queue, startGene)
	m.depth[startGene] = 0

	var tempStr, nextStr string
	for {
		tempStr = m.queue[0]
		// 移除队头元素
		m.queue = m.queue[1:]

		for i := 0; i < 8; i++ {
			for j := 0; j < 4; j++ {
				if tempStr[i] == m.gene[j] {
					continue
				}

				// 修改其中的字符
				byteNextStr := []byte(tempStr)
				byteNextStr[i] = m.gene[j]
				nextStr = string(byteNextStr)

				// bankMap 中没有nextStr
				if !m.checkIsInMap(nextStr, m.bankMap) {
					continue
				}

				if !m.checkIsInMap(nextStr, m.depth) {
					m.depth[nextStr] = m.depth[tempStr] + 1
					m.queue = append(m.queue, nextStr)
					if nextStr == endGene {
						return m.depth[nextStr]
					}
				}

			}
		}

		if len(m.queue) <= 0 {
			break
		}
	}

	return -1
}

func (m *MinimumFeneticMutation) initData(bank []string) {
	m.gene = "ACGT"

	// 初始化queue
	m.queue = make([]string, 0)

	// 初始化访问过的数组
	m.depth = make(map[string]int, 100)
	m.bankMap = make(map[string]int)
	for val, key := range bank {
		m.bankMap[key] = val
	}
}

// checkIsInMap 判断key是否存在
func (m *MinimumFeneticMutation) checkIsInMap(mapKey string, targetMap map[string]int) bool {
	_, exist := targetMap[mapKey]
	if exist {
		return true
	}

	return false
}
