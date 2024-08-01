//
// Created by niujunqing on 2024/7/22.
//
#include <stdio.h>

#define redisDebug(fmt, ...) \
    printf("DEBUG %s:%d > " fmt "\n", __FILE__, __LINE__, __VA_ARGS__)
#define redisMark() \
    printf("Mark %s:%d \n", __FILE__, __LINE__)
int main() {
    redisDebug("test Demo %d", 5);
    redisMark();

    return 1;
}