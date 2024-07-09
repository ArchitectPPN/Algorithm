c
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <arpa/inet.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/select.h>
#include <netinet/in.h>
#include <errno.h>

#define PORT 8080
#define MAX_CLIENTS 10
#define BUFFER_SIZE 1024

int main() {
    int server_sock, client_sock, max_sd, activity, new_socket, sd;
    int client_sockets[MAX_CLIENTS];
    struct sockaddr_in address;
    fd_set readfds;
    char buffer[BUFFER_SIZE];
    int addrlen = sizeof(address);

    // 初始化所有客户端套接字为0
    for (int i = 0; i < MAX_CLIENTS; i++) {
        client_sockets[i] = 0;
    }

    // 创建主套接字
    if ((server_sock = socket(AF_INET, SOCK_STREAM, 0)) == 0) {
        perror("socket failed");
        exit(EXIT_FAILURE);
    }

    // 设置服务器地址及端口
    address.sin_family = AF_INET;
    address.sin_addr.s_addr = INADDR_ANY;
    address.sin_port = htons(PORT);

    // 绑定套接字
    if (bind(server_sock, (struct sockaddr *)&address, sizeof(address)) < 0) {
        perror("bind failed");
        close(server_sock);
        exit(EXIT_FAILURE);
    }

    // 监听连接
    if (listen(server_sock, 3) < 0) {
        perror("listen failed");
        close(server_sock);
        exit(EXIT_FAILURE);
    }

    printf("Listening on port %d...\n", PORT);

    while (1) {
        // 清空fd集合并添加主套接字
        FD_ZERO(&readfds);
        FD_SET(server_sock, &readfds);
        max_sd = server_sock;

        // 添加客户端套接字到集合中
        for (int i = 0; i < MAX_CLIENTS; i++) {
            sd = client_sockets[i];

            if (sd > 0)
                FD_SET(sd, &readfds);

            if (sd > max_sd)
                max_sd = sd;
        }

        // 选择活动套接字
        activity = select(max_sd + 1, &readfds, NULL, NULL, NULL);

        if ((activity < 0) && (errno != EINTR)) {
            perror("select error");
        }

        // 检查主套接字是否有新连接
        if (FD_ISSET(server_sock, &readfds)) {
            if ((new_socket = accept(server_sock, (struct sockaddr *)&address, (socklen_t *)&addrlen)) < 0) {
                perror("accept error");
                exit(EXIT_FAILURE);
            }

            printf("New connection: socket fd is %d, ip is: %s, port: %d\n",
                   new_socket, inet_ntoa(address.sin_addr), ntohs(address.sin_port));

            // 将新套接字添加到客户端数组中
            for (int i = 0; i < MAX_CLIENTS; i++) {
                if (client_sockets[i] == 0) {
                    client_sockets[i] = new_socket;
                    printf("Adding to list of sockets as %d\n", i);
                    break;
                }
            }
        }

        // 处理中已有连接的数据
        for (int i = 0; i < MAX_CLIENTS; i++) {
            sd = client_sockets[i];

            if (FD_ISSET(sd, &readfds)) {
                int valread;

                if ((valread = read(sd, buffer, BUFFER_SIZE)) == 0) {
                    // 客户端断开连接
                    getpeername(sd, (struct sockaddr *)&address, (socklen_t *)&addrlen);
                    printf("Host disconnected: ip %s, port %d\n",
                           inet_ntoa(address.sin_addr), ntohs(address.sin_port));

                    close(sd);
                    client_sockets[i] = 0;
                } else {
                    // 发回数据到其他客户端
                    buffer[valread] = '\0';
                    for (int j = 0; j < MAX_CLIENTS; j++) {
                        if (client_sockets[j] != 0 && client_sockets[j] != sd) {
                            send(client_sockets[j], buffer, strlen(buffer), 0);
                        }
                    }
                }
            }
        }
    }

    return 0;
}