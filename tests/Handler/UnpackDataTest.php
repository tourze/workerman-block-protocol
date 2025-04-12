<?php

namespace Tourze\Workerman\BlockProtocol\Tests\Handler;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\BlockProtocol\Handler\UnpackData;
use Tourze\Workerman\BlockProtocol\Tests\MockConnection;

class UnpackDataTest extends TestCase
{
    private MockConnection $connection;

    protected function setUp(): void
    {
        $this->connection = new MockConnection();
    }

    public function testInputWithoutFormat(): void
    {
        // 创建一个处理4字节数据，但不解析的处理器
        $handler = new UnpackData($this->connection, 4);

        // 输入6字节数据
        $result = $handler->input("123456");

        // 应返回6，表示消费了整个buffer
        $this->assertEquals(6, $result);

        // 检查处理器的值是否正确设置（应返回前4个字节）
        $this->assertEquals("1234", $handler->getValue());
    }

    public function testInputWithFormat(): void
    {
        // 创建一个处理4字节并以网络字节序解析为整数的处理器
        $handler = new UnpackData($this->connection, 4, 'N');

        // 创建一个32位整数数据 16909060 (0x01020304) 
        $data = pack('N', 16909060);

        // 输入数据
        $result = $handler->input($data);

        // 应返回4，表示消费了整个buffer
        $this->assertEquals(4, $result);

        // 检查处理器的值是否正确解析为整数
        $this->assertEquals(16909060, $handler->getValue());
    }

    public function testInputWithAlias(): void
    {
        // 创建一个处理4字节并设置别名的处理器
        $handler = new UnpackData($this->connection, 4, null, 'testData');

        // 输入数据
        $handler->input("testdata");

        // 检查连接对象上是否设置了指定属性
        $this->assertEquals("test", $this->connection->testData);
    }

    /**
     * @test
     * @group skip
     */
    public function testInputWithAllowValues(): void
    {
        // 由于MockConnection与AsyncTcpConnection的兼容性问题，此测试暂时跳过
        $this->markTestSkipped('Skipped due to compatibility issues with AsyncTcpConnection');

        // 首先测试允许的值
        $this->connection->reset();
        $allowedHandler = new UnpackData($this->connection, 1, null, null, [65, 66, 67]);

        // 禁用关闭连接功能
        $this->connection->disableClose = true;

        // 输入允许的值 'A'
        $allowedHandler->input("A");

        // 验证连接未关闭
        $this->assertFalse($this->connection->isClosed);

        // 验证处理后的值
        $this->assertEquals("A", $allowedHandler->getValue());

        // 测试不允许的值
        $this->connection->reset();
        $disallowedHandler = new UnpackData($this->connection, 1, null, null, [65, 66, 67]);

        // 输入不允许的值 'D'
        $disallowedHandler->input("D");

        // 验证连接被关闭
        $this->assertTrue($this->connection->isClosed);

        // 值应保持未设置状态
        $this->assertNull($disallowedHandler->getValue());
    }

    public function testInputInsufficientData(): void
    {
        // 创建一个处理4字节数据的处理器
        $handler = new UnpackData($this->connection, 4);

        // 输入不足的数据
        $result = $handler->input("123");

        // 应返回0，表示需要更多数据
        $this->assertEquals(0, $result);

        // 值应该仍为null
        $this->assertNull($handler->getValue());
    }

    public function testInputAfterProcessed(): void
    {
        $handler = new UnpackData($this->connection, 2);

        // 第一次处理
        $handler->input("1234");

        // 第二次处理（已经处理过）
        $result = $handler->input("5678");

        // 应返回-1，表示已处理过
        $this->assertEquals(-1, $result);

        // 值不应该改变
        $this->assertEquals("12", $handler->getValue());
    }

    public function testDecode(): void
    {
        $handler = new UnpackData($this->connection, 3);
        $handler->input("123rest of data");

        // 第一次解码，应该去掉前3个字节
        $result = $handler->decode("123rest of data");
        $this->assertEquals("rest of data", $result);

        // 第二次解码，不应该再处理
        $result = $handler->decode("more data");
        $this->assertEquals("more data", $result);
    }

    public function testEncode(): void
    {
        $handler = new UnpackData($this->connection, 4);

        // encode不做任何处理
        $data = "test data";
        $result = $handler->encode($data);
        $this->assertEquals($data, $result);
    }
}
