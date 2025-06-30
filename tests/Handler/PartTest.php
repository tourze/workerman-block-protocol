<?php

namespace Tourze\Workerman\BlockProtocol\Tests\Unit\Handler;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\BlockProtocol\Handler\Part;
use Tourze\Workerman\BlockProtocol\Tests\MockConnection;

class PartTest extends TestCase
{
    private MockConnection $connection;

    protected function setUp(): void
    {
        $this->connection = new MockConnection();
    }

    public function testGetSetValue(): void
    {
        $part = new class($this->connection) extends Part {
            public function input(string $buffer): int
            {
                return 0;
            }

            public function decode(string $buffer): string
            {
                return $buffer;
            }

            public function encode(string $buffer): string
            {
                return $buffer;
            }
        };

        $this->assertNull($part->getValue());

        $part->setValue('test value');
        $this->assertEquals('test value', $part->getValue());

        $part->setValue(['array', 'value']);
        $this->assertEquals(['array', 'value'], $part->getValue());
    }

    public function testFlagContinue(): void
    {
        $this->assertSame(-1, Part::FLAG_CONTINUE);
    }
}