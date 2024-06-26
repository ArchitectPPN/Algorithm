<?php

namespace Select;
use Select\SocketParams;

class SocketSelectServer
{
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
     *
     * @return void
     */
    public function boot(): void
    {
        // 设置脚本运行时间不限制
        set_time_limit(0);
        // 设置缓冲区内容立即输出
        ob_implicit_flush();

        echo "start" . PHP_EOL;
        // https://www.php.net/manual/zh/function.socket-create.php
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($sock === false) {
            echo "Failed to create socket {$this->params->getAddr()}:{$this->params->getPort()}" . PHP_EOL;
            die;
        }

        // https://www.php.net/manual/zh/function.socket-set-option.php
        socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);

        // 检查是是否将套接字绑定到地址和端口上
        // https://www.php.net/manual/zh/function.socket-bind.php
        if (!socket_bind($sock, $this->params->getAddr(), $this->params->getPort())) {
            echo "Could not bind to address {$this->params->getAddr()}:{$this->params->getPort()}" . PHP_EOL;
            echo "Error Reason: " . socket_strerror(socket_last_error($sock));
            $this->closeSocket($sock);
            exit;
        }

        // 对socket进行监听
        // https://www.php.net/manual/zh/function.socket-listen.php
        if (!socket_listen($sock, $this->params->getBacklog())) {
            echo "Could not set up socket listener" . PHP_EOL;
            echo "Error Reason: " . socket_strerror(socket_last_error($sock));
            $this->closeSocket($sock);
            exit;
        }

        echo "Server started on{$this->params->getAddr()}:{$this->params->getPort()}" . PHP_EOL;

        // 要监听的客户端
        $clients[] = $sock;

        while (1) {
            $read = $clients;
            $write = $except = null;

            // https://www.php.net/manual/zh/function.socket-select.php
            $numChangedSockets = socket_select($read, $write, $except, 0, 10);
            if ($numChangedSockets === false) {
                echo "Socket select failed" . PHP_EOL;
                break;
            }

            if ($numChangedSockets > 0) {
                if (in_array($sock, $read)) {
                    $newClient = socket_accept($sock);
                    if ($newClient) {
                        $clients[] = $newClient;
                        echo "New client connected" . PHP_EOL;
                    }
                    $key = array_search($sock, $read);
                    unset($read[$key]);
                }
            }

            foreach ($read as $clientSock) {
                $data = @socket_read($clientSock, 1024, PHP_NORMAL_READ);
                // 数据获取失败时, 关闭连接
                if ($data === false) {
                    $key = array_search($clientSock, $clients);
                    unset($clients[$key]);
                    socket_close($clientSock);
                    echo "Client disconnected\n";
                    continue;
                }

                $data = trim($data);

                if (!empty($data)) {
                    echo "Received message: $data" . PHP_EOL;
                    foreach ($clients as $sendSock) {
                        // 跳过监听套接字和当前客户端
                        if ($sendSock == $sock || $sendSock == $clientSock) {
                            continue;
                        }
                        socket_write($sendSock, $data . PHP_EOL);
                    }
                }
            }
        }

        // 关闭socket
        $this->closeSocket($sock);
    }

    public function closeSocket($sock): void
    {
        // 关闭socket
        socket_close($sock);
    }
}