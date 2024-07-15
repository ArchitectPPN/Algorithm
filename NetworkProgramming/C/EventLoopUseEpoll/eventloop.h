// 前向声明, 这意味着你告诉编译器有这样一个类型存在，但是你还没有给出它的完整定义。
// 声明一个eventLoop结构体
#ifndef ALGORITHM_EVENTLOOP_H
#define ALGORITHM_EVENTLOOP_H

#include <sys/socket.h>


#define AE_NONE 0
#define AE_READABLE 1
#define AE_WRITABLE 2

#define PORT 12345
#define IP "127.0.0.1"

#define ANET_ERR -1
#define ANET_OK 1

struct aeEventLoop;
struct aeFileEvent;

typedef struct aeFiredEvent {
    int fd;
    int mask;
} aeFiredEvent;

/* Types and data structures */
typedef void aeFileProc(struct aeEventLoop *eventLoop, int fd, void *clientData, int mask);

typedef struct aeFileEvent {
    int mask;
    aeFileProc *rFileProc;
    aeFileProc *wFileProc;
    void *clientData;
} aeFileEvent;

typedef struct aeEventLoop {
    int maxfd;
    aeFileEvent *events; /* Registered events */
    aeFiredEvent *fired; /* Fired events */
    int setSize;
    void *apiData;
} aeEventLoop;

typedef struct aeApiState {
    int epfd;
    struct epoll_event *event;
} aeApiState;

// 创建一个事件循环
aeEventLoop * aeCreatEventLoop();
int cusCreateEpoll(aeEventLoop *eventLoop);
void freeEpoll(aeEventLoop *eventLoop);
void aeDeleteEventLoop(aeEventLoop *eventLoop);
int createSocket(aeEventLoop *eventLoop);
int socketSetOption(int fd);
int socketListen(int s, struct sockaddr *sa, socklen_t len, int backlog);

void aeDeleteEventLoop(aeEventLoop *eventLoop);
#endif // ALGORITHM_EVENTLOOP_H