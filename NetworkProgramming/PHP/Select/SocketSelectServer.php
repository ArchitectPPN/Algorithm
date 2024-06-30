<?php

namespace Select;

use Select\SocketParams;
use Socket;

class SocketSelectServer
{
    /**
     * @var array 要监听的客户端
     */
    private array $clients = [];

    /** @var false|Socket 服务器端socket */
    private false|Socket $serverSock;

    /**
     * @var SocketParams
     */
    private SocketParams $params;

    public function __construct(SocketParams $params)
    {
        $this->params = $params;
    }

    /**
     * 启动服务器
     * @return void
     */
    public function boot(): void
    {
        // 设置脚本运行时间不限制
        $this->setTimeLimit();

        // 设置缓冲区内容立即输出
        $this->obImplicitFlush();

        echo "start" . PHP_EOL;
        // https://www.php.net/manual/zh/function.socket-create.php
        $this->serverSock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->serverSock === false) {
            echo "Failed to create socket {$this->params->getAddr()}:{$this->params->getPort()}" . PHP_EOL;
            die;
        }

        // https://www.php.net/manual/zh/function.socket-set-option.php
        socket_set_option($this->serverSock, SOL_SOCKET, SO_REUSEADDR, 1);

        // 检查是是否将套接字绑定到地址和端口上
        // https://www.php.net/manual/zh/function.socket-bind.php
        if (!socket_bind($this->serverSock, $this->params->getAddr(), $this->params->getPort())) {
            echo "Could not bind to address {$this->params->getAddr()}:{$this->params->getPort()}" . PHP_EOL;
            echo "Error Reason: " . socket_strerror(socket_last_error($this->serverSock));
            $this->closeSocket($this->serverSock);
            exit;
        }

        // 对socket进行监听
        // https://www.php.net/manual/zh/function.socket-listen.php
        if (!socket_listen($this->serverSock, $this->params->getBacklog())) {
            echo "Could not set up socket listener" . PHP_EOL;
            echo "Error Reason: " . socket_strerror(socket_last_error($this->serverSock));
            $this->closeSocket($this->serverSock);
            exit;
        }

        echo "Server started on{$this->params->getAddr()}:{$this->params->getPort()}" . PHP_EOL;

        // 要监听的客户端
        $this->clients[] = $this->serverSock;

        while (1) {
            // 每次循环都需要初始化当前已连接的套接字和服务器套接字
            // 在使用socket_select时会修改read，只留下已就绪的链接
            $read = $this->clients;
            $write = $except = null;
            // 如果你设置超时时间为 10 秒和 10 微秒 ($tv_sec = 10 和 $tv_usec = 10)，这意味着函数将等待 10 秒加 10 微秒。
            // 总共等待时间会是这两个时间的总和。
            // socket_select 有就绪的描述符之后，会立刻返回
            // socket_select 返回已就绪描述符的数量,是一个int值
            // https://www.php.net/manual/zh/function.socket-select.php
            $numChangedSockets = socket_select($read, $write, $except, 20, 0);
            if ($numChangedSockets === false) {
                echo "Socket select failed" . PHP_EOL;
                break;
            }

            if ($numChangedSockets > 0 && in_array($this->serverSock, $read)) {
                $this->connectNewClient($read);
            }

            foreach ($read as $clientSock) {
                # https://www.php.net/manual/zh/function.socket-read.php
                $data = @socket_read($clientSock, 1024, PHP_NORMAL_READ);
                // 数据获取失败时, 关闭连接
                if ($data === false) {
                    $key = array_search($clientSock, $this->clients);
                    unset($this->clients[$key]);
                    socket_close($clientSock);
                    echo "Client disconnected\n";
                    continue;
                }

                // 将收到的消息发送给其他客户端
                $this->sendMessage(trim($data), $clientSock);
            }
        }

        // 关闭socket
        $this->closeSocket($this->serverSock);
    }

    /**
     * @param array $read
     * @return void
     */
    private function connectNewClient(array $read): void
    {
        // socket_accept 获取连接， 但是每次只能建立一个新的连接
        // 循环处理所有新的连接请求 while (($newClient = @socket_accept($sock)) !== false)
        # https://www.php.net/manual/zh/function.socket-accept.php
        $newClient = socket_accept($this->serverSock);
        if ($newClient) {
            $this->clients[] = $newClient;
            echo "New client connected" . PHP_EOL;
        }

        // 从中移除服务器端socket
        $key = array_search($this->serverSock, $read);
        unset($read[$key]);
    }

    /**
     * 关闭sock
     * @param Socket $sock
     * @return void
     */
    private function closeSocket(Socket $sock): void
    {
        // 关闭socket
        socket_close($sock);
    }

    /**
     * @param string $data
     * @param Socket $clientSock
     * @return void
     */
    private function sendMessage(string $data, Socket $clientSock): void
    {
        if (!empty($data)) {
            echo "Received message: $data" . PHP_EOL;
            foreach ($this->clients as $sendSock) {
                // 跳过监听套接字和当前客户端
                if ($sendSock == $this->serverSock || $sendSock == $clientSock) {
                    continue;
                }
                # 向套接字写数据
                # https://www.php.net/manual/zh/function.socket-write.php
                socket_write($sendSock, $data . PHP_EOL);
            }
        }
    }

    /**
     * @param int $seconds
     * @return void
     */
    private function setTimeLimit(int $seconds = 0): void
    {
        // 设置脚本运行时间不限制
        set_time_limit($seconds);
    }

    /**
     * @param bool $flag
     * @return void
     */
    private function obImplicitFlush(bool $flag = true): void
    {
        if ($flag) {
            ob_implicit_flush();
        }
    }
}