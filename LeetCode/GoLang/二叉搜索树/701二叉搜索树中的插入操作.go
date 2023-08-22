package main

import "fmt"

func main() {
	question := []int{4, 2, 7, 1, 3}

	solution := new(BinarySearchTreeSolution)
	root := solution.Handle(question)

	fmt.Println(root)
}

// BinarySearchTreeSolution 二叉搜索树中的插入操作
type BinarySearchTreeSolution struct {
}

// Handle 入口函数
func (s *BinarySearchTreeSolution) Handle(question []int) *TreeNode701 {
	binarySearchTree := new(BuildBinarySearchTree)

	return binarySearchTree.Handle(question)
}

type TreeNode701 struct {
	Val   int
	Left  *TreeNode701
	Right *TreeNode701
}

// BuildBinarySearchTree 二叉搜索树中的插入操作
type BuildBinarySearchTree struct {
}

// Handle 入口函数
func (s *BuildBinarySearchTree) Handle(question []int) *TreeNode701 {
	root := new(TreeNode701)

	for key, val := range question {
		if key == 0 {
			root.Val = val
		} else {
			root = s.insertToBST(root, val)
		}
	}

	return root
}

// InsertToBST 向二叉树中插入元素
func (s *BuildBinarySearchTree) insertToBST(root *TreeNode701, val int) *TreeNode701 {
	if root == nil {
		return &TreeNode701{Val: val}
	}

	// 左边的值小于右边
	if val < root.Val {
		root.Left = s.insertToBST(root.Left, val)
	} else {
		root.Right = s.insertToBST(root.Right, val)
	}

	return root
}
