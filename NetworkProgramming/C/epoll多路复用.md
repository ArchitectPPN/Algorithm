## epoll_create1

### 低层实现逻辑
epoll_create1 是 Linux 下的一个函数，用于创建一个 epoll 实例。它是 epoll 接口的一部分，用于高效地处理大量文件描述符的 I/O 事件。

epoll_create1 的底层实现逻辑主要涉及以下几个方面：

- 创建 epoll 实例：调用 epoll_create1 函数会在内核中创建一个 epoll 实例。这个实例用于存储要监听的文件描述符以及对应的事件。
- 分配内存：epoll 实例需要分配一定的内存来存储文件描述符和事件。内核会为每个 epoll 实例分配一块内存空间。
- 注册文件描述符：使用 epoll_ctl 函数可以将需要监听的文件描述符注册到 epoll 实例中，并指定感兴趣的事件。
- 等待事件：调用 epoll_wait 函数会阻塞当前线程，直到有事件发生。
- 事件处理：当有事件发生时，epoll_wait 函数会返回发生事件的文件描述符数量，并将对应的事件存储在用户提供的数组中。通过遍历这个数组，可以获取发生事件的文件描述符和对应的事件类型。
- 高效的实现：epoll 采用了一种基于回调的机制，不需要轮询所有的文件描述符来检测事件。它维护了一个红黑树来存储注册的文件描述符，并利用 mmap 来加速事件的通知和处理。
- 支持水平触发和边缘触发：epoll 可以配置为水平触发或边缘触发模式。水平触发模式下，只要文件描述符上有事件未处理，就会一直触发通知；而边缘触发模式下，只有在事件发生的边缘才会触发通知。
- 线程安全性：epoll 在多线程环境下是安全的，可以在多个线程中同时使用。
epoll_create1 的底层实现利用了 Linux 内核的 epoll 机制，提供了高效的 I/O 事件通知和处理能力。它是在 Linux 上进行高性能网络编程和并发编程的重要工具。

### epoll_create与epoll_create1函数的区别
epoll_create和epoll_create1的区别主要体现在它们的参数和功能上。

首先，epoll_create函数在早期版本的内核中用于创建一个新的epoll实例，它接受一个参数size，这个参数用来告诉内核这个epoll实例可以监听的最多文件描述符数目。然而，在较新的内核版本中，这个参数已经被弃用，因为内核可以根据需要动态地分配描述事件所需的内存空间。

相比之下，epoll_create1函数是在较新的内核版本中引入的，它的设计更加灵活和强大。epoll_create1的参数包括一个标志位flags，这个标志位可以用来设置一些额外的选项，比如EPOLL_CLOEXEC，这个选项可以在文件描述符上设置执行时关闭标志。如果flags参数为0，那么epoll_create1的行为就和没有flags参数的epoll_create一样。

另外，这两个函数在返回值方面也有所不同。epoll_create返回一个指向新创建的epoll实例的文件描述符，而epoll_create1在成功时返回一个非负的文件描述符，如果出现错误则返回-1，并设置相应的错误码。

总的来说，随着内核版本的更新和演进，epoll_create1提供了更多的选项和功能，使得用户可以更加灵活地使用epoll机制进行并发处理。

## epoll_ctl

epoll_ctl函数是Linux epoll接口中的一个重要部分，用于控制已经创建的epoll文件描述符上的事件监控。它的功能包括添加、修改或删除对特定文件描述符的监控。该函数的基本原型如下：
```
int epoll_ctl(int epfd, int op, int fd, struct epoll_event *event);
```
#### 参数说明：
- epfd：为通过epoll_create1()调用创建的epoll文件描述符。
- op：指定要执行的操作类型，可以是以下几种：
  - EPOLL_CTL_ADD：向epoll集合中添加需要监控的文件描述符。
  - EPOLL_CTL_MOD：修改epoll集合中已注册的文件描述符的监控事件。
  - EPOLL_CTL_DEL：从epoll集合中删除一个文件描述符，不再监控它。
