#include <stdio.h>
#include <stdlib.h>
#include <winsock2.h>
#include <ws2tcpip.h>

#define PORT 8080
#define BUFFER_SIZE 1024

void initialize_winsock() {
    WSADATA wsaData;
    int result = WSAStartup(MAKEWORD(2, 2), &wsaData);
    if (result != 0) {
        printf("WSAStartup failed: %d\n", result);
        exit(EXIT_FAILURE);
    }
}

void cleanup_winsock() {
    WSACleanup();
}

int main() {
    initialize_winsock();

    SOCKET sockfd, newsockfd;
    struct sockaddr_in serv_addr, cli_addr;
    char buffer[BUFFER_SIZE];
    fd_set readfds;
    int max_fd, activity;
    int cli_len;

    // 创建套接字
    if ((sockfd = socket(AF_INET, SOCK_STREAM, 0)) == INVALID_SOCKET) {
        perror("socket failed");
        cleanup_winsock();
        exit(EXIT_FAILURE);
    }

    // 配置服务器地址和端口
    serv_addr.sin_family = AF_INET;
    serv_addr.sin_addr.s_addr = INADDR_ANY;
    serv_addr.sin_port = htons(PORT);

    // 绑定套接字
    if (bind(sockfd, (struct sockaddr *)&serv_addr, sizeof(serv_addr)) == SOCKET_ERROR) {
        perror("bind failed");
        closesocket(sockfd);
        cleanup_winsock();
        exit(EXIT_FAILURE);
    }

    // 监听套接字
    if (listen(sockfd, 3) == SOCKET_ERROR) {
        perror("listen failed");
        closesocket(sockfd);
        cleanup_winsock();
        exit(EXIT_FAILURE);
    }

    printf("Listening on port %d...\n", PORT);

    while (1) {
        // 清空读文件描述符集合
        FD_ZERO(&readfds);

        // 添加标准输入（文件描述符 0）
        FD_SET(STDIN_FILENO, &readfds);
        max_fd = STDIN_FILENO;

        // 添加监听套接字
        FD_SET(sockfd, &readfds);
        if ((int)sockfd > max_fd) {
            max_fd = (int)sockfd;
        }

        // 调用 select 监视文件描述符
        activity = select(max_fd + 1, &readfds, NULL, NULL, NULL);

        if (activity < 0 && WSAGetLastError() != WSAEINTR) {
            perror("select error");
            closesocket(sockfd);
            cleanup_winsock();
            exit(EXIT_FAILURE);
        }

        // 检查标准输入是否有数据可读
        if (FD_ISSET(STDIN_FILENO, &readfds)) {
            memset(buffer, 0, BUFFER_SIZE);
            if (fgets(buffer, BUFFER_SIZE, stdin) != NULL) {
                printf("标准输入: %s", buffer);
            }
        }

        // 检查监听套接字是否有连接请求
        if (FD_ISSET(sockfd, &readfds)) {
            cli_len = sizeof(cli_addr);
            if ((newsockfd = accept(sockfd, (struct sockaddr *)&cli_addr, &cli_len)) == INVALID_SOCKET) {
                perror("accept failed");
                closesocket(sockfd);
                cleanup_winsock();
                exit(EXIT_FAILURE);
            }

            // 读取客户端发送的数据
            memset(buffer, 0, BUFFER_SIZE);
            if (recv(newsockfd, buffer, BUFFER_SIZE, 0) > 0) {
                printf("收到客户端数据: %s\n", buffer);
            }
            closesocket(newsockfd);
        }
    }

    closesocket(sockfd);
    cleanup_winsock();
    return 0;
}
