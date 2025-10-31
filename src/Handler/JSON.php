<?php

namespace Tourze\Workerman\BlockProtocol\Handler;

use Tourze\Workerman\BlockProtocol\Exception\InvalidProtocolArgumentException;
use Workerman\Connection\ConnectionInterface;

/**
 * JSON数据处理器
 * 处理JSON格式的数据，支持编码和解码
 */
class JSON extends Part
{
    /**
     * @param ConnectionInterface $connection 连接对象
     * @param int                 $maxLength  JSON最大长度限制
     * @param string|null         $alias      解码后的数据存储在连接对象的属性名
     * @param bool                $assoc      是否将对象转换为关联数组
     */
    public function __construct(
        ConnectionInterface $connection,
        private readonly int $maxLength = 65536,
        private readonly ?string $alias = null,
        private readonly bool $assoc = true,
    ) {
        parent::__construct($connection);
    }

    public function input(string $buffer): int
    {
        // 已经处理过了
        if (null !== $this->getValue()) {
            return self::FLAG_CONTINUE;
        }

        $length = strlen($buffer);
        if ($length > $this->maxLength) {
            $this->connection->close();

            return 0;
        }

        try {
            $data = json_decode($buffer, $this->assoc, 512, JSON_THROW_ON_ERROR);
            $this->setValue($data);

            if (null !== $this->alias) {
                /* @phpstan-ignore-next-line */
                $this->connection->{$this->alias} = $data;
            }

            return $length;
        } catch (\JsonException $e) {
            // JSON解析失败，继续等待数据
            return 0;
        }
    }

    private bool $decoded = false;

    public function decode(string $buffer): string
    {
        if (!$this->decoded) {
            $this->decoded = true;

            // 已经在input中解析过，直接消费掉整个buffer
            return '';
        }

        return $buffer;
    }

    public function encode(mixed $buffer): string
    {
        if (is_array($buffer) || is_object($buffer)) {
            $result = json_encode($buffer, JSON_UNESCAPED_UNICODE);
            if (false === $result) {
                throw new InvalidProtocolArgumentException('Failed to encode JSON: ' . json_last_error_msg());
            }

            return $result;
        }

        return is_string($buffer) ? $buffer : (string) $buffer;
    }
}
