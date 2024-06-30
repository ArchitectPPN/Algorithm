//
// Created by ppn on 24-6-27.
//

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <fcntl.h>
#include <sys/epoll.h>

#define PORT 8080
#define MAX_EVENTS 10
#define BUF_SIZE 1024

// 设置非阻塞
void set_nonblocking(int sock) {
    printf("当前sock文件描述符为：%d \n", sock);

    //  fcntl函数是一个用于控制文件描述符的函数，其中sock是文件描述符，F_GETFL是一个标志，表示获取当前文件描述符的标志位。
    // F_GETFL: 获取文件描述符状态标志
    // F_SETFL: 设置文件描述符状态标志
    // F_GETFD: 获取文件描述符标志
    // F_SETFD: 设置文件描述符标志
    // 函数执行成功后，返回获取的标志位值，并将其存储在opts变量中。
    int opts = fcntl(sock, F_GETFL);
    if (opts < 0) {
        perror("fcntl(F_GETFL)");
        exit(EXIT_FAILURE);
    }

    // 设置文件描述符为非阻塞模式
    opts = (opts | O_NONBLOCK);
    if (fcntl(sock, F_SETFL, opts) < 0) {
        perror("fcntl(F_SETFL)");
        exit(EXIT_FAILURE);
    }
}

int main() {
    // listen_sock：监听套接字。
    int listen_sock;
    // conn_sock：连接套接字。
    int conn_sock;
    // nfds：epoll_wait 返回的文件描述符数量。
    int nfds;
    // epoll_fd：epoll 文件描述符。
    int epoll_fd;

    // server_addr：服务器地址结构。
    struct sockaddr_in server_addr;

    // ev 和 events：epoll 事件结构体。
    struct epoll_event ev, events[MAX_EVENTS];

    /**
     *  该行代码创建了一个新的套接字（socket），其作用是在网络编程中准备一个用于通信的端点。这里是三个参数：
     *  1. AF_INET：指定地址族为IPv4，表示使用Internet Protocol version 4的地址和端口进行通信。
     *  2. SOCK_STREAM：指定套接字类型为流式套接字（TCP）。TCP是一种面向连接的、可靠的、基于字节流的通信协议。
     *  3. 0：作为协议参数，通常对于AF_INET地址族和SOCK_STREAM类型套接字，此参数设置为0，让系统选择默认的TCP协议。
     *  socket()函数返回新创建的套接字描述符（一个整数），这里将其存储在listen_sock变量中，
     *  后续用于绑定（bind()）、监听（listen()）和接受连接（accept()）等操作，是构建服务器端程序的基础步骤。
     */
    listen_sock = socket(AF_INET, SOCK_STREAM, 0);
    if (listen_sock < 0) {
        perror("socket");
        exit(EXIT_FAILURE);
    }

    // 设置监听套接字为非阻塞模式。
    set_nonblocking(listen_sock);

    /**
     * 该行代码的作用是使用memset函数将server_addr这个结构体变量的所有字节初始化为0。这里是三个参数：
     * 1. &server_addr：是指向server_addr结构体变量地址的指针，作为memset要清零的内存区域的起始地址。
     * 2. 0：指定要设置的字节值，这里是0，意味着将内存区域的每个字节都设置为0。
     * 3. sizeof(server_addr)：计算server_addr结构体变量的总字节数，作为memset要操作的字节长度，确保结构体内所有成员都被初始化。
     * 此操作通常在设置如sockaddr_in等网络地址结构体前进行，以避免结构体中遗留有不确定的值，保证数据的正确性和安全性。
     */
    memset(&server_addr, 0, sizeof(server_addr));
    //
    server_addr.sin_family = AF_INET;
    // 设置服务器地址为 INADDR_ANY，表示监听网路接口
    server_addr.sin_addr.s_addr = INADDR_ANY;
    // 设置服务器端口号为 8080。
    server_addr.sin_port = htons(PORT);

    // 绑定监听套接字到指定地址和端口，如果失败则打印错误并退出。
    if (bind(listen_sock, (struct sockaddr *)&server_addr, sizeof(server_addr)) < 0) {
        perror("bind");
        close(listen_sock);
        exit(EXIT_FAILURE);
    }

    // 开始监听传入连接，最多允许 10 个待处理连接。
    if (listen(listen_sock, 10) < 0) {
        perror("listen");
        close(listen_sock);
        exit(EXIT_FAILURE);
    }

    epoll_fd = epoll_create1(0);
    if (epoll_fd == -1) {
        perror("epoll_create1");
        close(listen_sock);
        exit(EXIT_FAILURE);
    }

    ev.events = EPOLLIN;
    ev.data.fd = listen_sock;
    if (epoll_ctl(epoll_fd, EPOLL_CTL_ADD, listen_sock, &ev) == -1) {
        perror("epoll_ctl: listen_sock");
        close(listen_sock);
        exit(EXIT_FAILURE);
    }

    while (1) {
        nfds = epoll_wait(epoll_fd, events, MAX_EVENTS, -1);
        if (nfds == -1) {
            perror("epoll_wait");
            close(listen_sock);
            exit(EXIT_FAILURE);
        }

        // nfds 返回已就绪文件描述符数量
        for (int n = 0; n < nfds; ++n) {
            if (events[n].data.fd == listen_sock) {
                struct sockaddr_in client_addr;
                socklen_t client_len = sizeof(client_addr);
                conn_sock = accept(listen_sock, (struct sockaddr *)&client_addr, &client_len);
                if (conn_sock == -1) {
                    perror("accept");
                    continue;
                }
                set_nonblocking(conn_sock);
                ev.events = EPOLLIN | EPOLLET;
                ev.data.fd = conn_sock;
                if (epoll_ctl(epoll_fd, EPOLL_CTL_ADD, conn_sock, &ev) == -1) {
                    perror("epoll_ctl: conn_sock");
                    close(conn_sock);
                    continue;
                }
                printf("New connection on socket %d\n", conn_sock);
            } else {
                char buf[BUF_SIZE];
                int bytes_read = read(events[n].data.fd, buf, sizeof(buf) - 1);
                if (bytes_read == -1) {
                    perror("read");
                    close(events[n].data.fd);
                } else if (bytes_read == 0) {
                    printf("Client disconnected on socket %d\n", events[n].data.fd);
                    close(events[n].data.fd);
                } else {
                    buf[bytes_read] = '\0';
                    printf("Server Received: %s\n", buf);
                    printf("sendSock %d \n", events[n].data.fd);
                    if (write(events[n].data.fd, buf, bytes_read) == -1) {
                        perror("write");
                        close(events[n].data.fd);
                    }
                }
            }
        }
    }

    close(listen_sock);
    return 0;
}