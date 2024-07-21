### 文件描述符
文件描述符（File Descriptor，简称FD）是计算机操作系统中用于标识和访问文件的整数标识符。在大多数类Unix系统（如Linux、macOS、BSD变种）中，文件描述符是一个非负整数，用来作为文件的句柄，允许程序进行文件的读写操作。
文件描述符是一个抽象层次的概念，它将操作系统底层的 I/O 操作与用户态程序的文件操作接口分离开，使得程序可以更方便地进行各种文件和 I/O 操作。
- 文件描述符的作用：
  - 文件描述符是操作系统内核用来跟踪已经打开的文件的索引。
  - 每次程序打开一个文件，内核都会返回一个唯一的文件描述符给程序，用于后续对该文件的操作。
  - 文件描述符可以用于读取、写入、关闭文件，以及获取文件状态等操作。
  - 文件描述符是一个非负整数，各个文件描述符在进程内部是唯一的。每个打开的文件或 I/O 资源都对应一个文件描述符。
  
- 文件描述符的形式：
  - 文件描述符是一个非负整数，通常从0开始递增。
  - 在 UNIX 和 UNIX-like 系统中，进程启动时会默认打开三个文件描述符:
    - 标准输入（Standard Input）：文件描述符 `0`
    - 标准输出（Standard Output）：文件描述符 `1`
    - 标准错误（Standard Error）：文件描述符 `2`
  - 这三个描述符是固定的，所以进程有新的连接时，描述符一般从3开始。  

- 文件描述符的生命周期：
  - 当一个进程打开一个文件时，内核会给这个进程分配一个文件描述符。
  - 进程可以通过关闭文件描述符来释放与之关联的资源。
  - 当进程终止时，所有打开的文件描述符会被自动关闭。
  
- 文件描述符的使用场景：
  - 文件描述符广泛用于低级别的程序设计，尤其是在需要进行I/O操作的场景下。
  - 它们也用于实现高级功能，如管道（pipes）、重定向和套接字编程。
  
- 文件描述符的传递：
  - 文件描述符可以在进程间通过管道、命名管道（FIFO）或Unix域套接字等方式传递。
  - 在多线程环境中，同一进程内的不同线程共享文件描述符。
  
- 文件描述符的限制：
  - 每个进程能拥有的最大文件描述符数量受系统限制，可以通过`ulimit -n`命令查看和设置。
  
文件描述符是操作系统提供给应用程序的一种抽象机制，使得文件和其他I/O资源能够被高效地管理和访问。

### 文件描述符不同的进程会发生重复吗?
每个进程的文件描述符在数值上可能相同，但它们代表的是独立的文件描述符表项，这些表项属于各自的进程地址空间。也就是说，虽然两个不同的进程可能都使用数字3作为某个文件的文件描述符，但这并不意味着这两个3代表的是同一个文件描述符表项或者指向同一个实际的文件。
在操作系统中，每个进程都有自己的文件描述符表，这个表存储了打开文件的状态信息，包括文件的偏移量、打开模式、文件对象引用等。当一个进程打开一个文件时，内核会在这个进程中分配一个新的文件描述符，并将相关的文件对象信息添加到该进程的文件描述符表中。

因此，尽管多个进程可以同时打开同一个文件，但它们各自使用的文件描述符是独立的。这意味着：
- 不同进程中的相同数值的文件描述符不表示同一个文件描述符表项。
- 即使两个文件描述符的数值相同，只要它们属于不同的进程，它们就分别指向各自进程的文件描述符表中的不同条目。
- 每个进程可以独立地读写文件，控制文件位置指针，而不会影响其他进程对同一文件的操作。

- 然而，值得注意的是，不同进程的文件描述符可以指向同一个文件，这是因为它们都引用了内核中同一个文件对象（或称为inode）。这使得多个进程能够并发地访问同一个文件，只要它们正确地管理对文件的访问即可

### fork进程会共享文件描述符吗?
是的，当一个进程调用fork()函数创建一个子进程时，子进程会继承父进程的几乎所有资源，包括文件描述符。这意味着在fork()之后，子进程和父进程会共享所有打开的文件描述符，这些描述符指向相同的文件对象。
具体来说，当fork()被调用时：

- 子进程获得与父进程几乎完全相同的内存映像，包括所有的变量值、环境变量和打开的文件描述符。
- 所有打开的文件描述符在子进程中都有相同的值，并且指向与父进程中相同的文件对象。
- 文件描述符的当前偏移量也会被复制，所以如果父进程在fork()前对文件进行了读写，子进程看到的文件位置将与父进程相同。

由于文件描述符的共享，任何一方对文件的读写操作都会影响到另一方。例如，如果父进程和子进程都打开了同一个文件用于写入，那么它们的写入操作可能会相互覆盖。

然而，尽管文件描述符被共享，但文件描述符表和文件状态标志是独立的。这意味着父进程和子进程可以独立地关闭相同的文件描述符，而不会影响对方。此外，如果父进程或子进程修改了文件描述符的属性（如设置O_APPEND标志），这种改变只会影响做出修改的那个进程，不会影响另一个进程。

为了防止文件描述符被继承，可以在`fork()`之前使用`fcntl()`函数或`close-on-exec`标志（FD_CLOEXEC）来标记那些不应该被子进程继承的文件描述符。这样，即使在`fork()`之后，子进程也不会继承这些被标记的文件描述符。

### 父进程有个描述符5, fork子进程后, 子进程继承描述符5,但是在子进程中文件描述符还是5吗?
是的，当你在父进程中有一个文件描述符，比如描述符5，然后调用fork()创建子进程，子进程确实会继承包括描述符5在内的所有打开的文件描述符。这意味着在子进程中，这个继承的文件描述符依然会被标示为5。

在fork()之后，子进程的文件描述符表会包含与父进程完全相同的一组文件描述符，每个描述符的数值和它在父进程中的数值相同，而且它们指向内核中相同的文件对象。因此，如果父进程中的描述符5指向一个特定的文件，那么子进程中的描述符5也会指向同一个文件。

这种行为对于进程间的文件共享非常有用，因为它允许父进程和子进程同时访问相同的文件，这对于并行处理任务、日志记录或其他需要共享数据的情况特别重要。然而，正如前面提到的，尽管描述符数值相同，但它们在各自的进程上下文中是独立的，这意味着父进程和子进程可以独立地关闭这些描述符，而不会影响到对方

### 如果子进程新建一个连接,出现文件描述符6,父进程会看到该描述符吗?
不会，父进程不会看到子进程创建的新文件描述符。当子进程通过fork()从父进程派生出来后，子进程和父进程拥有独立的进程地址空间和独立的文件描述符表。这意味着子进程创建的任何新文件描述符，如描述符6，只会存在于子进程的文件描述符表中，而不会出现在父进程的文件描述符表中。

父进程和子进程之间的文件描述符共享仅限于fork()时刻存在的文件描述符。一旦子进程创建了新的文件描述符，这些描述符对父进程是不可见的，反之亦然。因此，父进程无法直接访问或操作子进程中创建的任何新文件描述符，除非通过进程间通信（IPC）机制（如管道、套接字、共享内存等）间接地进行交互。

这种隔离性是操作系统设计的一个关键特性，它确保了进程间的独立性和安全性，防止了一个进程对另一个进程资源的不当访问。