#include <stdio.h>
#include <string.h>

// 声明一个PersonDemo的结构体
struct PersonDemo {
    char Name[15];
    int Age;
    char Sex[5];
};

int main()
{
    // 声明一个Person 结构体变量
    struct PersonDemo Person = {
            .Name = "我的名称",
            .Age = 21,
            .Sex = "Male"
    };

    printf("名字:%s, 年龄: %d, 性别: %s", Person.Name, Person.Age, Person.Sex);

    return 0;
}