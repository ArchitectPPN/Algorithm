package main

import "fmt"

func main() {
	// 初始化tree
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

	oneNode.Left = twoNode
	oneNode.Right = fiveNode
	twoNode.Left = threeNode
	twoNode.Right = fourNode
	fiveNode.Right = sixNode

	flatten(oneNode)

	fmt.Println(oneNode)
}

type TreeNode struct {
	Val   int
	Left  *TreeNode
	Right *TreeNode
}

func flatten(root *TreeNode) {
	for root != nil {
		// 我们要把左子树移到右子树上去，如果左子树本身就是nil，说明当前节点无需操作，继续下一个节点即可
		if root.Left == nil {
			root = root.Right
		} else {
			// 处理左子树
			pre := root.Left
			// 找到左子树的最右节点
			for pre.Right != nil {
				pre = pre.Right
			}
			// 需要注意的是， pre在这里是指针， 它的变化会导致root.Left本身发生变化
			// 把当前节点的右子树放到pre的右子树上去，也就是pre.Right指向root.Right
			// 这里其实就是存了一下root.Right
			pre.Right = root.Right
			// 处理root本身节点，将root.Left放到root.Right
			root.Right = root.Left
			// 删除root.Left
			root.Left = nil

			// 继续处理下一个节点
			// 记住这里处理的是新生成的二叉树的右子树
			root = root.Right
		}
	}
}
