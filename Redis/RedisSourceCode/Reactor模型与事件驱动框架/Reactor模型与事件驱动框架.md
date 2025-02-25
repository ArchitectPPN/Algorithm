### Reactor模型

Reactor模型就是网络服务器端用来处理高并发网络IO请求的一种编程模型。关键点：
- 三类关键事件：连接事件/写事件/读事件。
- 三个关键角色：reactor/acceptor/handler。

Reactor模型处理的是客户端和服务器端的交互过程，而这三类事件正好对应了客户端和服务器端交互过程中，不同类请求在服务器端引发的待处理事件。

- 当一个客户端要和服务器端进行交互时，客户端就会向服务器端发送连接请求，以建立连接，这就对应了服务器端的一个**连接事件**。
- 一旦建立连接以后，客户端就会给服务器端发送读请求，以便读取数据。服务器端在处理读请求时，需要向客户端写回数据，这就对应了服务器端的**写事件**。
- 无论客户端给服务器端发送读或写请求，服务器端都需要从客户端读去请求内容，所以在这里，读或写请求的读取就对应了服务器端的**读事件**。

![img_1.png](img_1.png)

三个关键角色的作用：
- 首先，连接事件由 acceptor 来处理，负责接收连接；acceptor 在接收连接后，会创建 handler，用于网络连接上对后续读写事件的处理；
- 其次，读写事件由 handler 处理；
- 最后，在高并发场景中，连接事件、读写事件会同时发生，所以，我们需要有一个角色专门监听和分配事件，这就是 reactor 角色。当有连接请求时，reactor 将产生的连接事件交由 acceptor 处理；当有读写请求时，reactor 将读写事件交由 handler 处理。

![img.png](reactor模型角色关系.png)

### 事件驱动框架

所谓的事件驱动框架，就是在实现Reactor模型时，需要实现的代码整体控制逻辑。简单来说，事件驱动框架包括了两部分：
- 事件初始化
- 事件捕获/分发/处理主循环

事件初始化是在服务器启动时就执行的，主要是创建需要监听的事件类型，以及该类事件对应的handler。服务器完成初始化后，事件初始化也就完成了，服务器程序就需要进入到事件的捕获/分发/处理主循环中。

在实际的开发过程中，通常会使用while循环来作为这个主循环，在这个循环中，要捕获发生的事件，然后根据事件类型来调用在初始化时创建好的**事件hanler**完成处理。

流程图如下： 
![img.png](事件循环处理流程.png)

### Redis 对 Reactor 模型的实现

Redis 的网络框架实现了 Reactor 模型，并且自行开发实现了一个事件驱动框架。这个框架对应的 Redis 代码实现文件是ae.c，对应的头文件是ae.h。

事件驱动框架的实现离不开事件的定义，以及事件注册、捕获、分发和处理等一系列操作。当然，对于整个框架来说，还需要能一直运行，持续地响应发生的事件。

从 ae.h 头文件中就可以看到，Redis 为了实现事件驱动框架，相应地定义了事件的数据结构、框架主循环函数、事件捕获分发函数、事件和 handler 注册函数。

事件的数据结构是关联事件类型和事件处理函数的关键要素，而 Redis 的事件驱动框架定义了两类事件：IO 事件和时间事件，分别对应了客户端发送的网络请求和 Redis 自身的周期性操作。

事件的数据结构定义：以 aeFileEvent 为例
```
// 
typedef struct aeFileEvent {
    int mask; /* one of AE_(READABLE|WRITABLE|BARRIER) */
    aeFileProc *rfileProc;
    aeFileProc *wfileProc;
    void *clientData;
} aeFileEvent;
```

- mask 是用来表示事件类型的掩码。对于网络通信的事件来说，主要有 AE_READABLE、AE_WRITABLE 和 AE_BARRIER 三种类型事件。框架在分发事件时，依赖的就是结构体中的事件类型；
- rfileProc 和 wfileProce 分别是指向 AE_READABLE 和 AE_WRITABLE 这两类事件的处理函数，也就是 Reactor 模型中的 handler。框架在分发事件后，就需要调用结构体中定义的函数进行事件处理；
- 最后一个成员变量 clientData 是用来指向客户端私有数据的指针；

Redis 在 ae.h 文件中，定义了支撑框架运行的主要函数，包括框架主循环的 aeMain 函数、负责事件捕获与分发的 aeProcessEvents 函数，以及负责事件和 handler 注册的 aeCreateFileEvent 函数，它们的原型定义如下：
```
void aeMain(aeEventLoop *eventLoop);
int aeCreateFileEvent(aeEventLoop *eventLoop, int fd, int mask, aeFileProc *proc, void *clientData);
int aeProcessEvents(aeEventLoop *eventLoop, int flags);
```

#### 主循环：aeMain 函数
aeMain 函数的逻辑很简单，就是用一个循环不停地判断事件循环的停止标记。如果事件循环的停止标记被设置为 true，那么针对事件捕获、分发和处理的整个主循环就停止了；否则，主循环会一直执行。aeMain 函数的主体代码如下所示：
```
void aeMain(aeEventLoop *eventLoop) {
    eventLoop->stop = 0;
    while (!eventLoop->stop) {
        …
        aeProcessEvents(eventLoop, AE_ALL_EVENTS|AE_CALL_AFTER_SLEEP);
    }
}
```

