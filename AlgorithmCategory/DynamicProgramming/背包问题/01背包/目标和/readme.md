### 目标和解题思路

力扣问题链接：
- [494. 目标和](https://leetcode.cn/problems/target-sum/description/)
- [LCR 102. 目标和](https://leetcode.cn/problems/YaVDxD/)

假设给定的数组question为[1,1,1,1,1]，target为3

首先，我们可以将其拆为两个部分，left 和 right，left我们代表所有的加法合集，right代表所有的减法合集。
- 那么整个question的sum就可以表示为：`sum = left + right`；
- 那么target就可以表示为：`target = left - right`；
- right就可以表示为：`right = sum - left`；
- 我们将`right = sum - left`带入到`target = left - right`中，可以得到：`target = left - (sum - left) = 2 * left - sum`；
- 进一步转化：`target = 2 * left - sum` -> `target + sum = 2 * left` -> `(target + sum) / 2 = left`;
- 最后就可以得到：`left = (target + sum) / 2`；
- 如果`(target + sum) / 2`是整数，那么我们就可以找到一种组合可以得到left的值；否则，我们就无法找到任何组合可以得到left的值。

left的值我们就可以确定，那么我们只需要在question中找到有几种组合可以得到left的值即可。有几种组合就是我们要找的答案。问题变为01背包。

