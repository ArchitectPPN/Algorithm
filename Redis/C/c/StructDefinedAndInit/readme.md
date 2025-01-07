### 结构体的声明和定义
- 创建一个结构体变量，可以直接使用DefineVar这个变量
```c
// 直接声明一个struct的变量
struct DefineStructVar {
    char StructName[35];
} DefineVar;

// 可直接使用
strcpy(DefineVar.StructName, "DefineStructVar");
```

- 创建一个结构体定义
```c
// 声明一个PersonDemo的结构体
struct PersonDemo {
    char StructName[35];
    int Age;
    char Sex[5];
};

// 需要先声明一个变量才可以使用
struct PersonDemo Person;

// 给结构体变量初始化
Person.Age = 18;
strcpy(Person.StructName, "PersonDemo");
```

