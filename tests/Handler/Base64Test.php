<?php

namespace Tourze\Workerman\BlockProtocol\Tests\Handler;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\BlockProtocol\Handler\Base64;
use Tourze\Workerman\BlockProtocol\Tests\MockConnection;

class Base64Test extends TestCase
{
    private MockConnection $connection;

    protected function setUp(): void
    {
        $this->connection = new MockConnection();
    }

    public function testInput(): void
    {
        $handler = new Base64($this->connection);

        // input方法应该直接返回FLAG_CONTINUE
        $result = $handler->input('some data');
        $this->assertEquals(-1, $result);
    }

    public function testStandardBase64EncodeAndDecode(): void
    {
        $handler = new Base64($this->connection);

        // 原始数据
        $originalData = 'Hello, World! 你好，世界！';

        // 编码
        $encoded = $handler->encode($originalData);

        // 验证编码结果与PHP内置base64_encode一致
        $expectedEncoded = base64_encode($originalData);
        $this->assertEquals($expectedEncoded, $encoded);

        // 解码
        $decoded = $handler->decode($encoded);

        // 验证解码后的数据与原始数据一致
        $this->assertEquals($originalData, $decoded);
    }

    public function testUrlSafeBase64(): void
    {
        $handler = new Base64($this->connection, false, true);

        // 包含需要URL安全处理的特殊字符
        $originalData = 'Hello+World/特殊字符?';

        // 编码为URL安全的base64
        $encoded = $handler->encode($originalData);

        // 验证不含+和/字符，也没有末尾的=填充
        $this->assertStringNotContainsString('+', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);

        // 解码
        $decoded = $handler->decode($encoded);

        // 验证解码后的数据与原始数据一致
        $this->assertEquals($originalData, $decoded);
    }

    public function testStrictMode(): void
    {
        // 创建非严格模式的处理器
        $handlerNonStrict = new Base64($this->connection, false);

        // 创建严格模式的处理器
        $handlerStrict = new Base64($this->connection, true);

        // 有效的base64数据
        $validBase64 = base64_encode('valid data');

        // 无效的base64数据（含有非法字符）
        $invalidBase64 = 'AB*CD';

        // 使用input方法设置初始状态，避免直接使用decode
        $result1 = $handlerNonStrict->input($validBase64);
        $result2 = $handlerStrict->input($validBase64);
        // 确保input方法被正确调用
        $this->assertNotNull($result1);
        $this->assertNotNull($result2);

        // 非严格模式下，应该可以解码非标准Base64
        $decodedNonStrict = $handlerNonStrict->decode($invalidBase64);

        // 检查非严格模式是否返回了某些结果（可能是乱码但不会抛出错误）
        $this->assertNotEquals($invalidBase64, $decodedNonStrict);

        // 使用有效数据测试
        $decoded1 = $handlerNonStrict->decode($validBase64);
        $decoded2 = $handlerStrict->decode($validBase64);

        // 两种模式下解码有效数据结果一致
        $this->assertEquals($decoded1, $decoded2);
    }

    public function testEmptyData(): void
    {
        $handler = new Base64($this->connection);

        // 空数据编码
        $this->assertEquals('', $handler->encode(''));

        // 空数据解码
        $this->assertEquals('', $handler->decode(''));
    }

    public function testBinaryData(): void
    {
        $handler = new Base64($this->connection);

        // 二进制数据（包含NULL字节和非打印字符）
        $binaryData = "\x00\x01\x02\x03\xFF\xFE\xFD\xFC";

        // 编码
        $encoded = $handler->encode($binaryData);

        // 解码
        $decoded = $handler->decode($encoded);

        // 验证解码后的数据与原始数据一致
        $this->assertEquals($binaryData, $decoded);
    }

    public function testUrlSafeAndStandardInteroperability(): void
    {
        // 标准Base64处理器
        $standardHandler = new Base64($this->connection);

        // URL安全Base64处理器
        $urlSafeHandler = new Base64($this->connection, false, true);

        // 原始数据（包含会导致+和/字符的数据）
        $originalData = "\x3F\xBF\xFF\x3E";

        // 使用标准base64编码
        $standardEncoded = $standardHandler->encode($originalData);

        // 验证标准编码包含+或/
        $this->assertTrue(
            strpos($standardEncoded, '+') !== false ||
            strpos($standardEncoded, '/') !== false
        );

        // 使用URL安全base64编码
        $urlSafeEncoded = $urlSafeHandler->encode($originalData);

        // 转换标准base64为URL安全的形式
        $convertedStandard = str_replace(['+', '/'], ['-', '_'], rtrim($standardEncoded, '='));

        // 验证转换后的标准编码与URL安全编码相同
        $this->assertEquals($convertedStandard, $urlSafeEncoded);

        // 验证URL安全base64可以被标准base64解码器正确解码（需先转换）
        $convertedUrlSafe = str_replace(['-', '_'], ['+', '/'], $urlSafeEncoded);
        // 添加适当的填充
        $padding = strlen($convertedUrlSafe) % 4;
        if ($padding !== 0) {
            $convertedUrlSafe .= str_repeat('=', 4 - $padding);
        }

        $this->assertEquals($originalData, $standardHandler->decode($convertedUrlSafe));
    }
}
