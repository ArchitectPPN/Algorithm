//
// Created by niujunqing on 2022/7/7.
//
#include <stdio.h>
#include <string.h>

int main(int argc, char* argv[])
{
    int i;
    printf("argc:%d\n", argc);

    for(i = 0; i < argc; i++) {
        if(strcmp(argv[i], "version") == 0) {
            printf("version: %s \n", "5.0.3");
        } else {
            printf("argv[%d]: %s\n", i, argv[i]);
        }
    }

    return 0;
}