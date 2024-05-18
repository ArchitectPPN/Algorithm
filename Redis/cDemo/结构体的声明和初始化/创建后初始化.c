#include <stdio.h>
#include <string.h>

// 声明一个PersonDemo的结构体
struct PersonDemo {
    char Name[20];
    int Age;
    char Sex[5];
};

int main()
{
    // 声明一个Person 结构体变量
    struct PersonDemo Person;

    // 给变量初始化
    Person.Age = 21;
    strcpy(Person.Name, "创建后初始化");
    strcpy(Person.Sex, "man");

    printf("名字:%s, 年龄: %d, 性别: %s", Person.Name, Person.Age, Person.Sex);

    return 0;
}