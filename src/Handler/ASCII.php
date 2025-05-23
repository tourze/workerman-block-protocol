<?php

namespace Tourze\Workerman\BlockProtocol\Handler;

use Workerman\Connection\ConnectionInterface;

/**
 * 一般用来表示读取一个字节
 */
class ASCII extends Part
{
    public function __construct(
        ConnectionInterface $connection,
        private readonly array $allowValues,
    )
    {
        parent::__construct($connection);
    }

    public function input(string $buffer): int
    {
        if ($this->getValue() !== null) {
            return static::FLAG_CONTINUE;
        }

        $v = ord($buffer[0]);
        if (!empty($this->allowValues) && !in_array($v, $this->allowValues, true)) {
            $this->connection->close();
            return 0;
        }

        $this->setValue($v);
        return 1;
    }

    private bool $decoded = false;

    public function decode(string $buffer): string
    {
        if (!$this->decoded) {
            $this->decoded = true;
            $buffer = substr($buffer, 1);
        }
        return $buffer;
    }

    public function encode(string $buffer): string
    {
        return $buffer;
    }
}
