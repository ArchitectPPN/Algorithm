### socket 
socket() 函数是在计算机网络编程中用于创建套接字（socket）的一个系统调用。套接字是应用程序与网络协议栈之间的接口，用于在网络中发送和接收数据。socket() 函数允许程序员指定通信的类型和协议族，从而创建一个可用于网络通信的端点。

在C/C++中，socket() 函数的基本定义如下：

```c
#include <sys/socket.h>

int socket(int domain, int type, int protocol);
```
函数参数解释如下: 
- domain: 指定套接字使用的协议族（或称域）。常见的值有：
  - AF_INET (或 PF_INET)：IPv4 地址族，用于TCP/IP v4协议。
  - AF_INET6 (或 PF_INET6)：IPv6 地址族，用于TCP/IP v6协议。
  - AF_UNIX (或 PF_UNIX)：本地进程间通信（IPC），用于同一台机器上的进程间通信。
  - 其他可能的值还包括 AF_APPLETALK, AF_NETLINK, AF_ROUTE 等，具体取决于操作系统支持。
- type: 指定套接字的类型，即通信模式。常见的类型有：
  - SOCK_STREAM：提供面向连接的、可靠的字节流服务，通常用于TCP协议。
  - SOCK_DGRAM：提供无连接的数据报服务，通常用于UDP协议。
  - SOCK_RAW：允许直接访问底层协议，用于创建特殊类型的套接字，如ICMP或IGMP。
- protocol: 指定具体使用的协议。通常可以设置为0，表示使用与domain和type相匹配的默认协议。
  - 对于TCP，可以显式地指定为IPPROTO_TCP；
  - 对于UDP，可以指定为IPPROTO_UDP。
- 函数返回值: 
  - 成功时，socket() 函数返回一个非负整数，这是新创建的套接字的文件描述符，后续的网络操作将使用这个描述符
  - 如果调用失败，socket() 返回-1，并且errno会设置为相应的错误码，指示失败的原因。 
  
创建的套接字可以随后通过其他函数如bind()、listen()、accept()、connect()、send()和recv()等进行配置和使用，以实现网络通信。

#### addrinfo 结构体
```c
struct addrinfo {
    int          ai_flags;      /* Input flags. */
    int          ai_family;     /* Address family of socket. */
    int          ai_socktype;   /* Socket type. */
    int          ai_protocol;   /* Protocol or protocol family. */
    socklen_t    ai_addrlen;    /* Length of socket address. */
    struct sockaddr *ai_addr;   /* Socket address for socket. */
    char         *ai_canonname; /* Canonical name for service location. */
    struct addrinfo *ai_next;   /* Pointer to next in linked list. */
};
```
- ai_flags: 输入标志，用于定制行为
  - AI_PASSIVE: 当创建服务器套接字时使用，意味着 getaddrinfo() 函数将返回一个可以用于绑定到本地地址的套接字地址。通常，这意味着你可以传入空字符串或 NULL 作为主机名参数，getaddrinfo() 将返回一个适合用于 bind() 的地址
  - AI_CANONNAME：请求返回规范主机名。如果设置了此标志，getaddrinfo() 将尝试返回一个指向规范主机名的指针，存储在 ai_canonname 字段中
  - AI_NUMERICHOST: 强制 getaddrinfo() 函数将主机名解析为数字形式，而不是尝试查找 DNS 名称。这通常用于需要直接使用 IP 地址的情况
  - 这些标志可以使用按位或运算符 (|) 组合在一起，以定制 getaddrinfo() 的行为。
- ai_family: 地址家族
  - AF_INET
  - AF_INET6
- ai_socktype: 套接字类型
  - SOCK_STREAM 
  - SOCK_DGRAM
- ai_protocol: 协议或协议家族
  - IPPROTO_TCP
  - IPPROTO_UDP
- ai_addrlen: 地址长度
- ai_addr: 指向 struct sockaddr 的指针，保存了实际的网络地址
- ai_canonname: 服务位置的规范名称，当设置了 AI_CANONNAME 标志时有效
- ai_next: 链表中的下一个 addrinfo 结构体的指针，允许返回多个地址信息

`getaddrinfo()` 函数接受主机名和端口服务名作为输入，返回一个 addrinfo 结构体链表，其中包含了所有可能的地址信息组合。这样做的好处是可以处理多个可能的地址和协议，例如，如果主机名解析到多个 IP 地址，或者服务名可以对应多种协议，getaddrinfo() 将返回所有可能的选项，程序员可以根据需要选择其中一个。

在实际编程中，addrinfo 结构体和 getaddrinfo() 函数的使用使得网络编程更加灵活和健壮，能够适应不同的网络环境和需求。

