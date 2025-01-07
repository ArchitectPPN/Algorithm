//
// Created by niujunqing on 2024/5/31.
//


#include <stdio.h>
#include <string.h>

#define LONG_STR_SIZE      21

int main() {
    char buf[LONG_STR_SIZE];
    long long val = 1234567890123456789;
    int vlen = ll2string(buf,sizeof(buf),val);
    printf("vlen = %d, buf = %s\n",vlen,buf);
}