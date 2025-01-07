<?php

namespace Redis\NetworkProgramming\PHP;

use JetBrains\PhpStorm\NoReturn;

class SocketSelectServer
{
    /**
     * @var string 监听的ip地址
     */
    private string $addr;

    /**
     * @var int 监听的端口号
     */
    private int $port;

    /**
     * @var string 请求的地址
     */
    private string $url;

    public function __construct(string $addr = '127.0.0.1', int $port = 8081)
    {
        $this->addr = $addr;
        $this->port = $port;
        $this->url = "tcp://{$this->addr}:{$this->port}";
    }

    /**
     * @return void
     */
    #[NoReturn] public function handle(): void
    {
        $serverSocket = stream_socket_server($this->url, $errNo, $errStr);
        if (!$serverSocket) {
            die("Failed to create socket: {$errStr} ({$errNo})");
        }

        echo "Server started on {$this->url}" . PHP_EOL;

        $clients = [];
        // 初始时只监视服务器套接字
        $read = [$serverSocket];
        while (1) {
            echo "Start Listen: " . date('Y-m-d H:i:s') . PHP_EOL;

            // 复制监视列表
            $changeSockets = $read;
            //
            if (stream_select($changeSockets, $write, $except, null) === false) {
                die("stream_select_failed");
            }

            foreach ($changeSockets as $socket) {
                if ($socket === $serverSocket) {
                    // 有新连接
                    $clientSocket = stream_socket_accept($serverSocket);
                    // 设置为非阻塞模式
                    stream_set_blocking($clientSocket, false);
                    $clients[] = $clientSocket;
                    echo "New client connected" . PHP_EOL;
                } else {
                    // 有数据可读
                    $data = fread($socket, 1024);

                    if ($data === false) {
                        // 读取错误或连接关闭
                        $key = array_search($socket, $clients);
                        unset($clients[$key]);
                        fclose($socket);
                        echo "Client disconnected" . PHP_EOL;
                    } else {
                        // 处理接受到的数据
                        echo "Received data: {$data}" . PHP_EOL;
                    }
                }
            }
        }
    }

}

$select = new SocketSelectServer();
$select->handle();