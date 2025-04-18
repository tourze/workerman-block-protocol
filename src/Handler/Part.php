<?php

namespace Tourze\Workerman\BlockProtocol\Handler;

use Workerman\Connection\ConnectionInterface;

abstract class Part
{
    const FLAG_CONTINUE = -1;

    private mixed $value = null;

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function __construct(
        protected readonly ConnectionInterface $connection,
    )
    {
    }

    /**
     * @param string $buffer
     * @return int -1:已处理, 0继续等待, 大于0是接收直接长度数据的意思
     */
    abstract public function input(string $buffer): int;

    abstract public function decode(string $buffer): string;

    abstract public function encode(string $buffer): string;
}
