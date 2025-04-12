<?php

namespace Tourze\Workerman\BlockProtocol\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\BlockProtocol\BlockProtocol;
use Tourze\Workerman\BlockProtocol\Handler\ASCII;
use Tourze\Workerman\BlockProtocol\Handler\Base64;
use Tourze\Workerman\BlockProtocol\Handler\Compression;
use Tourze\Workerman\BlockProtocol\Handler\Length;

class BlockProtocolTest extends TestCase
{
    private MockConnection $connection;

    protected function setUp(): void
    {
        $this->connection = new MockConnection();

        // 重置BlockProtocol的状态
        BlockProtocol::$handlerCallback = null;
    }

    public function testInitConnection(): void
    {
        // 设置处理器回调
        BlockProtocol::$handlerCallback = function ($connection) {
            return [
                new ASCII($connection, [])
            ];
        };

        // 初始化连接
        BlockProtocol::initConnection($this->connection);

        // 获取处理器的值（应为null，因为还未处理数据）
        $value = BlockProtocol::getPart($this->connection, ASCII::class);
        $this->assertNull($value);

        // 输入数据
        BlockProtocol::input("\x41", $this->connection); // ASCII 'A'

        // 获取处理器的值（应为65，即'A'的ASCII码）
        $value = BlockProtocol::getPart($this->connection, ASCII::class);
        $this->assertEquals(65, $value);
    }

    public function testInputWithMultipleHandlers(): void
    {
        // 设置多个处理器
        BlockProtocol::$handlerCallback = function ($connection) {
            return [
                new ASCII($connection, []),
                new Length($connection)
            ];
        };

        // 初始化连接
        BlockProtocol::initConnection($this->connection);

        // 创建测试数据：ASCII 'A'
        $asciiData = "\x41";

        // 输入ASCII数据
        $result = BlockProtocol::input($asciiData, $this->connection);
        $this->assertGreaterThan(0, $result);

        // 验证ASCII处理器的值
        $this->assertEquals(65, BlockProtocol::getPart($this->connection, ASCII::class));

        // 创建Length测试数据：长度4 + 内容"test"
        $lengthData = pack('N', 4) . "test";

        // 再次输入数据给Length处理器
        $result = BlockProtocol::input($lengthData, $this->connection);
        $this->assertGreaterThan(0, $result);

        // 验证Length处理器的值
        $this->assertEquals("test", BlockProtocol::getPart($this->connection, Length::class));
    }

    public function testDecodeWithMultipleHandlers(): void
    {
        // 设置多个处理器
        BlockProtocol::$handlerCallback = function ($connection) {
            return [
                new ASCII($connection, []),
                new Length($connection)
            ];
        };

        // 初始化连接
        BlockProtocol::initConnection($this->connection);

        // 创建测试数据：ASCII 'A' + 长度4 + 内容"test" + 额外数据
        $data = "\x41" . pack('N', 4) . "test" . "extra";

        // 首先进行input处理
        BlockProtocol::input($data, $this->connection);

        // 然后进行decode处理
        $result = BlockProtocol::decode($data, $this->connection);

        // 检查处理后的结果中包含"extra"
        $this->assertStringContainsString("extra", $result);
    }

    public function testEncodeWithMultipleHandlers(): void
    {
        // 设置处理器：Base64和Compression
        BlockProtocol::$handlerCallback = function ($connection) {
            return [
                new Base64($connection),
                new Compression($connection, Compression::ALGORITHM_GZIP)
            ];
        };

        // 原始数据
        $originalData = "Hello World!";

        // 使用协议编码数据
        $encodedData = BlockProtocol::encode($originalData, $this->connection);

        // 应该先压缩再base64编码
        // 验证结果不等于原始数据
        $this->assertNotEquals($originalData, $encodedData);

        // 验证结果是有效的base64
        $this->assertTrue(base64_decode($encodedData, true) !== false);

        // 使用协议解码数据
        $decodedData = BlockProtocol::decode($encodedData, $this->connection);

        // 解码后应该等于原始数据
        $this->assertEquals($originalData, $decodedData);
    }

    public function testGetPartWithNonexistentType(): void
    {
        // 设置处理器
        BlockProtocol::$handlerCallback = function ($connection) {
            return [
                new ASCII($connection, [])
            ];
        };

        // 初始化连接
        BlockProtocol::initConnection($this->connection);

        // 获取不存在的处理器类型
        $value = BlockProtocol::getPart($this->connection, Length::class);

        // 应返回null
        $this->assertNull($value);
    }

    public function testHandlerCallbackNotSet(): void
    {
        // 不设置处理器回调
        BlockProtocol::$handlerCallback = null;

        // 初始化连接
        BlockProtocol::initConnection($this->connection);

        // 输入数据（应该不会出错）
        $result = BlockProtocol::input("test", $this->connection);

        // 应返回输入数据的长度
        $this->assertEquals(4, $result);
    }
}
