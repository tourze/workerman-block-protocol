<?php

namespace Tourze\Workerman\BlockProtocol\Tests\Handler;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\BlockProtocol\Handler\JSON;
use Tourze\Workerman\BlockProtocol\Tests\MockConnection;

class JSONTest extends TestCase
{
    private MockConnection $connection;

    protected function setUp(): void
    {
        $this->connection = new MockConnection();
    }

    public function testInputWithValidJSON(): void
    {
        // 创建JSON处理器
        $handler = new JSON($this->connection);

        // 创建一个有效的JSON数据
        $jsonData = '{"name":"test","value":123}';

        // 输入数据
        $result = $handler->input($jsonData);

        // 应返回数据的长度
        $this->assertEquals(strlen($jsonData), $result);

        // 检查处理器的值是否正确解析
        $value = $handler->getValue();
        $this->assertIsArray($value);
        $this->assertEquals('test', $value['name']);
        $this->assertEquals(123, $value['value']);
    }

    public function testInputWithInvalidJSON(): void
    {
        // 创建JSON处理器
        $handler = new JSON($this->connection);

        // 创建一个无效的JSON数据
        $jsonData = '{"name":"test","value":123';

        // 输入数据
        $result = $handler->input($jsonData);

        // 应返回0，表示需要更多数据
        $this->assertEquals(0, $result);

        // 检查处理器的值应该为null
        $this->assertNull($handler->getValue());
    }

    public function testInputExceedMaxLength(): void
    {
        // 创建一个最大长度为10的JSON处理器
        $handler = new JSON($this->connection, 10);

        // 创建一个超过最大长度的数据
        $jsonData = '{"name":"test","value":123}';

        // 输入数据
        $result = $handler->input($jsonData);

        // 应返回0，表示连接已关闭
        $this->assertEquals(0, $result);

        // 连接应该被关闭
        $this->assertTrue($this->connection->isClosed);
    }

    public function testInputWithAlias(): void
    {
        // 创建一个设置别名的JSON处理器
        $handler = new JSON($this->connection, 65536, 'jsonData');

        // 输入有效的JSON数据
        $jsonData = '{"name":"test","value":123}';
        $handler->input($jsonData);

        // 检查连接对象上是否设置了指定属性
        $this->assertIsArray($this->connection->jsonData);
        $this->assertEquals('test', $this->connection->jsonData['name']);
    }

    public function testInputWithAssocFalse(): void
    {
        // 创建一个对象模式的JSON处理器
        $handler = new JSON($this->connection, 65536, null, false);

        // 输入有效的JSON数据
        $jsonData = '{"name":"test","value":123}';
        $handler->input($jsonData);

        // 检查解析结果应为对象
        $value = $handler->getValue();
        $this->assertIsObject($value);
        $this->assertEquals('test', $value->name);
    }

    public function testInputAfterProcessed(): void
    {
        $handler = new JSON($this->connection);

        // 第一次处理
        $handler->input('{"key":"value1"}');

        // 第二次处理（已经处理过）
        $result = $handler->input('{"key":"value2"}');

        // 应返回-1，表示已处理过
        $this->assertEquals(-1, $result);

        // 值不应该改变
        $value = $handler->getValue();
        $this->assertEquals('value1', $value['key']);
    }

    public function testDecode(): void
    {
        $handler = new JSON($this->connection);
        $handler->input('{"name":"test"}');

        // 第一次解码，应该消费整个buffer
        $result = $handler->decode('{"name":"test"}');
        $this->assertEquals('', $result);

        // 第二次解码，不应该再处理
        $result = $handler->decode('more data');
        $this->assertEquals('more data', $result);
    }

    public function testEncodeWithArray(): void
    {
        $handler = new JSON($this->connection);

        // 编码数组数据
        $data = ['name' => '测试', 'value' => 123];
        $result = $handler->encode($data);

        // 应该返回正确的JSON字符串（包含中文）
        $this->assertEquals('{"name":"测试","value":123}', $result);
    }

    public function testEncodeWithString(): void
    {
        $handler = new JSON($this->connection);

        // 对普通字符串不做处理
        $data = 'test string';
        $result = $handler->encode($data);
        $this->assertEquals($data, $result);
    }
} 