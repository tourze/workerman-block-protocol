<?php

namespace Tourze\Workerman\BlockProtocol\Tests\Handler;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\BlockProtocol\Exception\InvalidProtocolArgumentException;
use Tourze\Workerman\BlockProtocol\Handler\Compression;
use Tourze\Workerman\BlockProtocol\Tests\MockConnection;

class CompressionTest extends TestCase
{
    private MockConnection $connection;

    protected function setUp(): void
    {
        $this->connection = new MockConnection();
    }

    public function testConstructWithInvalidAlgorithm(): void
    {
        $this->expectException(InvalidProtocolArgumentException::class);
        $this->expectExceptionMessage('不支持的压缩算法: invalid');

        new Compression($this->connection, 'invalid');
    }

    public function testConstructWithInvalidLevel(): void
    {
        $this->expectException(InvalidProtocolArgumentException::class);
        $this->expectExceptionMessage('压缩级别必须在1-9之间');

        new Compression($this->connection, Compression::ALGORITHM_GZIP, 10);
    }

    public function testInput(): void
    {
        $handler = new Compression($this->connection);

        // input方法应该直接返回FLAG_CONTINUE
        $result = $handler->input('some data');
        $this->assertEquals(-1, $result);
    }

    public function testEncodeDecodeWithGzip(): void
    {
        $handler = new Compression($this->connection, Compression::ALGORITHM_GZIP);

        // 原始数据
        $originalData = str_repeat('Hello World! ', 100);

        // 压缩数据
        $compressed = $handler->encode($originalData);

        // 验证压缩后的数据长度应该小于原始数据
        $this->assertLessThan(strlen($originalData), strlen($compressed));

        // 解压数据
        $decompressed = $handler->decode($compressed);

        // 验证解压后的数据应该与原始数据相同
        $this->assertEquals($originalData, $decompressed);
    }

    public function testEncodeDecodeWithDeflate(): void
    {
        $handler = new Compression($this->connection, Compression::ALGORITHM_DEFLATE);

        // 原始数据
        $originalData = str_repeat('Hello World! ', 100);

        // 压缩数据
        $compressed = $handler->encode($originalData);

        // 验证压缩后的数据长度应该小于原始数据
        $this->assertLessThan(strlen($originalData), strlen($compressed));

        // 解压数据
        $decompressed = $handler->decode($compressed);

        // 验证解压后的数据应该与原始数据相同
        $this->assertEquals($originalData, $decompressed);
    }

    public function testEncodeDecodeWithZlib(): void
    {
        $handler = new Compression($this->connection, Compression::ALGORITHM_ZLIB);

        // 原始数据
        $originalData = str_repeat('Hello World! ', 100);

        // 压缩数据
        $compressed = $handler->encode($originalData);

        // 验证压缩后的数据长度应该小于原始数据
        $this->assertLessThan(strlen($originalData), strlen($compressed));

        // 解压数据
        $decompressed = $handler->decode($compressed);

        // 验证解压后的数据应该与原始数据相同
        $this->assertEquals($originalData, $decompressed);
    }

    public function testDifferentCompressionLevels(): void
    {
        // 创建不同压缩级别的处理器
        $handlerLow = new Compression($this->connection, Compression::ALGORITHM_GZIP, 1);
        $handlerHigh = new Compression($this->connection, Compression::ALGORITHM_GZIP, 9);

        // 原始数据（较大数据以体现压缩差异）
        $originalData = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 100);

        // 低压缩级别压缩
        $compressedLow = $handlerLow->encode($originalData);

        // 高压缩级别压缩
        $compressedHigh = $handlerHigh->encode($originalData);

        // 高压缩级别应该产生更小的数据
        $this->assertLessThanOrEqual(strlen($compressedLow), strlen($compressedHigh));

        // 验证两种压缩级别解压后的数据都与原始数据相同
        $this->assertEquals($originalData, $handlerLow->decode($compressedLow));
        $this->assertEquals($originalData, $handlerHigh->decode($compressedHigh));
    }

    public function testEmptyData(): void
    {
        $handler = new Compression($this->connection);

        // 空数据编码
        $this->assertEquals('', $handler->encode(''));

        // 空数据解码
        $this->assertEquals('', $handler->decode(''));
    }

    public function testInvalidCompressedData(): void
    {
        $handler = new Compression($this->connection, Compression::ALGORITHM_GZIP);

        // 无效的压缩数据
        $invalidData = 'This is not compressed data';

        // 解压无效数据应该返回原数据
        $this->assertEquals($invalidData, $handler->decode($invalidData));
    }
}
