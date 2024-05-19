<?php

const ZSKIPLIST_P = 0.25;
const ZSKIPLIST_MAXLEVEL = 64;

function zslRandomLevel() {
    $level = 1;
    # 16383.75
    while (mt_rand(0, 0xFFFF) < (ZSKIPLIST_P * 0xFFFF)) {
        $level++;
    }
    return min($level, ZSKIPLIST_MAXLEVEL);
}

echo zslRandomLevel();
