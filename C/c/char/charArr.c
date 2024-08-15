#include <stdio.h>

int main() {
    char str[] = "Hello\r\nWorld";

    // 输出整个字符串
    printf("outPutAllStr: %s\n", str);

    // 输出字符串中的第一个字符
    printf("outPutFirstChar: %c\n", str[0]);
    printf("outPutFirstChar: %c\n", *str);

    // 如果你显式地使用间接引用运算符 * 来打印指针所指向的单个字符，那么你需要使用 %c 格式化字符串，而不是 %s。
    // 这是因为 %c 用于打印单个字符，而 %s 用于打印字符串。
    char *ptr = str + 1; // 移动指针到 'e'
    // 使用 %s 和 ptr 可以打印指针所指向的字符串或子字符串。
    printf("Substring from 'e': %s\n", ptr);
    // 使用 %c 和 *ptr 可以打印指针所指向的单个字符。
    printf("Substring from 'e': %c\n", *ptr);

    return 0;
}