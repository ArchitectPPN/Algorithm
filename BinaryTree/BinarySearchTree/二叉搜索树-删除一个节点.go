package main

import "fmt"

func main() {

	// 初始化
	oneNode := new(TreeNode)
	oneNode.Val = 1

	twoNode := new(TreeNode)
	twoNode.Val = 2

	threeNode := new(TreeNode)
	threeNode.Val = 3

	fourNode := new(TreeNode)
	fourNode.Val = 4

	fiveNode := new(TreeNode)
	fiveNode.Val = 5

	sixNode := new(TreeNode)
	sixNode.Val = 6

	sevenNode := new(TreeNode)
	sevenNode.Val = 7

	fiveNode.Left = threeNode
	fiveNode.Right = sixNode

	threeNode.Left = twoNode
	threeNode.Right = fourNode

	sixNode.Right = sevenNode

	ansNode := deleteNode(fiveNode, 3)

	fmt.Println("ans: ", ansNode)
}

type TreeNode struct {
	Val   int
	Left  *TreeNode
	Right *TreeNode
}

func deleteNode(root *TreeNode, key int) *TreeNode {
	switch {
	// 节点为nil时，直接返回
	case root == nil:
		return nil
		// 节点值大于key时，说明要找的key在左子树
	case root.Val > key:
		root.Left = deleteNode(root.Left, key)
		// 节点值小于key时，说明要找的key在右子树
	case root.Val < key:
		root.Right = deleteNode(root.Right, key)
		// 节点值等于key时，左右子树为nil
	case root.Left == nil || root.Right == nil:
		if root.Left != nil {
			return root.Left
		}
		return root.Right
		// 节点值等于key时，左右子树都不为nil
	default:
		// 后继节点一定存在右子树的左子树上
		successor := root.Right
		// 左子树不为nil时，一直往下找，找到右子树上最小的那个val
		for successor.Left != nil {
			successor = successor.Left
		}
		// 递归的删除后继节点
		successor.Right = deleteNode(root.Right, successor.Val)
		// 将后继节点指向当前节点的左子树，完成替换
		successor.Left = root.Left
		return successor
	}
	// 最后返回root
	return root
}
