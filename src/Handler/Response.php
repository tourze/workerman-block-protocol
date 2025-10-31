<?php

namespace Tourze\Workerman\BlockProtocol\Handler;

use Workerman\Connection\ConnectionInterface;

/**
 * 响应处理器
 * 向客户端发送固定响应数据
 */
class Response extends Part
{
    /**
     * 是否已发送数据
     */
    private bool $sent = false;

    /**
     * @param ConnectionInterface $connection 连接对象
     * @param string              $data       要发送的数据
     * @param bool                $autoSend   是否自动发送数据
     */
    public function __construct(
        ConnectionInterface $connection,
        private readonly string $data,
        private readonly bool $autoSend = true,
    ) {
        parent::__construct($connection);
    }

    public function input(string $buffer): int
    {
        // 如果配置了自动发送且尚未发送
        if ($this->autoSend && !$this->sent) {
            $this->sent = true;
            $this->connection->send($this->data, true);
        }

        return self::FLAG_CONTINUE;
    }

    /**
     * 主动发送数据
     */
    public function send(): void
    {
        if (!$this->sent) {
            $this->sent = true;
            $this->connection->send($this->data, true);
        }
    }

    public function decode(string $buffer): string
    {
        return $buffer;
    }

    public function encode(string $buffer): string
    {
        return $buffer;
    }
}
