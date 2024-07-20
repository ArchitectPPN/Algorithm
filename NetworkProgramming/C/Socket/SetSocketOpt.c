//
// Created by niujunqing on 2024/7/19.
//
#include <stdio.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <netinet/tcp.h>
#include <unistd.h>

int main() {
    // 创建socket套接字
    int sfd = socket(AF_INET, SOCK_STREAM, 0);
    if(sfd == -1) {
        printf("socket create failed %d \n", sfd);
        close(sfd);
        return 0;
    }

    int enable = 1;
    if (setsockopt(sfd, SOL_SOCKET, SO_REUSEADDR, &enable, sizeof(enable)) == -1) {
        printf("set socket option failed %d \n", sfd);
        close(sfd);
        return 0;
    }

    printf("socket set success %d \n", sfd);
    close(sfd);
    return 1;
}