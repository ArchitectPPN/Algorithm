//
// Created by niujunqing on 2024/7/26.
//

#include <stdio.h>

int main() {
    char size[] = {'1', '2', '\0'};
    size_t pp = sizeof(size);
    printf("%zu \n", pp);

    return 1;
}