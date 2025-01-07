<?php

namespace Redis\NetworkProgramming\PHP;
class SocketSelectClient
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

    public function handle()
    {
        // 创建TCP套接字
        $clientSocket = stream_socket_client($this->url, $errNo, $errStr);

        if (!$clientSocket) {
            die("Failed to connect {$errStr} ({$errNo})");
        }

        echo "Connected to server" . PHP_EOL;

        // 向服务器发送消息
        $message = "Hello, server!";
        fwrite($clientSocket, $message);

        // 读取服务器的响应
        $response = fread($clientSocket, 1024);
        echo "Server response: {$response}" . PHP_EOL;

        // 关闭套接字
        fclose($clientSocket);
    }
}

$socketClient = new SocketSelectClient();
$socketClient->handle();