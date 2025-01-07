//
// Created by niujunqing on 2024/7/18.
//

#include <stdio.h>
#include <sys/socket.h>
#include <netdb.h>
#include <string.h>
#include <arpa/inet.h>

int main() {
    int rv;

    char _port[6];
    snprintf(_port, 6, "%d", 8080);

    printf("port: %s\n", _port);

    // 设置服务器地址
    struct addrinfo hints, *servinfo, *p;
    memset(&hints, 0, sizeof(hints));

    // 设置为ipv4地址
    hints.ai_family = AF_INET;
    // 套接字类型，表示我们将使用流式套接字，即 TCP 协议
    hints.ai_socktype = SOCK_STREAM;
    // 输入标志 表示我们打算使用返回的地址信息来绑定一个监听套接字，通常用于服务器端
    hints.ai_flags = AI_PASSIVE;

    // 获取地址信息
    rv = getaddrinfo("127.0.0.1", _port, &hints, &servinfo);
    printf("getAddressInfoRes: %d \n", rv);
    if (rv != 0) {
        return -1;
    }

    // 循环打印地址
    for (p = servinfo; p != NULL; p = p->ai_next) {
        // 输出Ipv4地址
        if (p->ai_family == AF_INET) {
            / 将通用的 sockaddr 指针强制转换为 sockaddr_in 类型的指针
            // 这样就可以访问 IPv4 地址的特定成员
            struct sockaddr_in *ipv4 = (struct sockaddr_in *) p->ai_addr;

            // 定义一个字符数组用于存储转换后的 IPv4 地址字符串
            // INET_ADDRSTRLEN 是一个宏，定义了存储 IPv4 地址字符串所需的最小长度
            char str[INET_ADDRSTRLEN];

            // 使用 inet_ntop 函数将二进制的 IPv4 地址转换为点分十进制格式的字符串
            // 参数依次为地址族、IPv4 地址的二进制表示、用于存储字符串的缓冲区、缓冲区的大小
            inet_ntop(p->ai_family, &ipv4->sin_addr, str, INET_ADDRSTRLEN);

            // 打印转换后的 IPv4 地址字符串
            printf("IPv4 Address: %s\n", str);
        }

        if (p->ai_family == AF_INET6) {
            struct sockaddr_in6 *ipv6 = (struct sockaddr_in6 *) p->ai_addr;
            char str[INET6_ADDRSTRLEN];
            inet_ntop(p->ai_family, &ipv6->sin6_addr, str, INET6_ADDRSTRLEN);
            printf("IPv6 Address: %s\n", str);
        }
    }

    return 0;
}