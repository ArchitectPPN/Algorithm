在 C 语言中，与指针相关的操作符主要有以下几个：
#### 取地址运算符 &
- 用途：获取一个变量的内存地址。
- 示例：int a = 10; int *p = &a; 这里 &a 获取了变量 a 的地址，并将其赋值给指针 p。
#### 间接引用运算符（指针运算符） *
- 用途：访问指针所指向的变量的值。
- 示例：int a = 10; int *p = &a; int b = *p; 这里 *p 表示获取指针 p 所指向的值，即 a 的值。
#### 指针算术运算符 +, -
- 用途：对指针进行加减操作，使其指向数组中的其他元素。
- 示例：int arr[5] = {1, 2, 3, 4, 5}; int *p = &arr[0]; p++; 这里 p++ 使指针 p 指向数组的下一个元素。
```c
#include <stdio.h>
#include <string.h>

int main() {
    const char *str = "Hello, world!";
    char *result;

    // 查找字符 'o'
    result = strchr(str, 'o');
    if (result != NULL) {
        printf("Found character 'o' at position: %ld\n", result - str + 1);
    } else {
        printf("Character not found.\n");
    }

    return 0;
}
```
#### 指针比较运算符 ==, !=
- 用途：比较两个指针是否指向同一内存地址。
- 示例：int a = 10; int *p = &a, *q = &a; if (p == q) { ... } 这里 p == q 判断两个指针是否指向相同的地址。
#### 指针与数组下标运算符 []
- 用途：通过指针访问数组元素。
- 示例：int arr[5] = {1, 2, 3, 4, 5}; int *p = arr; int b = p[2]; 这里 p[2] 等价于 *(p + 2)，即获取数组第三个元素的值。
#### 指针与逗号运算符 ,
- 用途：用于多个指针的初始化。
- 示例：int a = 10, b = 20; int *p1 = &a, *p2 = &b; 这里使用逗号运算符来同时初始化多个指针。
#### 指针与条件运算符 ?:
- 用途：用于根据条件选择不同的指针赋值。
- 示例：int a = 10, b = 20; int *p = (a > b) ? &a : &b; 这里根据条件选择 p 指向 a 或 b。
#### 指针与赋值运算符 =
- 用途：将一个指针的值赋给另一个指针。
- 示例：int a = 10; int *p = &a, *q; q = p; 这里 q = p 使 q 指向与 p 相同的地址。
#### 指针与复合赋值运算符 +=, -=
- 用途：修改指针所指向的地址。
- 示例：int arr[5] = {1, 2, 3, 4, 5}; int *p = &arr[0]; p += 2; 这里 p += 2 使指针 p 向后移动两个元素。