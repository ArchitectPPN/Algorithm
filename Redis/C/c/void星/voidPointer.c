//
// Created by niujunqing on 2024/8/22.
//

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

int main() {
    // 声明一个指针变量，指向任意类型的数据
    void *sh;
    char *s;
    // 要写入的字符
    const void *init = "njqm";
    printf("init = %s initLen=%zu \n", (char *) init, strlen((char *) init));

    // 头部空间
    int headLen = 1;
    // 内容长度
    int content = 4;
    // 尾部空间,结束符 \0
    int tail = 1;

    // 给该指针申请内存
    sh = malloc(headLen + content + tail);
    // 初始化
    memset(sh, 1, headLen + content + tail);
    // 此时只能打印指针的地址, 不能打印内容
    // 因为memset只是将内存中的值设置为1, 如果尝试  printf("sh = %s\n", (char*)sh); 打印, 由于没有尾部结束符\0, 会导致未定义的行为
    // 最后输出的值可能不是你想要的结果
    printf("sh = %p\n", sh);

    // 开始操作该块内存
    // 该函数将指针sh向后移动hdrlen个字节，并将移动后的指针强制转换为char*类型，然后赋值给s。
    s = (char *) sh + headLen;
    printf("s = %p\n", s);

    unsigned char *fp; /* flags pointer. */
    fp = ((unsigned char *) s) - 1;
    printf("fp = %p\n", fp);
    *fp = 8;
    printf("fp = %d\n", *fp);
    // s 赋值
    memcpy(s, init, content);
    s[4] = '\0';

    printf("s = %s\n", s); // s = njqm
    // 这里输出时, sh =后面的空格未输出,
    printf("sh = %s\n", (char *) sh); // sh =njqm
    printf("sh = %d%s\n", *((unsigned char *) sh), (char *) (sh + 1));// sh = 8njqm

    return 1;
}

