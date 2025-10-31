<?php

namespace Tourze\Workerman\BlockProtocol\Tests\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\BlockProtocol\Handler\Length;
use Tourze\Workerman\BlockProtocol\Tests\MockConnection;

/**
 * @internal
 */
#[CoversClass(Length::class)]
final class LengthTest extends TestCase
{
    private MockConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = new MockConnection();
    }

    public function testInputWithValidData(): void
    {
        // 创建Length处理器，使用4字节网络字节序整数表示长度
        $handler = new Length($this->connection);

        // 创建测试数据：5字节的消息体，前缀4字节表示长度
        $messageBody = 'hello';
        $lengthPrefix = pack('N', 5); // 打包长度为5的4字节网络字节序整数
        $fullMessage = $lengthPrefix . $messageBody;

        // 输入数据
        $result = $handler->input($fullMessage);

        // 应返回总长度9（4字节长度前缀 + 5字节消息体）
        $this->assertEquals(9, $result);

        // 检查处理器的值是否正确设置为消息体
        $this->assertEquals('hello', $handler->getValue());
    }

    public function testInputPartialHeader(): void
    {
        // 创建Length处理器
        $handler = new Length($this->connection);

        // 输入不足以解析长度字段的数据
        $result = $handler->input("\x00\x00");

        // 应返回0，表示需要更多数据
        $this->assertEquals(0, $result);

        // 值应该仍为null
        $this->assertNull($handler->getValue());
    }

    public function testInputWithInvalidLength(): void
    {
        // 创建Length处理器，最大长度为100
        $handler = new Length($this->connection, 'N', 4, 100);

        // 创建测试数据：长度字段值为200，超过了最大限制
        $lengthPrefix = pack('N', 200);

        // 输入数据
        $result = $handler->input($lengthPrefix . 'some data');

        // 应返回0，表示连接已关闭
        $this->assertEquals(0, $result);

        // 连接应该被关闭
        $this->assertTrue($this->connection->isClosed);
    }

    public function testInputWithZeroLength(): void
    {
        // 创建Length处理器
        $handler = new Length($this->connection);

        // 创建测试数据：长度字段值为0
        $lengthPrefix = pack('N', 0);

        // 输入数据
        $result = $handler->input($lengthPrefix . 'some data');

        // 应返回0，表示连接已关闭（长度不能为0）
        $this->assertEquals(0, $result);

        // 连接应该被关闭
        $this->assertTrue($this->connection->isClosed);
    }

    public function testInputPartialBody(): void
    {
        // 创建Length处理器
        $handler = new Length($this->connection);

        // 创建测试数据：长度字段表示需要10字节数据，但实际只提供5字节
        $lengthPrefix = pack('N', 10);
        $partialBody = '12345';

        // 输入数据
        $result = $handler->input($lengthPrefix . $partialBody);

        // 应返回0，表示需要更多数据
        $this->assertEquals(0, $result);

        // 值应该仍为null
        $this->assertNull($handler->getValue());
    }

    public function testInputWithAlias(): void
    {
        // 创建一个设置别名的Length处理器
        $handler = new Length($this->connection, 'N', 4, 1048576, 'lengthData');

        // 创建测试数据
        $messageBody = 'hello';
        $lengthPrefix = pack('N', 5);
        $fullMessage = $lengthPrefix . $messageBody;

        // 输入数据
        $handler->input($fullMessage);

        // 检查连接对象上是否设置了指定属性
        $this->assertEquals('hello', $this->connection->lengthData);
    }

    public function testInputAfterProcessed(): void
    {
        $handler = new Length($this->connection);

        // 创建测试数据
        $messageBody = 'hello';
        $lengthPrefix = pack('N', 5);
        $fullMessage = $lengthPrefix . $messageBody;

        // 第一次处理
        $handler->input($fullMessage);

        // 第二次处理（已经处理过）
        $result = $handler->input($fullMessage);

        // 应返回-1，表示已处理过
        $this->assertEquals(-1, $result);

        // 值不应该改变
        $this->assertEquals('hello', $handler->getValue());
    }

    public function testDecode(): void
    {
        $handler = new Length($this->connection);

        // 创建测试数据
        $messageBody = 'hello';
        $lengthPrefix = pack('N', 5);
        $fullMessage = $lengthPrefix . $messageBody . 'extra data';

        // 处理数据
        $handler->input($fullMessage);

        // 第一次解码，应该去掉长度前缀和消息体
        $result = $handler->decode($fullMessage);
        $this->assertEquals('extra data', $result);

        // 第二次解码，不应该再处理
        $result = $handler->decode('more data');
        $this->assertEquals('more data', $result);
    }

    public function testEncode(): void
    {
        $handler = new Length($this->connection);

        // 编码消息
        $data = 'hello world';
        $result = $handler->encode($data);

        // 应该生成包含长度前缀的数据
        $expectedLength = pack('N', 11); // 11是"hello world"的长度
        $expected = $expectedLength . 'hello world';
        $this->assertEquals($expected, $result);
    }

    public function testCustomFormatAndHeaderSize(): void
    {
        // 创建使用2字节小端序长度的处理器
        $handler = new Length($this->connection, 'v', 2);

        // 创建测试数据：3字节的消息体，前缀2字节表示长度
        $messageBody = 'abc';
        $lengthPrefix = pack('v', 3); // 打包长度为3的2字节小端序整数
        $fullMessage = $lengthPrefix . $messageBody;

        // 输入数据
        $result = $handler->input($fullMessage);

        // 应返回总长度5（2字节长度前缀 + 3字节消息体）
        $this->assertEquals(5, $result);

        // 检查处理器的值是否正确设置为消息体
        $this->assertEquals('abc', $handler->getValue());
    }
}
