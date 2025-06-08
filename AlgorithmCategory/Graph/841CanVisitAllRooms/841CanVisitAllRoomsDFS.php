<?php

# 841CanVisitAllRooms. 钥匙和房间 https://leetcode.cn/problems/keys-and-rooms/description/


$questions = [
    [[2, 3], [], [2], [1, 3]],
    [[1], [2], [3], []]
];

class CanVisitAllRoomsSolutionDFS
{
    /** @var array 已经访问过的房间 */
    private array $visited = [];
    /** @var int 已经访问了的房间数量 */
    private int $visitedNum = 0;

    /**
     * @param Integer[][] $rooms
     * @return Boolean
     */
    function canVisitAllRooms(array $rooms): bool
    {
        // 获取房间数量
        $n = count($rooms);

        $this->dfs($rooms, 0);

        return $this->visitedNum == $n;
    }

    /**
     * @param array $rooms
     * @param int $index
     * @return void
     */
    private function dfs(array $rooms, int $index): void
    {
        // 如果房间已被访问，直接返回
        if (isset($this->visited[$index])) {
            return;
        }

        // 设置房间已被访问
        $this->visited[$index] = true;
        $this->visitedNum += 1;

        foreach ($rooms[$index] as $room) {
            $this->dfs($rooms, $room);
        }
    }
}

$svc = new CanVisitAllRoomsSolutionDFS();
foreach ($questions as $question) {
    $ans = $svc->canVisitAllRooms($questions);
    var_dump($ans);
}
