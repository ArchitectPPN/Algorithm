//
// Created by niujunqing on 2024/7/29.
//

#include <stdio.h>
#include <stdlib.h>

//int * func() {
//    int a = 10;
//    int * pa = &a;
//    return pa;
//}
//
//int * func2() {
//    int b = 20;
//    int * p = &b;
//    return p;
//}

int * func() {
    int * pa = (int *)malloc(sizeof(int));
    *pa = 10;
    return pa;
}

int * func2() {
    int * pb = (int *)malloc(sizeof(int));
    *pb = 22;
    return pb;
}

int main() {
    int * pa = func();
    printf("pa: addr:%p value: %d\n", pa, *pa);

    int * pb = func2();
    printf("pb: addr:%p value: %d\n", pb, *pb);

    // 重新打印pa
    printf("pa: addr:%p value: %d\n", pa, *pa);

    return 0;
}