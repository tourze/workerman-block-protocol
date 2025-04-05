<?php

namespace Tourze\Workerman\BlockProtocol\Handler;

use Workerman\Connection\ConnectionInterface;

/**
 * 截取并解密数据，这里总是只读一个数据喔
 */
class UnpackData extends Part
{
    public function __construct(
        ConnectionInterface $connection,
        private readonly int $length,
        private readonly ?string $format = null,
        private readonly ?string $alias = null,
        private readonly array $allowValues = [],
    )
    {
        parent::__construct($connection);
    }

    public function input(string $buffer): int
    {
        // 处理过了，不管
        if ($this->getValue() !== null) {
            return static::FLAG_CONTINUE;
        }

        $dataLen = strlen($buffer);
        if ($dataLen < $this->length) {
            return 0;
        }

        $v = substr($buffer, 0, $this->length);
        if ($this->format !== null) {
            $v = unpack($this->format, $v);
            $v = array_shift($v);
        }

        if (!empty($this->allowValues) && !in_array($v, $this->allowValues, true)) {
            $this->connection->close();
            return 0;
        }

        $this->setValue($v);
        if ($this->alias !== null) {
            $this->connection->{$this->alias} = $this->getValue();
        }
        return $dataLen;
    }

    private bool $decoded = false;

    public function decode(string $buffer): string
    {
        if (!$this->decoded) {
            $this->decoded = true;
            $buffer = substr($buffer, $this->length);
        }
        return $buffer;
    }

    public function encode(string $buffer): string
    {
        return $buffer;
    }
}