#### sockaddr 
sockaddr 是一个在 Unix 和类 Unix 操作系统中用于网络编程的重要结构体，它定义在 <sys/socket.h> 头文件中。sockaddr 结构体用于存储网络套接字（socket）的地址信息，包括网络地址和端口号。由于不同地址族（如 IPv4、IPv6 或本地 UNIX 域套接字）的地址信息格式不同，sockaddr 是一个通用的结构体，它的具体类型和内容依赖于具体的地址族。
sockaddr 的基本定义如下：
```c
struct sockaddr {
    sa_family_t sin_family; /* 地址族，如 AF_INET 或 AF_INET6 */
    char        sa_data[14]; /* 14 字节的协议地址 */
};
```
然而，sa_data 实际上是一个未指定用途的数组，用于存储地址族特定的信息。在实际使用中，程序员通常会使用 sockaddr 的派生结构体，如 sockaddr_in（用于 IPv4）或 sockaddr_in6（用于 IPv6），这些派生结构体提供了更具体和更易于使用的字段。

##### sockaddr_in (IPv4)
sockaddr_in 结构体用于 IPv4 地址，定义如下：
```c
struct sockaddr_in {
    sa_family_t sin_family; /* 地址族，AF_INET */
    in_port_t   sin_port;   /* 端口号 */
    struct in_addr sin_addr; /* IPv4 地址 */
};
```
其中 in_addr 是另一个结构体，用于存储 32 位的 IPv4 地址：
```c
struct in_addr {
    uint32_t s_addr; /* 32 位的 IPv4 地址 */
};
```

##### sockaddr_in6 (IPv6)
sockaddr_in6 结构体用于 IPv6 地址，定义如下：
```c
struct sockaddr_in6 {
    sa_family_t sin6_family; /* 地址族，AF_INET6 */
    in_port_t   sin6_port;   /* 端口号 */
    uint32_t    sin6_flowinfo; /* 流信息 */
    struct in6_addr sin6_addr; /* IPv6 地址 */
    uint32_t    sin6_scope_id; /* 范围 ID */
};
```
其中 in6_addr 是一个结构体，用于存储 128 位的 IPv6 地址：
```c
struct in6_addr {
    uint8_t s6_addr[16]; /* 128 位的 IPv6 地址 */
};
```
在编写网络程序时，sockaddr 和其派生结构体是与套接字进行交互的关键，用于诸如 bind()、connect()、sendto() 和 recvfrom() 等函数中，以指定或接收网络地址信息。

#### inet_ntop()
```c
#include <arpa/inet.h>

const char *inet_ntop(int af, const void *src, char *dst, socklen_t size);
```
参数说明：
- af：地址族，可以是 AF_INET（IPv4）或 AF_INET6（IPv6）。
- src：指向要转换的原始二进制地址的指针。对于IPv4，这通常是 struct in_addr 的 sin_addr 成员；对于IPv6，则是 struct in6_addr 的 sin6_addr 成员。
- dst：指向用于存储转换后文本字符串的缓冲区的指针。
- size：dst 缓冲区的大小，以字节为单位。对于IPv4地址，推荐使用 INET_ADDRSTRLEN 宏指定的长度；对于IPv6地址，则使用 INET6_ADDRSTRLEN 宏。

返回值：
- 成功时，返回指向转换后的地址字符串的指针（即 dst）。
- 如果发生错误（如缓冲区太小或无效的地址族），则返回 NULL。可以通过 errno 获取具体的错误代码

这个函数用于将网络字节序的二进制 IP 地址转换为人类可读的点分十进制（IPv4）或冒号十六进制（IPv6）格式的字符串。
如果你要在 C 或 C++ 程序中使用 inet_ntop()，需要包含 <arpa/inet.h> 头文件。在某些系统上，你可能还需要包含 <netinet/in.h> 或 <sys/socket.h>，因为它们可能间接地包含了 <arpa/inet.h> 或者定义了 inet_ntop() 所需的一些类型和常量。

#### setsockopt
```c
#include <sys/socket.h>
int setsockopt(int sockfd, int level, int optname, const void *optval, socklen_t optlen);
```
- sockfd：这是套接字描述符，即通过socket()函数创建的文件描述符。
- level：选项所属的协议层。
  - SOL_SOCKET（通用套接字选项）这是套接字层的选项，与具体的传输协议无关。它包括了如 SO_REUSEADDR, SO_BROADCAST, SO_KEEPALIVE, SO_LINGER, SO_RCVBUF, SO_SNDBUF, SO_OOBINLINE 等选项。
  - IPPROTO_TCP/IPPROTO_UDP/IPPROTO_IP: 分别对应TCP、UDP和IP协议的选项