aeMain 函数是在哪里被调用?

按照事件驱动框架的编程规范来说，框架主循环是在服务器程序初始化完成后，就会开始执行。

Redis 在完成 Redis server 的初始化后，会调用 aeMain 函数开始执行事件驱动框架。

#### 事件捕获与分发：aeProcessEvents 函数
aeMain 函数包含了事件框架的主循环，在主循环中通过 aeProcessEvents 来完成事件的捕获/分发/处理。

aeProcessEvents 函数实现的主要功能，包括捕获事件、判断事件类型和调用具体的事件处理函数，从而实现事件的处理。

aeProcessEvents 函数的主体结构中有三个if条件分支：
```
int aeProcessEvents(aeEventLoop *eventLoop, int flags)
{
    int processed = 0, numevents;
 
    /* 若没有事件处理，则立刻返回*/
    if (!(flags & AE_TIME_EVENTS) && !(flags & AE_FILE_EVENTS)) return 0;
    /*如果有IO事件发生，或者紧急的时间事件发生，则开始处理*/
    if (eventLoop->maxfd != -1 || ((flags & AE_TIME_EVENTS) && !(flags & AE_DONT_WAIT))) {
       …
    }
    /* 检查是否有时间事件，若有，则调用processTimeEvents函数处理 */
    if (flags & AE_TIME_EVENTS)
        processed += processTimeEvents(eventLoop);
    /* 返回已经处理的文件或时间*/
    return processed; 
}
```
这三个分支分别对应了以下三种情况:
- 情况一：既没有时间事件，也没有网络事件；
- 情况二：有 IO 事件或者有需要紧急处理的时间事件；
- 情况三：只有普通的时间事件。

那么对于第一种情况来说，因为没有任何事件需要处理，aeProcessEvents 函数就会直接返回到 aeMain 的主循环，开始下一轮的循环；而对于第三种情况来说，该情况发生时只有普通时间事件发生，所以 aeMain 函数会调用专门处理时间事件的函数 processTimeEvents，对时间事件进行处理。

第二种情况，Redis 需要捕获发生的网络事件，并进行相应的处理。Redis会调用aeApiPoll 来捕获事件。
```
int aeProcessEvents(aeEventLoop *eventLoop, int flags){
   ...
   if (eventLoop->maxfd != -1 || ((flags & AE_TIME_EVENTS) && !(flags & AE_DONT_WAIT))) {
       ...
       //调用aeApiPoll函数捕获事件
       numevents = aeApiPoll(eventLoop, tvp);
       ...
    }
    ...
```

aeApiPoll 是如何捕获事件?

Redis 是依赖于操作系统底层提供的 IO 多路复用机制，来实现事件捕获，检查是否有新的连接、读写事件发生。对于不同的系统， redis做了不同的封装。

这里用Linux来做示例：Linux 上提供了 epoll_wait API，用于检测内核中发生的网络 IO 事件。在ae_epoll.c文件中，aeApiPoll 函数就是封装了对 epoll_wait 的调用。
在 aeApiPoll 函数中直接调用了 epoll_wait 函数，并将 epoll 返回的事件信息保存起来的逻辑：
```
static int aeApiPoll(aeEventLoop *eventLoop, struct timeval *tvp) {
    …
    //调用epoll_wait获取监听到的事件
    retval = epoll_wait(state->epfd,state->events,eventLoop->setsize,
            tvp ? (tvp->tv_sec*1000 + tvp->tv_usec/1000) : -1);
    if (retval > 0) {
        int j;
        //获得监听到的事件数量
        numevents = retval;
        //针对每一个事件，进行处理
        for (j = 0; j < numevents; j++) {
             #保存事件信息
        }
    }
    return numevents;
}
```
现在我们就已经在 aeMain 函数中，看到了 aeProcessEvents 函数被调用，并用于捕获和分发事件的基本处理逻辑.
![img.png](img.png)

#### 事件注册：aeCreateFileEvent 函数
在初始化过程中，aeCreateFileEvent 会被调用，用于注册要监听的事件，以及相应的事件处理函数。

在 initServer 函数的执行过程中，initServer 函数会根据启用的 IP 端口个数，为每个 IP 端口上的网络事件，调用 aeCreateFileEvent，创建对 AE_READABLE 事件的监听，并且注册 AE_READABLE 事件的处理 handler，也就是 acceptTcpHandler 函数。这一过程如下图所示：
![img_2.png](img_2.png)

AE_READABLE 事件就是客户端的网络连接事件，而对应的处理函数就是接收 TCP 连接请求。

aeCreateFileEvent 如何实现事件和处理函数的注册呢？

Linux 提供了 epoll_ctl API，用于增加新的观察事件。而 Redis 在此基础上，封装了 aeApiAddEvent 函数，对 epoll_ctl 进行调用。aeCreateFileEvent 就会调用 aeApiAddEvent，然后 aeApiAddEvent 再通过调用 epoll_ctl，来注册希望监听的事件和相应的处理函数。等到 aeProceeEvents 函数捕获到实际事件时，它就会调用注册的函数对事件进行处理了。

![img_3.png](img_3.png)

问题： 
- epoll_wait 为什么返回的是已就绪事件的数量？