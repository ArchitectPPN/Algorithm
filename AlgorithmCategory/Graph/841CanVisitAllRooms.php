<?php

# 841. 钥匙和房间 https://leetcode.cn/problems/keys-and-rooms/description/

/**
 * 把0号房间中能访问的所有房间都初始化到stack中，
 * 然后依次看stack中每把钥匙能访问的房间，使用entryRoom标记已经访问过的房间，避免重复访问
 * 最后统计entryRoom的元素数量是否等于 n - 1，注意：这里我没有把 0 放入entryRoom中，所以需要n-1
 */
class CanVisitAllRoomsSolution
{
    /**
     * @param Integer[][] $rooms
     * @return Boolean
     */
    function canVisitAllRooms(array $rooms): bool
    {
        $n = count($rooms);
        // 初始化一个已经进入的房间列表
        $entryRoom = [];
        $stack = new SplStack();
        // 将0号房间的钥匙都入栈
        foreach($rooms as $room) {
            foreach($room as $key) {
                $stack->push($key);
                $entryRoom[$key] = true;
            }
            break ;
        }

        while(!$stack->isEmpty()) {
            $key = $stack->pop();
            foreach($rooms[$key] as $tmpKey) {
                // 房间进入过，或者号为0，跳过
                if (isset($entryRoom[$tmpKey]) || $tmpKey == 0) {
                    continue;
                }
                $entryRoom[$tmpKey] = true;
                $stack->push($tmpKey);
            }
        }

        return count($entryRoom) == $n - 1;
    }
}

$questions = [[1],[2],[3],[]];
$questions = [[2,3],[],[2],[1,3]];
$svc = new CanVisitAllRoomsSolution();
$ans = $svc->canVisitAllRooms($questions);
var_dump($ans);