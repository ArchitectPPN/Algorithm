//
// Created by niujunqing on 2024/7/9.
//
#ifndef BOOSTRAP
#define BOOSTRAP
#include "eventloop.h"
#endif

#include <stdio.h>
#include <unistd.h>

int main() {
    aeEventLoop *eventLoop;
    printf("start create eventLoop \n");

    eventLoop = aeCreatEventLoop(10);

    printf("最大输出 %d \n", eventLoop->setSize);

    // 释放aeDeleteEventLoop
    aeDeleteEventLoop(eventLoop);

    int sockfd;
    // 创建socket
    sockfd = createSocket(eventLoop);

    printf("关闭fd %d \n", sockfd);
    close(sockfd);

    return 0;
}