- optname：整型，表示要设置的具体选项。选项很多，具体取决于level的值。
  - SO_REUSEADDR: 它允许一个套接字绑定到一个已经在使用中的地址和端口上。在默认情况下，如果一个端口正在被一个进程使用，其他尝试绑定到同一端口的进程会被操作系统拒绝。但是，当设置了 SO_REUSEADDR 选项后，新的套接字可以绑定到这个端口，只要它不在 LAST_ACK 或 ESTABLISHED 状态即可。
    - 快速重启服务器：如果服务器意外崩溃或正常关闭，可能有套接字处于 TIME_WAIT 状态，这通常会持续几分钟。如果没有设置 SO_REUSEADDR，在这段时间内，新的服务器实例将无法绑定到原来的端口，因为系统认为端口仍然被占用。设置 SO_REUSEADDR 可以让新实例立即绑定并开始监听。
    - 多线程或多进程服务器：在一个多线程或多进程环境中，多个实例可能需要绑定到同一个端口。设置 SO_REUSEADDR 允许每个实例绑定到相同的端口，只要它们使用不同的 IP 地址（例如，在多网卡系统中）或者它们不同时处于连接状态。
  - SO_KEEPALIVE: 是一个用于套接字的选项，主要功能是在长时间没有数据传输的情况下自动发送探测包，以检查连接是否仍然有效。这个特性对于TCP长连接尤其重要，可以确保即使在网络不稳定或中间设备出现问题时，也能及时发现并处理断开的连接。然而，仅仅启用 SO_KEEPALIVE 并不能完全控制探测包的行为。在某些操作系统中，如 Linux，你还可以进一步配置以下参数：
    - TCP_KEEPIDLE: 定义在第一次发送探测包前的空闲时间（单位通常是秒）。即，如果在这段时间内没有数据传输，将开始发送探测包。
    - TCP_KEEPINTVL: 定义两次连续探测包之间的间隔时间（单位通常是秒）。
    - TCP_KEEPCNT: 定义在放弃连接之前发送的探测包的最大次数。
  - TCP_NODELAY: TCP_NODELAY 是一个与 TCP 套接字相关的选项，用于控制 Nagle 算法的行为。Nagle 算法是为了减少小数据包在网络上传输的次数而设计的，它会等待一段时间（通常几百毫秒）来收集更多的数据，然后一次性发送较大的数据包，从而提高网络带宽的利用率。
    然而，在某些应用中，比如实时通信或游戏服务器，延迟比带宽利用率更为关键。在这种情况下，使用 TCP_NODELAY 可以禁用 Nagle 算法，确保数据能够尽快发送出去，即使这意味着会有更多的小数据包在网络上传输。设置 TCP_NODELAY 后，每次调用 send 或 write 函数时，数据将立即发送到网络上，而不进行缓冲。这可以显著降低数据传输的延迟，但可能会增加网络的负载，特别是在高频率的小数据包发送场景下。
    因此，是否启用 TCP_NODELAY 需要根据具体的应用需求和网络条件来决定。在对延迟敏感的场景中，启用 TCP_NODELAY 可以带来更好的用户体验，而在对带宽利用率有更高要求的场景中，则可能需要保留 Nagle 算法的默认行为。
- optval：指针，指向一个缓冲区，该缓冲区包含要设置的选项值。缓冲区的内容和类型根据optname的不同而变化。
- optlen：表示optval缓冲区的大小，以字节为单位。

SO_KEEPALIVE相关示例: 
```c
// 设置在2小时后开始发送keepalive探测包
int idle = 7200;
setsockopt(sockfd, IPPROTO_TCP, TCP_KEEPIDLE, &idle, sizeof(idle));

// 设置每30秒发送一次探测包
int interval = 30;
setsockopt(sockfd, IPPROTO_TCP, TCP_KEEPINTVL, &interval, sizeof(interval));

// 设置发送5次探测包后放弃连接
int cnt = 5;
setsockopt(sockfd, IPPROTO_TCP, TCP_KEEPCNT, &cnt, sizeof(cnt));
```

#### bind
```c
#include <sys/socket.h>

int bind(int sockfd, const struct sockaddr *addr, socklen_t addrlen);
```
bind() 函数用于将一个套接字与一个本地网络地址（包括 IP 地址和端口号）相关联。这是创建一个用于监听网络连接的套接字所必需的步骤之一。在调用 bind() 成功之后，套接字就被绑定到了指定的地址上，后续的连接请求将会被定向到这个地址。