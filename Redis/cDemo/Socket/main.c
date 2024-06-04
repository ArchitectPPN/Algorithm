#include <stdio.h>

int main() {

    int sock_fd,conn_fd; //监听套接字和已连接套接字的变量
    sock_fd = socket(); //创建套接字
    bind(sock_fd);   //绑定套接字
    listen(sock_fd); //在套接字上进行监听，将套接字转为监听套接字

    fd_set rset;  //被监听的描述符集合，关注描述符上的读事件

    int max_fd = sock_fd;

    //初始化rset数组，使用FD_ZERO宏设置每个元素为0
    FD_ZERO(&rset);
    //使用FD_SET宏设置rset数组中位置为sock_fd的文件描述符为1，表示需要监听该文件描述符
    FD_SET(sock_fd,&rset);

//设置超时时间
    struct timeval timeout;
    timeout.tv_sec = 3;
    timeout.tv_usec = 0;

    while(1) {
        //调用select函数，检测rset数组保存的文件描述符是否已有读事件就绪，返回就绪的文件描述符个数
        n = select(max_fd+1, &rset, NULL, NULL, &timeout);

        //调用FD_ISSET宏，在rset数组中检测sock_fd对应的文件描述符是否就绪
        if (FD_ISSET(sock_fd, &rset)) {
            //如果sock_fd已经就绪，表明已有客户端连接；调用accept函数建立连接
            conn_fd = accept();
            //设置rset数组中位置为conn_fd的文件描述符为1，表示需要监听该文件描述符
            FD_SET(conn_fd, &rset);
        }

        //依次检查已连接套接字的文件描述符
        for (i = 0; i < max_fd; i++) {
            //调用FD_ISSET宏，在rset数组中检测文件描述符是否就绪
            if (FD_ISSET(i, &rset)) {
                //有数据可读，进行读数据处理
            }
        }
    }
}
