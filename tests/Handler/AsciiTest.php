<?php

namespace Tourze\Workerman\BlockProtocol\Tests\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\BlockProtocol\Handler\Ascii;
use Tourze\Workerman\BlockProtocol\Tests\MockConnection;

/**
 * @internal
 */
#[CoversClass(Ascii::class)]
final class AsciiTest extends TestCase
{
    private MockConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = new MockConnection();
    }

    public function testInputWithValidValue(): void
    {
        // 允许值为1和2的ASCII处理器
        $handler = new Ascii($this->connection, [1, 2]);

        // 输入ASCII码为1的数据
        $result = $handler->input("\x01");

        // 应返回1，表示消费了1个字节
        $this->assertEquals(1, $result);

        // 检查处理器的值是否正确设置
        $this->assertEquals(1, $handler->getValue());
    }

    public function testInputWithInvalidValue(): void
    {
        // 允许值为1和2的ASCII处理器
        $handler = new Ascii($this->connection, [1, 2]);

        // 输入ASCII码为3的数据（不在允许列表中）
        $result = $handler->input("\x03");

        // 应返回0，表示连接已关闭
        $this->assertEquals(0, $result);

        // 连接应该被关闭
        $this->assertTrue($this->connection->isClosed);
    }

    public function testInputWithEmptyAllowValues(): void
    {
        // 无限制的ASCII处理器
        $handler = new Ascii($this->connection, []);

        // 输入任意ASCII码
        $result = $handler->input("\x7F");

        // 应返回1，表示消费了1个字节
        $this->assertEquals(1, $result);

        // 检查处理器的值是否正确设置
        $this->assertEquals(127, $handler->getValue());
    }

    public function testInputAfterProcessed(): void
    {
        $handler = new Ascii($this->connection, []);

        // 第一次处理
        $handler->input("\x01");

        // 第二次处理（已经处理过）
        $result = $handler->input("\x02");

        // 应返回-1，表示已处理过
        $this->assertEquals(-1, $result);

        // 值不应该改变
        $this->assertEquals(1, $handler->getValue());
    }

    public function testDecode(): void
    {
        $handler = new Ascii($this->connection, []);
        $handler->input("\x01rest of data");

        // 第一次解码，应该去掉第一个字节
        $result = $handler->decode("\x01rest of data");
        $this->assertEquals('rest of data', $result);

        // 第二次解码，不应该再处理
        $result = $handler->decode('more data');
        $this->assertEquals('more data', $result);
    }

    public function testEncode(): void
    {
        $handler = new Ascii($this->connection, []);

        // encode不做任何处理
        $data = 'test data';
        $result = $handler->encode($data);
        $this->assertEquals($data, $result);
    }
}
