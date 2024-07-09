//
// Created by niujunqing on 2024/7/9.
//
#include "eventloop.h"
#include <stdio.h>

int main() {
    aeEventLoop *eventLoop;
    printf("start create eventLoop \n");

    eventLoop = aeCreatEventLoop(10);

    printf("最大输出 %d \n", eventLoop->setSize);

    // 释放aeDeleteEventLoop
    aeDeleteEventLoop(eventLoop);
    return 0;
}