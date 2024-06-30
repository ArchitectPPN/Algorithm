#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/epoll.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <fcntl.h>

#define MAX_EVENTS 10
#define BUFFER_SIZE 1024
#define PORT 12345

typedef struct Client {
    int fd;
    struct Client *next;
} Client;

Client *clients_list = NULL;

void add_client(int client_fd) {
    Client *new_client = (Client *)malloc(sizeof(Client));
    new_client->fd = client_fd;
    new_client->next = clients_list;
    clients_list = new_client;
}

void remove_client(int client_fd) {
    Client **current = &clients_list;
    while (*current) {
        Client *entry = *current;
        if (entry->fd == client_fd) {
            *current = entry->next;
            free(entry);
            return;
        }
        current = &entry->next;
    }
}

void set_nonblocking(int fd) {
    int flags = fcntl(fd, F_GETFL, 0);
    if (flags == -1) {
        perror("fcntl F_GETFL");
        exit(EXIT_FAILURE);
    }
    flags |= O_NONBLOCK;
    if (fcntl(fd, F_SETFL, flags) == -1) {
        perror("fcntl F_SETFL");
        exit(EXIT_FAILURE);
    }
}

void handle_client_message(int client_fd, int epoll_fd, struct epoll_event *events, int num_fds, int server_fd) {
    char buffer[BUFFER_SIZE];
    int bytes_read;

    // 读取客户端消息
    bytes_read = read(client_fd, buffer, BUFFER_SIZE);
    if (bytes_read == -1) {
        perror("read");
        close(client_fd);
        remove_client(client_fd);
        epoll_ctl(epoll_fd, EPOLL_CTL_DEL, client_fd, NULL);
        return;
    } else if (bytes_read == 0) {
        // 客户端断开连接
        printf("Client (fd: %d) disconnected\n", client_fd);
        close(client_fd);
        remove_client(client_fd);
        epoll_ctl(epoll_fd, EPOLL_CTL_DEL, client_fd, NULL);
        return;
    }

    buffer[bytes_read] = '\0';
    printf("Received message from client (fd: %d): %s", client_fd, buffer);

    // 分发消息给其他客户端
    Client *current = clients_list;
    while (current) {
        int other_fd = current->fd;
        if (other_fd != client_fd) {
            if (write(other_fd, buffer, bytes_read) == -1) {
                perror("write");
                close(other_fd);
                remove_client(other_fd);
                epoll_ctl(epoll_fd, EPOLL_CTL_DEL, other_fd, NULL);
            }
        }
        current = current->next;
    }
}

int main() {
    int server_fd, epoll_fd, num_fds, client_fd;
    struct sockaddr_in server_addr, client_addr;
    socklen_t client_addr_len = sizeof(client_addr);
    struct epoll_event ev, events[MAX_EVENTS];

    // 创建监听socket
    if ((server_fd = socket(AF_INET, SOCK_STREAM, 0)) == -1) {
        perror("socket");
        exit(EXIT_FAILURE);
    }

    // 设置服务器地址
    memset(&server_addr, 0, sizeof(server_addr));
    server_addr.sin_family = AF_INET;
    server_addr.sin_addr.s_addr = INADDR_ANY;
    server_addr.sin_port = htons(PORT);

    // 绑定socket
    if (bind(server_fd, (struct sockaddr *)&server_addr, sizeof(server_addr)) == -1) {
        perror("bind");
        close(server_fd);
        exit(EXIT_FAILURE);
    }

    // 监听socket
    if (listen(server_fd, 10) == -1) {
        perror("listen");
        close(server_fd);
        exit(EXIT_FAILURE);
    }

    // 创建epoll实例
    if ((epoll_fd = epoll_create1(0)) == -1) {
        perror("epoll_create1");
        close(server_fd);
        exit(EXIT_FAILURE);
    }

    // 将服务器socket加入epoll监听列表
    ev.events = EPOLLIN;
    ev.data.fd = server_fd;
    if (epoll_ctl(epoll_fd, EPOLL_CTL_ADD, server_fd, &ev) == -1) {
        perror("epoll_ctl: server_fd");
        close(server_fd);
        close(epoll_fd);
        exit(EXIT_FAILURE);
    }

    // 主循环
    while (1) {
        num_fds = epoll_wait(epoll_fd, events, MAX_EVENTS, -1);
        if (num_fds == -1) {
            perror("epoll_wait");
            close(server_fd);
            close(epoll_fd);
            exit(EXIT_FAILURE);
        }

        for (int i = 0; i < num_fds; i++) {
            if (events[i].data.fd == server_fd) {
                // 处理新连接
                client_fd = accept(server_fd, (struct sockaddr *)&client_addr, &client_addr_len);
                if (client_fd == -1) {
                    perror("accept");
                    continue;
                }

                // 设置新连接为非阻塞
                set_nonblocking(client_fd);

                // 将新连接加入epoll监听列表
                ev.events = EPOLLIN | EPOLLET;
                ev.data.fd = client_fd;
                if (epoll_ctl(epoll_fd, EPOLL_CTL_ADD, client_fd, &ev) == -1) {
                    perror("epoll_ctl: client_fd");
                    close(client_fd);
                    continue;
                }

                // 将新客户端添加到客户端列表
                add_client(client_fd);

                printf("New connection from %s:%d (fd: %d)\n",
                       inet_ntoa(client_addr.sin_addr),
                       ntohs(client_addr.sin_port),
                       client_fd);
            } else {
                // 处理客户端消息
                handle_client_message(events[i].data.fd, epoll_fd, events, num_fds, server_fd);
            }
        }
    }

    // 清理资源
    close(server_fd);
    close(epoll_fd);
    return 0;
}
