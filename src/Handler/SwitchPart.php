<?php

namespace Tourze\Workerman\BlockProtocol\Handler;

use Workerman\Connection\ConnectionInterface;

class SwitchPart extends Part
{
    public function __construct(
        ConnectionInterface $connection,
        private readonly string $dataKey,
        private readonly array $handlers,
    )
    {
        parent::__construct($connection);
    }

    public function input(string $buffer): int
    {
        $data = $this->connection->{$this->dataKey};
        if (!isset($this->getHandlers()[$data])) {
            throw new \RuntimeException('SwitchPart发现未知的数据类型');
        }
        return $this->getHandlers()[$data]->input($buffer);
    }

    public function decode(string $buffer): string
    {
        $data = $this->connection->{$this->dataKey};
        return $this->getHandlers()[$data]->decode($buffer);
    }

    public function encode(string $buffer): string
    {
        $data = $this->connection->{$this->dataKey};
        return $this->getHandlers()[$data]->encode($buffer);
    }

    /**
     * @return Part[]
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}
