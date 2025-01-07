//
// Created by niujunqing on 2024/8/13.
//

#include <stdio.h>
#include <string.h>

int main(int argc, char *argv[]) {

    if (argc < 2) {
        printf("Params Less then 2\n");
        return 0;
    }

    char *newLIne = strchr(argv[1], 'p');
    if (newLIne == NULL) {
        printf("Err \n");
        return 1;
    } else {
        printf("newLIne is: %s \n", newLIne);
        printf("%c before char is: %c \n", *newLIne, *(newLIne - 1));
        printf("%c after char is: %c \n", *newLIne, *(newLIne + 1));
    }

    return 0;
}