//
// Created by niujunqing on 2024/7/9.
//
#include "eventloop.h"
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <errno.h>
#include <sys/epoll.h>

// 创建epoll实例
static int createEpoll(aeEventLoop *eventLoop) {
    printf("start create epoll fd \n");
    aeApiState *state = calloc(1, sizeof(aeApiState));
    if (!state) {
        printf("state assign mem failed, exit");
        return -1;
    }

    printf("assign state event mem \n");
    state->event = calloc(eventLoop->setSize, sizeof(struct epoll_event));
    if (!state->event) {
        free(state);
        printf("state event assign mem failed, exit;");
        return -1;
    }

    // 实际调用epoll_create
    printf("start use epoll_create \n");
    state->epfd = epoll_create(1024);
    if (state->epfd == -1) {
        free(state->event);
        free(state);
        return -1;
    }

    printf("epoll_create success %d \n", state->epfd);
    eventLoop->apiData = state;
    return 0;
}

// 创建一个事件循环实例
aeEventLoop * aeCreatEventLoop(int setSize) {
    // 声明一个eventLoop
    aeEventLoop *eventLoop;

    // 分配内存
    eventLoop = calloc(1, sizeof(*eventLoop));
    if (eventLoop == NULL) {
        printf("assign eventLoop failed! \n");

        goto err;
    }
    // 这里先默认分配10个元素
    eventLoop->events = calloc(setSize, sizeof(aeFileEvent));
    if(eventLoop->events == NULL){
        printf("assign events failed! \n");
        goto err;
    }
    eventLoop->fired = calloc(setSize, sizeof(aeFileEvent));
    if(eventLoop->fired == NULL) {
        printf("assign fired failed! \n");
        goto err;
    }
    eventLoop->maxfd = -1;
    eventLoop->setSize = setSize;

    // 创建epoll实例
    if (createEpoll(eventLoop) == -1) goto err;

    // 初始化每一个event为未触发的
    printf("start init event mask! \n");
    for(int i = 0; i < setSize; i++) {
        eventLoop->events[i].mask = AE_NONE;
    }
    return eventLoop;

err:
    printf("assign ");

    if (eventLoop) {
        // 释放内存
        free(eventLoop->events);
        free(eventLoop->fired);
        free(eventLoop);
    }
    return NULL;
}

// 释放epoll
static void freeEpoll(aeEventLoop *eventLoop) {
    aeApiState *state = eventLoop->apiData;
    printf("close fd %d \n", state->epfd);
    close(state->epfd);
    free(state->event);
    free(state);
}

// 释放eventLoop
void aeDeleteEventLoop(aeEventLoop *eventLoop) {
    freeEpoll(eventLoop);
    free(eventLoop->events);
    free(eventLoop->fired);
    free(eventLoop);
}

// 创建socket
static int createSocket(aeEventLoop *eventLoop) {
    return 0;
}

// socket设置选项
static void socketSetOption(aeEventLoop *eventLoop) {}


// 绑定socket地址
static void socketBindAddress(aeEventLoop *eventLoop) {}

// socket监听地址
static void socketListen(aeEventLoop *eventLoop) {}