- fd：是要操作的文件描述符，比如一个socket的描述符。
- event：是一个指向epoll_event结构体的指针，该结构体定义了要监控的事件类型及与之关联的用户数据。
- epoll_event结构体至少包含两个字段：
  - events：指定关注的事件掩码，如EPOLLIN（可读事件）、EPOLLOUT（可写事件）等。
  - data：联合体，可以存储文件描述符或者其他自定义的数据，供回调函数使用。
    
epoll_ctl函数的返回值表示操作是否成功，成功时返回0，失败则返回-1并设置相应的错误码。
通过epoll_ctl，开发者可以灵活地管理关注的事件集合，实现高效的I/O事件处理，特别是在处理高并发连接时表现出色。

## epoll_wait
epoll_wait函数是Linux epoll接口中的关键部分，用于等待并获取那些在先前通过epoll_ctl添加到epoll集合中的文件描述符上发生的就绪事件（如可读、可写等）。该函数会阻塞调用者直到至少一个事件发生，或者超时，或者被信号中断。其基本原型如下：

```
int epoll_wait(int epfd, struct epoll_event *events, int maxevents, int timeout);
```
#### 参数说明：
- epfd：由epoll_create1()创建的epoll文件描述符。
- events：是一个指向epoll_event结构体数组的指针，用于接收就绪事件的集合。每个元素对应一个就绪的文件描述符及其发生的事件。
- maxevents：指定了events数组的最大事件数量，即最多可以返回多少个就绪事件。
- timeout：指定等待时间，单位为毫秒：
  - 如果timeout为-1，则epoll_wait会无限期等待，直到至少有一个文件描述符就绪。
  - 如果timeout为0，则epoll_wait立即返回，无论是否有事件发生。
  - 如果timeout大于0，则epoll_wait会在指定的毫秒数后返回，即使没有事件发生。
  
函数返回值表示实际就绪的事件数量，如果返回0表示超时，小于0则表示出现错误（通常会设置errno）。

epoll_wait通过其高效的事件通知机制，使得在处理大量并发连接时能显著减少系统调用和上下文切换的次数，从而提高应用程序的性能和可扩展性。

## epoll_event

epoll_event结构体是Linux epoll机制中的核心数据结构，用于存储与epoll监控相关的事件信息。一个epoll_event可以关联一个文件描述符及其发生的事件类型，还可以携带额外的用户数据。其定义通常如下（位于<sys/epoll.h>头文件中）：
```
struct epoll_event {
    __uint32_t events;      /* Epoll events */
    epoll_data_t data;      /* User data variable */
};
```

#### 结构体成员说明：
- events：这是一个无符号32位整数，表示关注的事件集合。它可以是按位或（|）组合的多个事件标记，常见的事件标记包括但不限于：
  - EPOLLIN：表示对应的文件描述符可读。
  - EPOLLOUT：表示对应的文件描述符可写。
  - EPOLLERR：发生错误。
  - EPOLLHUP：挂断（对端关闭连接）。
  - EPOLLET（边缘触发模式）：与水平触发模式相对，仅当状态变化时才触发事件。
  - 其他高级事件标记如EPOLLONESHOT、EPOLLRDHUP等。
  
- data：这是一个联合体（epoll_data_t），可以用来存放任意类型的用户数据。根据实际需求，它可以是以下几种类型之一：
  - ptr：一个通用指针，最常用，可以指向用户定义的数据结构。
  - fd：一个整型，可以用来直接存储另一个文件描述符。
  - u32、u64：无符号32位或64位整数，用于存储整型数据。

通过epoll_event结构体，epoll不仅能够高效地管理文件描述符的事件监听，还允许开发者附加额外信息，便于在事件触发时进行进一步的处理逻辑。

### 文章连接
- [epoll_create函数简介](https://www.cnblogs.com/yubo-guan/p/17997722)
- [epoll_ctl函数](https://www.cnblogs.com/yubo-guan/p/17997734)