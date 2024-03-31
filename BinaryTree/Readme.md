## 二叉树的遍历
### 前序遍历
顺序：根节点 -> 左子树 -> 右子树
```php
function preOrder($root) {
    echo "{$root->val}";
    // 遍历左子树
    preOrder($root->left);
    // 遍历右子树
    preOrder($root->right);
}
```

### 中序遍历
顺序：左子树 -> 根节点 -> 右子树
```php
function inOrder($root) {
    // 遍历左子树
    inOrder($root->left);
    echo "{$root->val}";
    // 遍历右子树
    inOrder($root->right);
}
```

### 后续遍历
顺序：左子树 -> 右子树 -> 根节点
```php
function postOrder($root) {
    // 遍历左子树
    postOrder($root->left);
    // 遍历右子树
    postOrder($root->right);
    echo "{$root->val}";
}
```

### 层序遍历
层序遍历就是逐层遍历树结构。
广度优先搜索是一种广泛运用在树或图这类数据结构中，遍历或搜索的算法。 该算法从一个根节点开始，首先访问节点本身。 然后遍历它的相邻节点，其次遍历它的二级邻节点、三级邻节点，以此类推。

当我们在树中进行广度优先搜索时，我们访问的节点的顺序是按照层序遍历顺序的。

通常，我们使用一个叫做队列的数据结构来帮助我们做广度优先搜索。

```php
class Solution1 {
    // 用来存放最终的答案
    private $ans = [];
    // 记录当前递归到的层数
    private $level = 0;
    
    /**
     * @param TreeNode $root
     * @return Integer[][]
     */
    function levelOrder($root) {
        if (is_null($root)) {
            return $this->ans;
        }

        //// 一进来就加一层，会影响最终答案的下标从0还是从1开始
        /// 可以认为根节点就是第1层
       
        //$this->level++;

        // 一进来就将根节点的值放入ans中
        $this->ans[$this->level][] = $root->val;
        
        // 开始循环下一层
        // 在这里开始加1，认为根节点是第0层
        $this->level++;
        
        // 遍历左子树
        $this->levelOrder($root->left);
        // 遍历右子树
        $this->levelOrder($root->right);
        
        // 当前层遍历结束后，要将层数恢复，不然层数会一直加
        // 最终导致属于同一层的数据放入到不同层
        $this->level--;

        return $this->ans;
    }
}
```

```php
# 与solution1相比，少了一个用来控制层数的变量level，每层循环独享一个层数控制
class Solution2 {
    private $ans = [];
    /**
     * @param TreeNode $root
     * @return Integer[][]
     */
    function levelOrder($root) {
        // 直接传入层数，这样就不需要单独声明一个变量来记录当前层数
        $this->eachElement($root, 0);

        return $this->ans;
    }

    function eachElement($root, $index) {
        // 当前节点为null， 直接返回ans，无需任何操作
        if (is_null($root)) {
            return $this->ans;
        }

        // 将当前节点的值放入ans
        $this->ans[$index][] = $root->val;
        // 遍历左子树
        $this->eachElement($root->left, $index+1);
        // 遍历右子树
        $this->eachElement($root->right, $index+1);

        // 返回结果
        return $this->ans;
    }
}
```
