<?php

namespace Select;

class SocketParams
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

    /**
     * @var int 最大客户端数量
     */
    private int $maxClients;

    private int $backlog;

    public function getAddr(): string
    {
        return $this->addr;
    }

    /**
     * @param string $addr
     * @return $this
     */
    public function setAddr(string $addr): static
    {
        $this->addr = $addr;
        return $this;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return $this
     */
    public function setPort(int $port): static
    {
        $this->port = $port;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getMaxClients(): int
    {
        return $this->maxClients;
    }

    /**
     * @param int $maxClients
     * @return $this
     */
    public function setMaxClients(int $maxClients): static
    {
        $this->maxClients = $maxClients;
        return $this;
    }

    /**
     * @return int
     */
    public function getBacklog(): int
    {
        return $this->backlog;
    }

    /**
     * @param int $backlog
     * @return $this
     */
    public function setBacklog(int $backlog): static
    {
        $this->backlog = $backlog;
        return $this;
    }
}