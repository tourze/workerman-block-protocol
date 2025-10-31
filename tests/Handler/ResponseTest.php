<?php

namespace Tourze\Workerman\BlockProtocol\Tests\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\BlockProtocol\Handler\Response;
use Tourze\Workerman\BlockProtocol\Tests\MockConnection;

/**
 * @internal
 */
#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    private MockConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = new MockConnection();
    }

    public function testInputWithAutoSend(): void
    {
        // 创建自动发送响应的处理器
        $responseData = 'Hello Response';
        $handler = new Response($this->connection, $responseData);

        // 调用input方法
        $result = $handler->input('any data');

        // 应返回FLAG_CONTINUE
        $this->assertEquals(-1, $result);

        // 验证响应已发送到连接
        $this->assertEquals($responseData, $this->connection->getLastSendData());
    }

    public function testInputWithoutAutoSend(): void
    {
        // 创建不自动发送响应的处理器
        $responseData = 'Hello Response';
        $handler = new Response($this->connection, $responseData, false);

        // 确保连接的lastSentData为空
        $this->connection->clearSendBuffer();

        // 调用input方法
        $handler->input('any data');

        // 验证响应未发送
        $this->assertEquals('', $this->connection->getLastSendData());

        // 主动调用send方法
        $handler->send();

        // 验证响应已发送
        $this->assertEquals($responseData, $this->connection->getLastSendData());
    }

    public function testSendOnlyOnce(): void
    {
        // 创建响应处理器
        $responseData = 'Hello Response';
        $handler = new Response($this->connection, $responseData, false);

        // 第一次发送
        $handler->send();
        $this->assertEquals($responseData, $this->connection->getLastSendData());

        // 清空发送缓冲区
        $this->connection->clearSendBuffer();

        // 第二次发送（不应再次发送）
        $handler->send();
        $this->assertEquals('', $this->connection->getLastSendData());
    }

    public function testDecode(): void
    {
        $handler = new Response($this->connection, 'Response Data');

        // decode方法不改变数据
        $data = 'Test Data';
        $result = $handler->decode($data);

        // 应原样返回数据
        $this->assertEquals($data, $result);
    }

    public function testEncode(): void
    {
        $handler = new Response($this->connection, 'Response Data');

        // encode方法不改变数据
        $data = 'Test Data';
        $result = $handler->encode($data);

        // 应原样返回数据
        $this->assertEquals($data, $result);
    }

    public function testAutoSendTriggerOnlyOnce(): void
    {
        // 创建自动发送响应的处理器
        $responseData = 'Hello Response';
        $handler = new Response($this->connection, $responseData);

        // 第一次调用input
        $handler->input('first call');
        $this->assertEquals($responseData, $this->connection->getLastSendData());

        // 清空发送缓冲区
        $this->connection->clearSendBuffer();

        // 第二次调用input（不应再次发送）
        $handler->input('second call');
        $this->assertEquals('', $this->connection->getLastSendData());
    }
}
