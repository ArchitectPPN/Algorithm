package main

import "fmt"

func main() {
	buildBinaryTree := new(DelElementFromBinaryTree)
	tree := buildBinaryTree.handle()

	deleteNode := new(DelElementFromBinaryTree)
	tree = deleteNode.deleteNode(tree, 5)

	fmt.Println(tree)
}

// BinaryTree 二叉搜索树中的插入操作
type BinaryTree struct {
}

type TreeNode450 struct {
	Val   int
	Left  *TreeNode450
	Right *TreeNode450
}

// Handle 入口函数
func (s *BinaryTree) Handle(question []int) *TreeNode450 {
	root := new(TreeNode450)

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
func (s *BinaryTree) insertToBST(root *TreeNode450, val int) *TreeNode450 {
	if root == nil {
		return &TreeNode450{Val: val}
	}

	// 左边的值小于右边
	if val < root.Val {
		root.Left = s.insertToBST(root.Left, val)
	} else {
		root.Right = s.insertToBST(root.Right, val)
	}

	return root
}

type DelElementFromBinaryTree struct {
}

func (d *DelElementFromBinaryTree) handle() *TreeNode450 {
	binaryTree := new(BinaryTree)
	return binaryTree.Handle([]int{5, 3, 6, 2, 4, 7})
}

func (d *DelElementFromBinaryTree) deleteNode(root *TreeNode450, key int) *TreeNode450 {
	// nil 时直接返回
	if root == nil {
		return nil
	}

	//
	if root.Val == key {
		// 只有左子树，让right代替左子树
		if root.Left == nil {
			return root.Right
		}

		// 只有右子树
		if root.Right == nil {
			return root.Left
		}

		// 左右都有，查找后继，找有左子树一路向左
		next := root.Right
		for next.Left != nil {
			next = next.Left
		}
		root.Right = d.deleteNode(root.Right, next.Val)
		root.Val = next.Val

		return root
	}

	if key < root.Val {
		root.Left = d.deleteNode(root.Left, key)
	} else {
		root.Right = d.deleteNode(root.Right, key)
	}

	return root
}
