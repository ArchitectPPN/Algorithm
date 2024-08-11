//
// Created by ppn on 2024/8/11.
//

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

int main(int argc, char *argv[]) {
    char *filename;
    int fix = 0;
    if (argc < 2) {
        printf("Usage: %s [--fix] <file.aof>\n", argv[0]);
    } else if (argc == 2) {
        filename = argv[1];
        printf("Input FileName %s\n", filename);
    } else if (argc == 3) {
        // 第一个参数必须为--fix
        if (strcmp(argv[1], "--fix") != 0) {
            printf("Invalid argument: %s \n", argv[1]);
            return 0;
        }
        // 第三个参数为文件名
        filename = argv[2];
        fix = 1;
        printf("Input need fix: %d \n", fix);
    } else {
        printf("Invalid Argument\n");
    }

    return 1;
}