<?php

namespace Tourze\Workerman\BlockProtocol\Tests;

use Workerman\Connection\AsyncTcpConnection;

/**
 * Mock连接类用于测试
 */
class MockConnection extends AsyncTcpConnection
{
    /**
     * 连接是否已关闭
     */
    public bool $isClosed = false;

    /**
     * 上次发送的数据
     */
    public string $lastSentData = '';

    /**
     * 是否禁用关闭功能
     */
    public bool $disableClose = false;

    /**
     * 重写构造函数，为测试提供默认参数
     */
    public function __construct()
    {
        parent::__construct('tcp://127.0.0.1:1234');
    }

    /**
     * 模拟关闭连接
     *
     * @param mixed $data 关闭前发送的数据
     * @param bool $raw 是否发送原始数据
     * @return void
     */
    public function close(mixed $data = null, bool $raw = false): void
    {
        if (!$this->disableClose) {
            $this->isClosed = true;
        }
    }

    /**
     * 模拟发送数据
     *
     * @param mixed $sendBuffer 要发送的数据
     * @param bool $raw 是否发送原始数据
     * @return bool|null
     */
    public function send(mixed $sendBuffer, bool $raw = false): bool|null
    {
        $this->lastSentData = is_string($sendBuffer) ? $sendBuffer : json_encode($sendBuffer);
        return true;
    }

    /**
     * 获取最后发送的数据
     *
     * @return string
     */
    public function getLastSendData(): string
    {
        return $this->lastSentData;
    }

    /**
     * 清除发送缓冲区
     *
     * @return void
     */
    public function clearSendBuffer(): void
    {
        $this->lastSentData = '';
    }

    /**
     * 重置连接状态
     *
     * @return void
     */
    public function reset(): void
    {
        $this->isClosed = false;
        $this->lastSentData = '';
        $this->disableClose = false;
        // 清除可能添加的动态属性
        foreach (get_object_vars($this) as $property => $value) {
            if (!in_array($property, ['isClosed', 'lastSentData', 'disableClose'])) {
                unset($this->$property);
            }
        }
    }
}
