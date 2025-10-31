<?php

namespace Tourze\Workerman\BlockProtocol\Handler;

use Tourze\Workerman\BlockProtocol\Exception\ProtocolRuntimeException;
use Workerman\Connection\ConnectionInterface;

class SwitchPart extends Part
{
    /**
     * @param array<string, Part> $handlers
     */
    public function __construct(
        ConnectionInterface $connection,
        private readonly string $dataKey,
        private readonly array $handlers,
    ) {
        parent::__construct($connection);
    }

    public function input(string $buffer): int
    {
        /** @phpstan-ignore-next-line */
        $data = $this->connection->{$this->dataKey};
        if (!isset($this->getHandlers()[$data])) {
            throw new ProtocolRuntimeException('SwitchPart发现未知的数据类型');
        }

        return $this->getHandlers()[$data]->input($buffer);
    }

    public function decode(string $buffer): string
    {
        /** @phpstan-ignore-next-line */
        $data = $this->connection->{$this->dataKey};

        return $this->getHandlers()[$data]->decode($buffer);
    }

    public function encode(string $buffer): string
    {
        /** @phpstan-ignore-next-line */
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
