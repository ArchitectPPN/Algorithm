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
#include <arpa/inet.h>
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
    eventLoop->stop = 0;

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
    // hints 结构体用于给 getaddrinfo() 函数提供一些提示
    // servinfo 用于存储 getaddrinfo() 函数返回的地址信息
    // memset() 用于将 hints 结构体的所有成员初始化为零，以确保没有未初始化的字段影响 getaddrinfo() 的行为
    memset(&hints, 0, sizeof(hints));

    // 设置为ipv4地址
    hints.ai_family = AF_INET;
    // 套接字类型，表示我们将使用流式套接字，即 TCP 协议
    hints.ai_socktype = SOCK_STREAM;
    // 输入标志 表示我们打算使用返回的地址信息来绑定一个监听套接字，通常用于服务器端
    hints.ai_flags = AI_PASSIVE;

    // getaddrinfo 函数根据提供的主机名 "127.0.0.1"（本地回环地址）和端口号 _port，结合之前设置的 hints 参数，来获取一系列可能的地址信息。
    // 这些信息将存储在 servinfo 指向的链表中。
    // 如果 getaddrinfo() 调用成功，它将返回 0，否则返回一个错误码，此时代码会返回 ANET_ERR，通常表示网络错误或配置问题。
    if ((rv = getaddrinfo("127.0.0.1", _port, &hints, &servinfo)) != 0) {
        return ANET_ERR;
    }

    // 处理地址信息链表
    for (p = servinfo; p != NULL; p = p->ai_next) {
        // 创建socket
        if ((s = socket(p->ai_family, p->ai_socktype, p->ai_protocol)) == -1)
            continue;
        // 设置socket 选项
        if (socketSetOption(s) == ANET_ERR) goto err;
        // 绑定和监听
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

// 添加事件
int aeCreateFileEvent(aeEventLoop *eventLoop, int fd, int mask, aeFileProc *proc, void *clientData) {
    printf("Create file event, fd: %d \n", fd);

    if (fd > eventLoop->setSize) {
        printf("超过最大数量, 当前fd: %d, 最大数量: %d", fd, eventLoop->setSize);
        return ANET_ERR;
    }

    aeFileEvent *fe = &eventLoop->events[fd];
    if (aeApiAddEvent(eventLoop, fd, mask) == -1) {
        return ANET_ERR;
    }

    fe->mask |= mask;
    // 处理读事件
    if (mask & AE_READABLE) {
        printf("需要处理读事件 fd: %d\n", fd);
        fe->rFileProc = proc;
    }
    // 处理写事件
//    if (mask & AE_WRITABLE) fe->wFileProc = proc;
    fe->clientData = clientData;
    if (fd > eventLoop->maxfd) {
        eventLoop->maxfd = fd;
    }

    return ANET_OK;
}

// 给fd绑定事件
int aeApiAddEvent(aeEventLoop *eventLoop, int fd, int mask) {
    printf("设置epoll fd: %d\n", fd);

    aeApiState *state = eventLoop->apiData;

    // 初始化epoll_event
    struct epoll_event ee = {0};

    // 判断是添加还是修改
    int op = eventLoop->events[fd].mask == AE_NONE ? EPOLL_CTL_ADD : EPOLL_CTL_MOD;

    ee.events = 0;
    mask |= eventLoop->events[fd].mask; /* Merge old events */
    if (mask & AE_READABLE) {
        printf("ee.events 设置为 EPOLLIN epfd: %d fd: %d\n", state->epfd, fd);
        ee.events |= EPOLLIN;
    }
//    if (mask & AE_WRITABLE) ee.events |= EPOLLOUT;
    ee.data.fd = fd;
    if (epoll_ctl(state->epfd, op, fd, &ee) == -1) return -1;

    return 0;
}

// 建立tcp链接
// fe->rfileProc(eventLoop,fd,fe->clientData,mask);
// void acceptTcpHandler(aeEventLoop *el, int fd, void *privdata, int mask) {
void acceptTcpHandler(aeEventLoop *eventLoop, int sockFd, void *privdata, int mask) {
    // 每次最多处理100个客户端
    int max = 100;

    int cFd, cPort;
    char cIp[46];

    while (max--) {
        printf("开始处理新的客户端连接 \n");

        // 与客户端建立链接
        struct sockaddr_storage sa;
        socklen_t salen = sizeof(sa);

        // 与客户端建立链接
        cFd = accept(sockFd, (struct sockaddr *) &sa, &salen);
        if (cFd == -1) {
            printf("新的客户端连接失败 \n");
        }

        // 转换地址类型
        struct sockaddr_in *s = (struct sockaddr_in *) &sa;
        inet_ntop(AF_INET, (void *) &(s->sin_addr), cIp, sizeof(cIp));
        cPort = ntohs(s->sin_port);

        printf("新的客户端连接成功:%d ip: %s port: %d\n", cFd, cIp, cPort);
        close(cFd);
        printf("关闭客户端连接:%d ip: %s port: %d\n", cFd, cIp, cPort);
    }
}

// 处理对应的事件
int aeProcessEvents(aeEventLoop *eventLoop, int flags) {
    // 已处理的时间和已就绪的事件数量
    int numevents = 0, retval = 0;

    // maxfd != -1 说明有注册好的fd
    if (eventLoop->maxfd != -1) {
        // 拿出epoll实例
        aeApiState *state = eventLoop->apiData;

        // 调用epoll_wait获取已就绪的事件
        retval = epoll_wait(state->epfd, state->event, eventLoop->setSize, -1);
        // 说明有就绪的事件, 处理就绪的事件
        if (retval > 0) {
            int j;
            numevents = retval;

            // 将已触发的事件放入fired数组中
            for (j = 0; j < numevents; j++) {
                int mask = 0;
                // 访问数组下的第几个事件
                struct epoll_event *e = state->event + j;

                if (e->events & EPOLLIN) mask |= AE_READABLE;
                // 将已触发的事件放到对应的事件数组中
                eventLoop->fired[j].fd = e->data.fd;
                eventLoop->fired[j].mask = mask;
            }

            // 就绪的事件调用回调函数
            for (j = 0; j < numevents; j++) {
                // 拿出绑定好的已就绪事件
                aeFileEvent *fe = &eventLoop->events[eventLoop->fired[j].fd];
                int mask = eventLoop->fired[j].mask;
                int fd = eventLoop->fired[j].fd;

                // 触发读事件
                fe->rFileProc(eventLoop, fd, fe->clientData, mask);
            }
        }
    }

    return numevents;
}

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