package main

import "fmt"

func main() {
	solution := new(inorderSuccessorSolution)
	tree := solution.Handle()

	fmt.Println(tree)
}

// TreeNode 二叉搜索树
type TreeNode struct {
	Val   int
	Left  *TreeNode
	Right *TreeNode
}

// BinarySearchTree 创建一个二叉搜索树
type BinarySearchTree struct {
}

// Handle 入口函数
func (b *BinarySearchTree) Handle(question []int) *TreeNode {
	root := new(TreeNode)

	for key, val := range question {
		if key == 0 {
			root.Val = val
		} else {
			root = b.insertIntToBST(root, val)
		}
	}

	return root
}

// InsertToBST 向二叉树中插入元素
func (b *BinarySearchTree) insertIntToBST(root *TreeNode, val int) *TreeNode {
	if root == nil {
		return &TreeNode{Val: val}
	}

	// 左边的值小于右边
	if val < root.Val {
		root.Left = b.insertIntToBST(root.Left, val)
	} else {
		root.Right = b.insertIntToBST(root.Right, val)
	}

	return root
}

type inorderSuccessorSolution struct {
}

func (i *inorderSuccessorSolution) Handle() *TreeNode {
	binarySearchTree := new(BinarySearchTree)
	tree := binarySearchTree.Handle([]int{2, 1, 3})

	return i.FindInOrderScc(tree, 1)
}

// FindInOrderScc 查找后驱
func (i *inorderSuccessorSolution) FindInOrderScc(root *TreeNode, val int) *TreeNode {
	var curr *TreeNode
	var ans *TreeNode
	curr = root

	for curr != nil {
		// 当后继结点存在于经过的点中时
		if curr.Val > val && (ans == nil || ans.Val > curr.Val) {
			ans = curr
		}

		// 找到P点，顺着P的右子树一路向左
		if curr.Val == val {
			if curr.Right != nil {
				curr = curr.Right
				for curr.Left != nil {
					curr = curr.Left
				}
				return curr
			}

			break
		}

		// P比当前值小往左走
		if curr.Val > val {
			curr = curr.Left
		} else {
			curr = curr.Right
		}
	}

	return root
}
