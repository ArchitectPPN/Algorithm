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
    //  fcntl函数是一个用于控制文件描述符的函数，其中sock是文件描述符，F_GETFL是一个标志，表示获取当前文件描述符的标志位。
    //  函数执行成功后，返回获取的标志位值，并将其存储在opts变量中。
    int opts = fcntl(sock, F_GETFL);
    if (opts < 0) {
        perror("fcntl(F_GETFL)");
        exit(EXIT_FAILURE);
    }

    opts = (opts | O_NONBLOCK);
    // 设置文件描述符为非阻塞模式
    if (fcntl(sock, F_SETFL, opts) < 0) {
        perror("fcntl(F_SETFL)");
        exit(EXIT_FAILURE);
    }
}

int main() {
    // listen_sock：监听套接字。
    // conn_sock：连接套接字。
    // nfds：epoll_wait 返回的文件描述符数量。
    // epoll_fd：epoll 文件描述符。
    // server_addr：服务器地址结构。
    // ev 和 events：epoll 事件结构体。
    int listen_sock, conn_sock, nfds, epoll_fd;
    struct sockaddr_in server_addr;
    struct epoll_event ev, events[MAX_EVENTS];

    listen_sock = socket(AF_INET, SOCK_STREAM, 0);
    if (listen_sock < 0) {
        perror("socket");
        exit(EXIT_FAILURE);
    }

    // 设置监听套接字为非阻塞模式。
    set_nonblocking(listen_sock);

    // 清零 server_addr。
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
                    printf("Received: %s\n", buf);
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