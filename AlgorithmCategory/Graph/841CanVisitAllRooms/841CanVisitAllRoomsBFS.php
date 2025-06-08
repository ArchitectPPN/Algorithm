<?php

# 841CanVisitAllRooms. 钥匙和房间 https://leetcode.cn/problems/keys-and-rooms/description/


$questions = [
    [[2, 3], [], [2], [1, 3]],
    [[1], [2], [3], []]
];

/**
 * 把0号房间中能访问的所有房间都初始化到stack中，
 * 然后依次看stack中每把钥匙能访问的房间，使用entryRoom标记已经访问过的房间，避免重复访问
 * 最后统计entryRoom的元素数量是否等于 n - 1，注意：这里我没有把 0 放入entryRoom中，所以需要n-1
 */
class CanVisitAllRoomsSolutionBFSOptimization
{
    /**
     * @param Integer[][] $rooms
     * @return Boolean
     */
    function canVisitAllRooms(array $rooms): bool
    {
        $n = count($rooms);
        // 初始化访问标记数组，0号房间默认已访问
        $visited = array_fill(0, $n, false);
        $visited[0] = true;

        // 使用栈进行深度优先搜索
        $stack = new SplStack();
        $stack->push(0); // 从0号房间开始

        // 记录已访问的房间数量
        $visitedCount = 1;

        while (!$stack->isEmpty()) {
            $currentRoom = $stack->pop();

            // 遍历当前房间中的所有钥匙
            foreach ($rooms[$currentRoom] as $key) {
                if (!$visited[$key]) {
                    $visited[$key] = true;
                    $visitedCount++;
                    $stack->push($key);
                }
            }
        }

        // 检查是否访问了所有房间
        return $visitedCount === $n;
    }
}
$svc = new CanVisitAllRoomsSolutionBFSOptimization();
foreach ($questions as $question) {
    $ans = $svc->canVisitAllRooms($questions);
    var_dump($ans);
}
