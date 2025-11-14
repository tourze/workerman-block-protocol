<?php

namespace Tourze\Workerman\BlockProtocol\Handler;

use Workerman\Connection\ConnectionInterface;

/**
 * 长度前缀消息处理器
 * 处理带有长度前缀的消息，常用于二进制协议
 */
class Length extends Part
{
    private ?int $expectedLength = null;

    /**
     * @param ConnectionInterface $connection   连接对象
     * @param string              $lengthFormat 长度字段的打包格式，如 'N'表示32位网络字节序整数
     * @param int                 $headerSize   长度字段的字节数
     * @param int                 $maxLength    允许的最大消息长度
     * @param string|null         $alias        解析后的数据存储在连接对象的属性名
     */
    public function __construct(
        ConnectionInterface $connection,
        private readonly string $lengthFormat = 'N',
        private readonly int $headerSize = 4,
        private readonly int $maxLength = 1048576,  // 默认最大1MB
        private readonly ?string $alias = null,
    ) {
        parent::__construct($connection);
    }

    public function input(string $buffer): int
    {
        // 已处理过数据
        if (null !== $this->getValue()) {
            return self::FLAG_CONTINUE;
        }

        $bufferLen = strlen($buffer);

        // 如果还没解析长度字段
        if (null === $this->expectedLength) {
            // 数据不足以解析长度字段
            if ($bufferLen < $this->headerSize) {
                return 0;
            }

            // 解析长度字段
            $header = substr($buffer, 0, $this->headerSize);
            $lengthData = unpack($this->lengthFormat, $header);
            $this->expectedLength = $lengthData[1] ?? 0;

            // 验证长度是否合法
            if ($this->expectedLength <= 0 || $this->expectedLength > $this->maxLength) {
                $this->connection->close();

                return 0;
            }
        }

        // 检查是否收到完整消息
        $totalLength = $this->headerSize + $this->expectedLength;
        if ($bufferLen < $totalLength) {
            return 0;
        }

        // 提取消息体
        $body = substr($buffer, $this->headerSize, $this->expectedLength);
        $this->setValue($body);

        if (null !== $this->alias) {
            /* @phpstan-ignore-next-line */
            $this->connection->{$this->alias} = $body;
        }

        return $totalLength;
    }

    private bool $decoded = false;

    public function decode(string $buffer): string
    {
        if (!$this->decoded) {
            $this->decoded = true;
            if (null !== $this->expectedLength) {
                return substr($buffer, $this->headerSize + $this->expectedLength);
            }
        }

        return $buffer;
    }

    public function encode(string $buffer): string
    {
        // 计算消息长度
        $length = strlen($buffer);

        // 打包长度字段
        $header = pack($this->lengthFormat, $length);

        // 拼接长度字段和消息体
        return $header . $buffer;
    }
}
