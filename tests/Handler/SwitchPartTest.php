<?php

namespace Tourze\Workerman\BlockProtocol\Tests\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\BlockProtocol\Exception\ProtocolRuntimeException;
use Tourze\Workerman\BlockProtocol\Handler\Part;
use Tourze\Workerman\BlockProtocol\Handler\SwitchPart;
use Tourze\Workerman\BlockProtocol\Tests\MockConnection;

/**
 * @internal
 */
#[CoversClass(SwitchPart::class)]
final class SwitchPartTest extends TestCase
{
    private MockConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = new MockConnection();
    }

    public function testConstruct(): void
    {
        $mockHandler = $this->createMock(Part::class);
        $handlers = ['type1' => $mockHandler];

        $switchPart = new SwitchPart($this->connection, 'dataKey', $handlers);

        $this->assertSame($handlers, $switchPart->getHandlers());
    }

    public function testInputWithValidData(): void
    {
        $mockHandler = $this->createMock(Part::class);
        $mockHandler->expects($this->once())
            ->method('input')
            ->with('test buffer')
            ->willReturn(10)
        ;

        $handlers = ['type1' => $mockHandler];
        $switchPart = new SwitchPart($this->connection, 'dataKey', $handlers);

        /* @phpstan-ignore-next-line */
        $this->connection->dataKey = 'type1';

        $result = $switchPart->input('test buffer');
        $this->assertEquals(10, $result);
    }

    public function testInputWithInvalidData(): void
    {
        $this->expectException(ProtocolRuntimeException::class);
        $this->expectExceptionMessage('SwitchPart发现未知的数据类型');

        $mockHandler = $this->createMock(Part::class);
        $handlers = ['type1' => $mockHandler];
        $switchPart = new SwitchPart($this->connection, 'dataKey', $handlers);

        /* @phpstan-ignore-next-line */
        $this->connection->dataKey = 'unknown_type';

        $switchPart->input('test buffer');
    }

    public function testDecode(): void
    {
        $mockHandler = $this->createMock(Part::class);
        $mockHandler->expects($this->once())
            ->method('decode')
            ->with('test buffer')
            ->willReturn('decoded data')
        ;

        $handlers = ['type1' => $mockHandler];
        $switchPart = new SwitchPart($this->connection, 'dataKey', $handlers);

        /* @phpstan-ignore-next-line */
        $this->connection->dataKey = 'type1';

        $result = $switchPart->decode('test buffer');
        $this->assertEquals('decoded data', $result);
    }

    public function testEncode(): void
    {
        $mockHandler = $this->createMock(Part::class);
        $mockHandler->expects($this->once())
            ->method('encode')
            ->with('test buffer')
            ->willReturn('encoded data')
        ;

        $handlers = ['type1' => $mockHandler];
        $switchPart = new SwitchPart($this->connection, 'dataKey', $handlers);

        /* @phpstan-ignore-next-line */
        $this->connection->dataKey = 'type1';

        $result = $switchPart->encode('test buffer');
        $this->assertEquals('encoded data', $result);
    }
}
