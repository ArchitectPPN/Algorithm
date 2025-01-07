package BloomFilter

import (
	"hash"
)

type BloomFilter struct {
	Bytes  []byte
	Hashes []hash.Hash64
}

func (bf *BloomFilter) AddElement(cusStr []byte) {
	var byteLen = len(bf.Bytes)
	for _, funcName := range bf.Hashes {
		// 初始化随机数种子
		funcName.Reset()
		funcName.Write(cusStr)
		var res = funcName.Sum64()
		var yByte = res % uint64(byteLen)
		var yBit = res % 8
		// 遇到大端模式CPU可能会出问题 BUG
		bf.Bytes[yByte] |= 1 << yBit
	}
}

func (bf *BloomFilter) RemoveElement(cusStr []byte) bool {
	var byteLen = len(bf.Bytes)
	for _, funcName := range bf.Hashes {
		// 初始化随机数种子
		funcName.Reset()
		funcName.Write(cusStr)

		var res = funcName.Sum64()
		var yByte = res % uint64(byteLen)
		var yBit = res % 8

		// 遇到大端模式CPU可能会出问题 BUG
		if bf.Bytes[yByte]|1<<yBit != bf.Bytes[yByte] {
			return false
		}

	}

	return true
}
