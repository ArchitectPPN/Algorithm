#include <stdio.h>
#include <string.h>

// 直接声明一个struct的变量
struct DefineStructVar {
    char StructName[35];
} DefineVar;

// 声明一个PersonDemo的结构体
struct PersonDemo {
    char StructName[35];
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

// 为int取一个别名叫Integer
typedef int Integer;
typedef struct UserDemo {
    char StructName[9];
    Integer Age;
} User;

int main()
{
    // 声明一个Person 结构体变量
    struct PersonDemo Person;

    // 给变量初始化
    Person.Age = 21;
    strcpy(Person.StructName, "创建后初始化");
    strcpy(Person.Sex, "man");

    printf("结构体名称:%s, 年龄: %d, 性别: %s \n", Person.StructName, Person.Age, Person.Sex);

    // 我们也可以直接用definedPerson定义一个变量
    definedPerson Person2;
    Person2.Age = 22;
    strcpy(Person2.StructName, "使用typedef别名创建的变量");
    strcpy(Person2.Sex, "man");

    printf("结构体名称:%s, 年龄: %d, 性别: %s \n", Person.StructName, Person.Age, Person.Sex);

    User user;
    user.Age = 22;
    strcpy(user.StructName, "ppn");
    printf("结构体名称:%s, 年龄: %d \n", user.StructName, user.Age);

    // 使用直接声明的结构体变量
    strcpy(DefineVar.StructName, "DefineStructVar");
    printf("结构体名称:%s \n", DefineVar.StructName);

    return 0;
}