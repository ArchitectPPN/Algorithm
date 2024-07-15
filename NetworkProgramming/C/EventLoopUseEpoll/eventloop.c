//
// Created by niujunqing on 2024/7/9.
//
// eventloop.h
#ifndef EVENTLOOP_H
#define EVENTLOOP_H

#include <stdio.h>
#include <unistd.h>
#include "eventloop.h"
#include <sys/socket.h>


#endif

#include <stdlib.h>
#include <errno.h>
#include <sys/epoll.h>
#include <string.h>
#include <netinet/in.h>
#include <netdb.h>


// 创建epoll实例
int cusCreateEpoll(aeEventLoop *eventLoop) {
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
aeEventLoop *aeCreatEventLoop(int setSize) {
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
    if (eventLoop->events == NULL) {
        printf("assign events failed! \n");
        goto err;
    }
    eventLoop->fired = calloc(setSize, sizeof(aeFileEvent));
    if (eventLoop->fired == NULL) {
        printf("assign fired failed! \n");
        goto err;
    }
    eventLoop->maxfd = -1;
    eventLoop->setSize = setSize;

    // 创建epoll实例
    if (cusCreateEpoll(eventLoop) == -1) goto err;

    // 初始化每一个event为未触发的
    printf("start init event mask! \n");
    for (int i = 0; i < setSize; i++) {
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
void freeEpoll(aeEventLoop *eventLoop) {
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
int createSocket(aeEventLoop *eventLoop) {
    int s = -1, rv;

    char _port[6];
    snprintf(_port, 6, "%d", PORT);

    // 设置服务器地址
    struct addrinfo hints, *servinfo, *p;
    memset(&hints, 0, sizeof(hints));
    // 设置为ipv4地址
    hints.ai_family = AF_INET;
    //套接字类型
    hints.ai_socktype = SOCK_STREAM;
    //输入标志
    hints.ai_flags = AI_PASSIVE;

    if ((rv = getaddrinfo("127.0.0.1", _port, &hints, &servinfo)) != 0) {
        return ANET_ERR;
    }

    for (p = servinfo; p != NULL; p = p->ai_next) {
        // 创建socket
        if ((s = socket(p->ai_family, p->ai_socktype, p->ai_protocol)) == -1)
            continue;
        // 设置socket 选项
        if (socketSetOption(s) == ANET_ERR) goto err;
        // 监听
        if (socketListen(s, p->ai_addr, p->ai_addrlen, eventLoop->setSize) == ANET_ERR) s = ANET_ERR;
        goto end;
    }

    if (p == NULL) {
        printf("unable to bind socket \n");
        goto err;
    }

err:
    if (s != -1) close(s);
    s = ANET_ERR;
end:
    freeaddrinfo(servinfo);

    return s;
}

// socket设置选项
int socketSetOption(int fd) {
    int yes = 1;
    if (setsockopt(fd, SOL_SOCKET, SO_REUSEADDR, &yes, sizeof(yes)) == -1) {
        return ANET_ERR;
    }

    return ANET_OK;
}


// 绑定socket地址
// void socketBindAddress(aeEventLoop *eventLoop) {}

// socket监听地址
int socketListen(int s, struct sockaddr *sa, socklen_t len, int backlog) {
    // bind地址
    if (bind(s, sa, len) == -1) {
        close(s);
        return ANET_ERR;
    }

    // 监听地址
    if (listen(s, backlog) == -1) {
        close(s);
        return ANET_ERR;
    }

    return ANET_OK;
}