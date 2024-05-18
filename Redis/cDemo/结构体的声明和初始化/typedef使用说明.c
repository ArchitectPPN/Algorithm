#include <stdio.h>
#include <string.h>

// 声明一个PersonDemo的结构体
struct PersonDemo {
    char Name[35];
    int Age;
    char Sex[5];
};


// 给PersonDemo起一个别名叫definedPerson
typedef struct PersonDemo definedPerson;
/**
 * typedef 是 C 和 C++ 语言中的一个关键字，用于创建新的类型别名。
 * 上面就是为PersonDemo新建了一个别名叫definedPerson，
 * 于是我们可以直接使用definedPerson声明一个变量。
 * typedef int Integer;
 * Integer x, y, z; // x, y, z 都是 int 类型
 *
 * @return
 */
int main()
{
    // 声明一个Person 结构体变量
    struct PersonDemo Person;

    // 给变量初始化
    Person.Age = 21;
    strcpy(Person.Name, "创建后初始化");
    strcpy(Person.Sex, "man");

    printf("名字:%s, 年龄: %d, 性别: %s", Person.Name, Person.Age, Person.Sex);

    // 我们也可以直接用definedPerson定一个一个变量
    definedPerson Person2;
    Person2.Age = 22;
    strcpy(Person2.Name, "使用typedef别名创建的变量");
    strcpy(Person2.Sex, "man");

    printf("名字:%s, 年龄: %d, 性别: %s", Person.Name, Person.Age, Person.Sex);

    return 0;
}