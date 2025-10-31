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
     * JSON数据属性 - 用于测试别名功能
     */
    public mixed $jsonData = null;

    /**
     * Length数据属性 - 用于测试别名功能
     */
    public mixed $lengthData = null;

    /**
     * 测试数据属性 - 用于测试别名功能
     */
    public mixed $testData = null;

    /**
     * 重写构造函数，为测试提供默认参数
     */
    public function __construct()
    {
        parent::__construct('tcp://127.0.0.1:1234');

        // 初始化 isSafe 属性以避免 Workerman 错误
        $this->isSafe = true;
    }

    /**
     * 模拟关闭连接
     *
     * @param mixed $data 关闭前发送的数据
     * @param bool  $raw  是否发送原始数据
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
     * @param bool  $raw        是否发送原始数据
     */
    public function send(mixed $sendBuffer, bool $raw = false): ?bool
    {
        if (is_string($sendBuffer)) {
            $this->lastSentData = $sendBuffer;
        } else {
            $encoded = json_encode($sendBuffer);
            $this->lastSentData = false !== $encoded ? $encoded : '';
        }

        return true;
    }

    /**
     * 获取最后发送的数据
     */
    public function getLastSendData(): string
    {
        return $this->lastSentData;
    }

    /**
     * 清除发送缓冲区
     */
    public function clearSendBuffer(): void
    {
        $this->lastSentData = '';
    }

    /**
     * 重置连接状态
     */
    public function reset(): void
    {
        $this->isClosed = false;
        $this->lastSentData = '';
        $this->disableClose = false;
        $this->jsonData = null;
        $this->lengthData = null;
        $this->testData = null;
    }
}
