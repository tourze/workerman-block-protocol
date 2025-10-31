<?php

namespace Tourze\Workerman\BlockProtocol\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\BlockProtocol\Exception\InvalidProtocolArgumentException;

/**
 * @internal
 */
#[CoversClass(InvalidProtocolArgumentException::class)]
final class InvalidProtocolArgumentExceptionTest extends AbstractExceptionTestCase
{
    public function testIsInstanceOfInvalidArgumentException(): void
    {
        $exception = new InvalidProtocolArgumentException('Test message');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(InvalidProtocolArgumentException::class);
        $this->expectExceptionMessage('Protocol argument error');

        throw new InvalidProtocolArgumentException('Protocol argument error');
    }
}
