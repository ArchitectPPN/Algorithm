//
// Created by  on 2022/7/7.
//
#include <stdio.h>
#include <string.h>

typedef struct users {
    int age;
    char name[10];
} user;

int main(int argc, char* argv[])
{
    user *Niu;
    Niu->age = 21;
    Niu->name[0] = '1';

    printf("%d", Niu->age);

    return 0;
}