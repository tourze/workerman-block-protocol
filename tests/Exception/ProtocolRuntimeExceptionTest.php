<?php

namespace Tourze\Workerman\BlockProtocol\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tourze\Workerman\BlockProtocol\Exception\ProtocolRuntimeException;

class ProtocolRuntimeExceptionTest extends TestCase
{
    public function testIsInstanceOfRuntimeException(): void
    {
        $exception = new ProtocolRuntimeException('Test message');
        
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(ProtocolRuntimeException::class);
        $this->expectExceptionMessage('Protocol runtime error');
        
        throw new ProtocolRuntimeException('Protocol runtime error');
    }
}