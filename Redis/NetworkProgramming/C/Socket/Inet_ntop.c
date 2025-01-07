//
// Created by niujunqing on 2024/7/18.
//
#include <stdio.h>
#include <string.h>
#include <arpa/inet.h>

int main() {
    struct sockaddr_in ipv4_addr;
    inet_pton(AF_INET, "192.168.1.1", &ipv4_addr.sin_addr);
    char ipv4_str[INET_ADDRSTRLEN];
    inet_ntop(AF_INET, &ipv4_addr.sin_addr, ipv4_str, INET_ADDRSTRLEN);
    printf("IPv4 address: %s\n", ipv4_str);

    struct sockaddr_in6 ipv6_addr;
    inet_pton(AF_INET6, "2001:0db8:85a3:0000:0000:8a2e:0370:7334", &ipv6_addr.sin6_addr);
    char ipv6_str[INET6_ADDRSTRLEN];
    inet_ntop(AF_INET6, &ipv6_addr.sin6_addr, ipv6_str, INET6_ADDRSTRLEN);
    printf("IPv6 address: %s\n", ipv6_str);

    return 0;
}