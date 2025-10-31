<?php

namespace Tourze\Workerman\BlockProtocol\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\BlockProtocol\Exception\ProtocolRuntimeException;

/**
 * @internal
 */
#[CoversClass(ProtocolRuntimeException::class)]
final class ProtocolRuntimeExceptionTest extends AbstractExceptionTestCase
{
    public function testIsInstanceOfRuntimeException(): void
    {
        $exception = new ProtocolRuntimeException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(ProtocolRuntimeException::class);
        $this->expectExceptionMessage('Protocol runtime error');

        throw new ProtocolRuntimeException('Protocol runtime error');
    }
}
