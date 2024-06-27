```c++
    gcc -Wall -o example example.c
```

这里简要解释各部分的含义：
- gcc：GNU Compiler Collection的命令，用于编译C、C++等编程语言。
- -Wall：这是一个编译选项，表示启用所有警告（"all warnings"）。这对于发现代码中的潜在问题非常有帮助。
- -o example：指定输出文件名。这里将生成的可执行文件命名为example。
- example.c：这是你要编译的C源代码文件。

运行上述命令后，如果源代码没有错误，GCC将会生成一个名为example的可执行文件。你可以在终端通过./example命令来运行这个程序。