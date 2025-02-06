以下是为期 **12 周**的 LeetCode 刷题计划，旨在系统性提升算法能力，覆盖高频题型和核心算法思想。计划分为 **基础巩固**、**分类突破**、**高频冲刺** 三个阶段，每周安排明确目标，适合每天投入 **1~2 小时**。

---

### **阶段一：基础巩固（第 1-4 周）**
**目标**：掌握基础数据结构与简单算法，建立解题思维。

#### **第 1 周：数组与字符串**
- **重点题型**：双指针、滑动窗口、前缀和
- **每日任务**（每天 2 题）：
    - **必刷题**：
        1. [两数之和](https://leetcode.cn/problems/two-sum/)（哈希）
        2. [移动零](https://leetcode.cn/problems/move-zeroes/)（双指针）
        3. [最长无重复子串](https://leetcode.cn/problems/longest-substring-without-repeating-characters/)（滑动窗口）
        4. [盛最多水的容器](https://leetcode.cn/problems/container-with-most-water/)（对撞双指针）
        5. [反转字符串](https://leetcode.cn/problems/reverse-string/)（原地操作）
    - **学习资源**：
        - 《算法导论》第 2 章（循环不变式）
        - [LeetCode 数组专题](https://leetcode.cn/tag/array/problemset/)

#### **第 2 周：链表与栈/队列**
- **重点题型**：虚拟头节点、快慢指针、栈实现
- **每日任务**：
    - **必刷题**：
        1. [反转链表](https://leetcode.cn/problems/reverse-linked-list/)（迭代/递归）
        2. [环形链表](https://leetcode.cn/problems/linked-list-cycle/)（快慢指针）
        3. [有效的括号](https://leetcode.cn/problems/valid-parentheses/)（栈匹配）
        4. [最小栈](https://leetcode.cn/problems/min-stack/)（辅助栈）
        5. [用栈实现队列](https://leetcode.cn/problems/implement-queue-using-stacks/)
    - **学习资源**：
        - [可视化链表操作](https://visualgo.net/zh/list)

#### **第 3 周：哈希表与集合**
- **重点题型**：哈希冲突处理、空间换时间
- **每日任务**：
    - **必刷题**：
        1. [字母异位词分组](https://leetcode.cn/problems/group-anagrams/)（哈希键设计）
        2. [两数组交集](https://leetcode.cn/problems/intersection-of-two-arrays/)（集合操作）
        3. [快乐数](https://leetcode.cn/problems/happy-number/)（哈希判环）
        4. [四数相加 II](https://leetcode.cn/problems/4sum-ii/)（分组哈希）
    - **学习技巧**：
        - 哈希表的时间复杂度分析（平均 O(1) vs 最坏 O(n)）

#### **第 4 周：递归与分治**
- **重点题型**：递归终止条件、分治合并
- **每日任务**：
    - **必刷题**：
        1. [合并两个有序链表](https://leetcode.cn/problems/merge-two-sorted-lists/)（递归/迭代）
        2. [Pow(x, n)](https://leetcode.cn/problems/powx-n/)（快速幂）
        3. [多数元素](https://leetcode.cn/problems/majority-element/)（分治统计）
        4. [二叉树的最大深度](https://leetcode.cn/problems/maximum-depth-of-binary-tree/)（递归遍历）
    - **学习资源**：
        - 《算法图解》第 4 章（分治思想）

---

### **阶段二：分类突破（第 5-8 周）**
**目标**：掌握高频算法模板（DFS/BFS、动态规划、贪心）。

#### **第 5 周：二叉树与DFS/BFS**
- **重点题型**：前中后序遍历、层序遍历、路径问题
- **每日任务**：
    - **必刷题**：
        1. [二叉树的中序遍历](https://leetcode.cn/problems/binary-tree-inorder-traversal/)（迭代/递归）
        2. [对称二叉树](https://leetcode.cn/problems/symmetric-tree/)（递归/队列）
        3. [二叉树的最大路径和](https://leetcode.cn/problems/binary-tree-maximum-path-sum/)（后序遍历）
        4. [岛屿数量](https://leetcode.cn/problems/number-of-islands/)（DFS/BFS）
    - **模板总结**：
      ```python
      # BFS 层序遍历模板
      from collections import deque
      def bfs(root):
          queue = deque([root])
          while queue:
              level_size = len(queue)
              for _ in range(level_size):
                  node = queue.popleft()
                  # 处理节点
                  if node.left: queue.append(node.left)
                  if node.right: queue.append(node.right)
      ```

#### **第 6 周：回溯与组合问题**
- **重点题型**：排列、组合、子集、剪枝优化
- **每日任务**：
    - **必刷题**：
        1. [全排列](https://leetcode.cn/problems/permutations/)（回溯模板）
        2. [组合总和](https://leetcode.cn/problems/combination-sum/)（去重剪枝）
        3. [子集](https://leetcode.cn/problems/subsets/)（二进制掩码/回溯）
        4. [括号生成](https://leetcode.cn/problems/generate-parentheses/)（剪枝条件）
    - **技巧**：
        - 回溯时间复杂度分析（O(n!) 或 O(2^n)）

#### **第 7 周：动态规划（一维/二维）**
- **重点题型**：背包问题、字符串编辑距离、路径问题
- **每日任务**：
    - **必刷题**：
        1. [爬楼梯](https://leetcode.cn/problems/climbing-stairs/)（一维 DP）
        2. [最长递增子序列](https://leetcode.cn/problems/longest-increasing-subsequence/)（O(n logn) 优化）
        3. [零钱兑换](https://leetcode.cn/problems/coin-change/)（完全背包）
        4. [编辑距离](https://leetcode.cn/problems/edit-distance/)（二维 DP）
    - **模板总结**：
      ```python
      # 二维 DP 模板（以编辑距离为例）
      dp = [[0]*(n+1) for _ in range(m+1)]
      for i in range(m+1):
          for j in range(n+1):
              if i == 0: dp[i][j] = j
              elif j == 0: dp[i][j] = i
              else:
                  dp[i][j] = min(
                      dp[i-1][j] + 1,
                      dp[i][j-1] + 1,
                      dp[i-1][j-1] + (s1[i-1] != s2[j-1])
                  )
      ```

#### **第 8 周：贪心算法与堆**
- **重点题型**：区间调度、任务调度、Top K 问题
- **每日任务**：
    - **必刷题**：
        1. [合并区间](https://leetcode.cn/problems/merge-intervals/)（排序贪心）
        2. [任务调度器](https://leetcode.cn/problems/task-scheduler/)（贪心策略）
        3. [前 K 个高频元素](https://leetcode.cn/problems/top-k-frequent-elements/)（堆/桶排序）
        4. [跳跃游戏](https://leetcode.cn/problems/jump-game/)（贪心覆盖）
    - **学习资源**：
        - 《算法导论》第 16 章（贪心选择性质）

---

### **阶段三：高频冲刺（第 9-12 周）**
**目标**：刷透高频题，提升手写代码速度和准确性。

#### **第 9-10 周：LeetCode 热题 HOT 100**
- **每日任务**：每天 3 题，优先选择中等难度
    - **精选题目**：
        1. [LRU 缓存](https://leetcode.cn/problems/lru-cache/)（哈希+双向链表）
        2. [三数之和](https://leetcode.cn/problems/3sum/)（排序+双指针）
        3. [合并K个升序链表](https://leetcode.cn/problems/merge-k-sorted-lists/)（分治/堆）
        4. [打家劫舍 III](https://leetcode.cn/problems/house-robber-iii/)（树形 DP）
    - **刷题策略**：
        - 第一遍独立完成，限时 30 分钟/题
        - 第二遍复习错题，总结模板

#### **第 11 周：剑指 Offer 精选**
- **每日任务**：每天 4 题，覆盖面试经典题
    - **必刷题**：
        1. [二维数组中的查找](https://leetcode.cn/problems/er-wei-shu-zu-zhong-de-cha-zhao-lcof/)（线性扫描）
        2. [重建二叉树](https://leetcode.cn/problems/zhong-jian-er-cha-shu-lcof/)（前序+中序）
        3. [数值的整数次方](https://leetcode.cn/problems/shu-zhi-de-zheng-shu-ci-fang-lcof/)（快速幂）
        4. [二叉搜索树与双向链表](https://leetcode.cn/problems/er-cha-sou-suo-shu-yu-shuang-xiang-lian-biao-lcof/)（中序遍历）

#### **第 12 周：模拟面试与总结**
- **每日任务**：
    1. **模拟面试**：使用 [LeetCode 模拟面试功能](https://leetcode.cn/interview/) 或 [Pramp](https://www.pramp.com/)，每天 1 场（45 分钟）。
    2. **错题本复习**：回顾前 11 周的错题，手写代码。
    3. **总结模板**：整理高频题型的代码模板（如二分查找、DFS 回溯等）。

---

### **刷题方法论**
1. **五步刷题法**：
    - Step 1：独立思考 15 分钟，尝试暴力解法。
    - Step 2：阅读题解，理解最优解思路。
    - Step 3：手写代码，确保无语法错误。
    - Step 4：对比他人代码，学习优化技巧。
    - Step 5：总结到笔记（如 Notion/语雀），标注易错点。

2. **高效工具**：
    - [VisuAlgo](https://visualgo.net/zh)：算法可视化
    - [LeetCode 题解](https://leetcode.cn/problemset/all/?topicSlugs=array)：精选题解
    - [代码模板库](https://github.com/greyireland/algorithm-pattern)

3. **时间管理**：
    - 固定每日刷题时间（如早晨 1 小时）。
    - 零碎时间复习笔记（通勤、午休）。

---

### **附加资源**
- **书籍推荐**：
    - 《剑指 Offer》
    - 《算法导论》（CLRS）
    - 《代码随想录》
- **在线课程**：
    - [LeetCode 例题精讲](https://leetcode.cn/leetbook/)
    - [MIT 6.006 算法导论](https://ocw.mit.edu/courses/6-006-introduction-to-algorithms-fall-2011/)

---

通过此计划，你将在 3 个月内系统覆盖 90% 的面试高频题，并建立扎实的算法思维。**关键不在刷题数量，而在吃透每一题的变体与核心思想**。坚持每天打卡，12 周后见分晓！