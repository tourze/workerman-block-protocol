<?php

namespace Tourze\Workerman\BlockProtocol\Tests\Unit\Exception;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\BlockProtocol\Exception\InvalidProtocolArgumentException;

class InvalidProtocolArgumentExceptionTest extends TestCase
{
    public function testIsInstanceOfInvalidArgumentException(): void
    {
        $exception = new InvalidProtocolArgumentException('Test message');
        
        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(InvalidProtocolArgumentException::class);
        $this->expectExceptionMessage('Protocol argument error');
        
        throw new InvalidProtocolArgumentException('Protocol argument error');
    }
}